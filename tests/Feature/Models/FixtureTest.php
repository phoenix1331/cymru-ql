<?php

declare(strict_types=1);

use App\Enums\FixtureStatus;
use App\Enums\MatchEventType;
use App\Models\Club;
use App\Models\Fixture;
use App\Models\MatchEvent;
use Illuminate\Database\QueryException;

it('links a fixture to its clubs, venue, league and season', function () {
    $fixture = Fixture::factory()->finished(3, 1)->create();

    expect($fixture->status)->toBe(FixtureStatus::Finished)
        ->and($fixture->ground->is($fixture->homeClub->ground))->toBeTrue()
        ->and($fixture->homeClub->homeFixtures->sole()->is($fixture))->toBeTrue()
        ->and($fixture->awayClub->awayFixtures->sole()->is($fixture))->toBeTrue()
        ->and($fixture->league)->not->toBeNull()
        ->and($fixture->season)->not->toBeNull()
        ->and(Fixture::query()->finished()->count())->toBe(1);
});

it('orders match events by minute', function () {
    $fixture = Fixture::factory()->live(60)->create();
    $club = Club::query()->findOrFail($fixture->home_club_id);

    MatchEvent::factory()->for($fixture)->for($club)->create(['type' => MatchEventType::Goal, 'minute' => 55, 'home_score' => 1]);
    MatchEvent::factory()->for($fixture)->create(['type' => MatchEventType::KickOff, 'minute' => 0]);

    $events = $fixture->events;

    expect($events->pluck('minute')->all())->toBe([0, 55])
        ->and($events[1]->type)->toBe(MatchEventType::Goal)
        ->and($events[1]->club?->is($club))->toBeTrue()
        ->and($events[1]->fixture->is($fixture))->toBeTrue();
});

it('knows which events are goals', function (MatchEventType $type, bool $isGoal) {
    expect($type->isGoal())->toBe($isGoal);
})->with([
    [MatchEventType::Goal, true],
    [MatchEventType::PenaltyGoal, true],
    [MatchEventType::OwnGoal, true],
    [MatchEventType::YellowCard, false],
    [MatchEventType::FullTime, false],
]);

it('allows each pairing once per season', function () {
    $fixture = Fixture::factory()->create();

    Fixture::factory()->create([
        'season_id' => $fixture->season_id,
        'home_club_id' => $fixture->home_club_id,
        'away_club_id' => $fixture->away_club_id,
    ]);
})->throws(QueryException::class);
