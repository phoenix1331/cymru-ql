<?php

declare(strict_types=1);

use App\Enums\FixtureStatus;
use App\Enums\MatchEventType;
use App\Live\LiveRound;
use App\Live\MatchFeed;
use App\Models\Club;
use App\Models\ClubSeason;
use App\Models\Fixture;
use App\Models\League;
use App\Models\MatchEvent;
use App\Models\Season;
use App\Simulation\MatchPlanner;
use App\Simulation\SeasonGenerator;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Sleep;

beforeEach(function () {
    Sleep::fake();

    $this->season = Season::factory()->current()->create(['slug' => '2026-27', 'starts_on' => '2026-08-01', 'ends_on' => '2027-05-31']);
    $league = League::factory()->create();
    foreach (Club::factory()->count(4)->create() as $club) {
        ClubSeason::factory()->for($club)->for($league)->for($this->season)->create();
    }

    app(SeasonGenerator::class)->generate($this->season, CarbonImmutable::parse('2026-07-01'));

    $this->feed = Mockery::spy(MatchFeed::class);
    app()->instance(MatchFeed::class, $this->feed);
});

it('picks the earliest round with fixtures still to play', function () {
    $live = app(LiveRound::class);

    expect($live->next($this->season)->pluck('round')->unique()->all())->toBe([1]);

    Fixture::query()->where('round', 1)->update(['status' => FixtureStatus::Finished]);

    expect($live->next($this->season)->pluck('round')->unique()->all())->toBe([2])
        ->and($live->next($this->season))->toHaveCount(2);
});

it('plays a round to full time with the planned results and publishes every minute', function () {
    $live = app(LiveRound::class);
    $fixtures = $live->next($this->season);
    $seen = [];

    $live->play($fixtures, 1.0, function (Fixture $fixture, MatchEvent $event) use (&$seen) {
        $seen[] = $event->type;
    });

    foreach ($fixtures as $fixture) {
        $fixture->refresh();
        $planned = app(MatchPlanner::class)->plan($this->season, $fixture->homeClub, $fixture->awayClub, []);
        $final = $planned[array_key_last($planned)];

        expect($fixture->status)->toBe(FixtureStatus::Finished)
            ->and($fixture->minute)->toBe(90)
            ->and([$fixture->home_score, $fixture->away_score])->toBe([$final->homeScore, $final->awayScore])
            ->and($fixture->events()->count())->toBe(count($planned))
            ->and($fixture->events->last()->type)->toBe(MatchEventType::FullTime);
    }

    expect($seen)->toContain(MatchEventType::KickOff, MatchEventType::HalfTime)
        ->and(array_count_values(array_map(fn (MatchEventType $type): string => $type->value, $seen))['full_time'])->toBe(2);

    $this->feed->shouldHaveReceived('publish')->times(2 * 91);
    Sleep::assertSleptTimes(90);
});

it('does nothing for an empty round and can run without pauses', function () {
    $live = app(LiveRound::class);
    $live->play(new Collection);
    $live->play($live->next($this->season), 0);

    Sleep::assertNeverSlept();
    $this->feed->shouldHaveReceived('publish')->times(2 * 91);
});
