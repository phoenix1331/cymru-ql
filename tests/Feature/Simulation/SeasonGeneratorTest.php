<?php

declare(strict_types=1);

use App\Enums\FixtureStatus;
use App\Models\Club;
use App\Models\ClubSeason;
use App\Models\Fixture;
use App\Models\League;
use App\Models\MatchEvent;
use App\Models\Season;
use App\Simulation\ClubRatings;
use App\Simulation\SeasonGenerator;
use Carbon\CarbonImmutable;

beforeEach(function () {
    $this->previous = Season::factory()->create(['slug' => '2025-26', 'starts_on' => '2025-08-01', 'ends_on' => '2026-05-31']);
    $this->season = Season::factory()->current()->create(['slug' => '2026-27', 'starts_on' => '2026-08-01', 'ends_on' => '2027-05-31']);
    $this->premier = League::factory()->create(['tier' => 1]);
    $this->north = League::factory()->create(['tier' => 2]);
    $this->clubs = Club::factory()->count(4)->create();

    foreach ($this->clubs as $index => $club) {
        ClubSeason::factory()->for($club)->for($this->season)->for($this->premier)->create();
        ClubSeason::factory()->for($club)->for($this->previous)->for($index < 2 ? $this->premier : $this->north)->create(['position' => $index % 2 + 1]);
    }
});

it('rates clubs from their previous finish', function () {
    $ratings = (new ClubRatings)->forSeason($this->season);

    expect($ratings[$this->clubs[0]->id])->toBe(1.35)
        ->and($ratings[$this->clubs[1]->id])->toBe(1.32)
        ->and($ratings[$this->clubs[2]->id])->toBe(1.0)
        ->and((new ClubRatings)->forSeason($this->previous))->toBe([]);
});

it('creates a weekly double round robin and plays fixtures already kicked off', function () {
    $result = app(SeasonGenerator::class)->generate($this->season, CarbonImmutable::parse('2026-08-16 12:00', 'UTC'));

    $fixtures = Fixture::query()->orderBy('round')->get();
    $finished = $fixtures->where('status', FixtureStatus::Finished);

    expect($result)->toBe(['fixtures' => 12, 'finished' => 6])
        ->and($fixtures->pluck('round')->unique()->values()->all())->toBe([1, 2, 3, 4, 5, 6])
        ->and($fixtures->first()->kicks_off_at->toIso8601String())->toBe('2026-08-01T13:30:00+00:00')
        ->and($fixtures->last()->kicks_off_at->toDateString())->toBe('2026-09-05')
        ->and($finished->pluck('round')->unique()->values()->all())->toBe([1, 2, 3])
        ->and($finished->every(fn (Fixture $fixture): bool => $fixture->home_score !== null && $fixture->minute === 90))->toBeTrue()
        ->and($fixtures->where('status', FixtureStatus::Scheduled)->every(fn (Fixture $fixture): bool => $fixture->home_score === null))->toBeTrue()
        ->and($fixtures->every(fn (Fixture $fixture): bool => $fixture->ground_id === $fixture->homeClub->ground_id))->toBeTrue()
        ->and(MatchEvent::query()->distinct()->count('fixture_id'))->toBe(6);
});

it('records the final score in the last event and credits goals to clubs', function () {
    app(SeasonGenerator::class)->generate($this->season, CarbonImmutable::parse('2027-06-01'));

    Fixture::query()->with('events')->get()->each(function (Fixture $fixture) {
        $last = $fixture->events->last();
        $goals = $fixture->events->filter(fn (MatchEvent $event): bool => $event->type->isGoal());

        expect([$last->home_score, $last->away_score])->toBe([$fixture->home_score, $fixture->away_score])
            ->and($goals->where('club_id', $fixture->home_club_id)->count())->toBe($fixture->home_score)
            ->and($goals->where('club_id', $fixture->away_club_id)->count())->toBe($fixture->away_score)
            ->and($fixture->events->first()->description)->toBe('Kick off');
    });
});

it('produces the same results every time it runs', function () {
    $generator = app(SeasonGenerator::class);
    $asOf = CarbonImmutable::parse('2027-06-01');
    $scores = fn (): array => Fixture::query()->orderBy('home_club_id')->orderBy('away_club_id')
        ->get()->map(fn (Fixture $fixture): string => "{$fixture->home_score}-{$fixture->away_score}")->all();

    $generator->generate($this->season, $asOf);
    $first = $scores();
    $generator->generate($this->season, $asOf);

    expect($scores())->toBe($first)
        ->and(Fixture::query()->count())->toBe(12);
});

it('describes every event type', function () {
    app(SeasonGenerator::class)->generate($this->season, CarbonImmutable::parse('2027-06-01'));

    $descriptions = MatchEvent::query()->pluck('description', 'type')->all();

    expect($descriptions['half_time'])->toStartWith('Half time, ')
        ->and($descriptions['full_time'])->toStartWith('Full time, ')
        ->and($descriptions['goal'])->toStartWith('Goal for ')
        ->and($descriptions['yellow_card'])->toStartWith('Yellow card for ');
});

it('moves the first round to the first saturday of the season', function () {
    $season = Season::factory()->make(['starts_on' => '2027-08-04']);

    expect(app(SeasonGenerator::class)->kickOff($season, 0)->toIso8601String())->toBe('2027-08-07T13:30:00+00:00')
        ->and(app(SeasonGenerator::class)->kickOff($season, 13)->toIso8601String())->toBe('2027-11-06T14:30:00+00:00');
});
