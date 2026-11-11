<?php

declare(strict_types=1);

use App\Enums\MatchEventType;
use App\Models\Club;
use App\Models\Fixture;
use App\Models\League;
use App\Models\MatchEvent;
use App\Models\Season;

beforeEach(function () {
    $this->season = Season::factory()->current()->create(['slug' => '2026-27']);
    $this->old = Season::factory()->create(['slug' => '2025-26']);
    $this->league = League::factory()->create(['slug' => 'cymru-premier']);
    $this->bala = Club::factory()->create(['slug' => 'bala-town', 'name' => 'Bala Town']);
    $this->rhyl = Club::factory()->create(['slug' => 'rhyl', 'name' => 'Rhyl']);
    $base = Fixture::factory()->for($this->season)->for($this->league);

    $this->played = $base->finished(2, 1)->create([
        'home_club_id' => $this->bala->id, 'away_club_id' => $this->rhyl->id, 'kicks_off_at' => '2026-08-01 13:30:00', 'round' => 1,
    ]);
    $this->upcoming = $base->create([
        'home_club_id' => $this->rhyl->id, 'away_club_id' => $this->bala->id, 'kicks_off_at' => '2026-12-05 14:30:00', 'round' => 18,
    ]);
    Fixture::factory()->for($this->old)->create(['round' => 40]);
});

it('lists fixtures with clubs and scores', function () {
    $this->graphQL('{
        fixtures(first: 10) {
            data { id round kicksOffAt status minute homeScore awayScore homeClub { slug } awayClub { name } ground { slug } league { slug } season { slug } }
            paginatorInfo { total }
        }
    }')
        ->assertJsonPath('data.fixtures.paginatorInfo.total', 2)
        ->assertJsonPath('data.fixtures.data.0', [
            'id' => (string) $this->played->id,
            'round' => 1,
            'kicksOffAt' => '2026-08-01T13:30:00+00:00',
            'status' => 'Finished',
            'minute' => 90,
            'homeScore' => 2,
            'awayScore' => 1,
            'homeClub' => ['slug' => 'bala-town'],
            'awayClub' => ['name' => 'Rhyl'],
            'ground' => ['slug' => $this->bala->ground->slug],
            'league' => ['slug' => 'cymru-premier'],
            'season' => ['slug' => '2026-27'],
        ])
        ->assertJsonPath('data.fixtures.data.1.homeScore', null);
});

it('filters fixtures like the rest api', function (string $arguments, array $rounds) {
    $this->graphQL("{ fixtures({$arguments}) { data { round } } }")
        ->assertJsonPath('data.fixtures.data.*.round', $rounds);
})->with([
    'status' => ['status: Scheduled', [18]],
    'club' => ['club: "bala-town"', [1, 18]],
    'league' => ['league: "cymru-premier"', [1, 18]],
    'dates' => ['from: "2026-11-01T00:00:00+00:00", to: "2026-12-31T00:00:00+00:00"', [18]],
    'round' => ['round: 1', [1]],
    'season' => ['season: "2025-26"', [40]],
]);

it('rejects an unknown club filter', function () {
    $this->graphQL('{ fixtures(club: "nobody") { data { id } } }')->assertGraphQLErrorMessage('Club "nobody" not found.');
});

it('fetches a fixture with its events in match order', function () {
    MatchEvent::factory()->for($this->played)->for($this->bala)->create(['type' => MatchEventType::PenaltyGoal, 'minute' => 12, 'home_score' => 1, 'description' => 'Penalty scored for Bala Town']);
    MatchEvent::factory()->for($this->played)->create(['type' => MatchEventType::KickOff, 'minute' => 0, 'description' => 'Kick off']);

    $this->graphQL('query ($id: ID!) { fixture(id: $id) { id events { type minute homeScore awayScore description club { slug } fixture { id } } } }', ['id' => $this->played->id])
        ->assertJsonPath('data.fixture.events', [
            ['type' => 'KickOff', 'minute' => 0, 'homeScore' => 0, 'awayScore' => 0, 'description' => 'Kick off', 'club' => null, 'fixture' => ['id' => (string) $this->played->id]],
            ['type' => 'PenaltyGoal', 'minute' => 12, 'homeScore' => 1, 'awayScore' => 0, 'description' => 'Penalty scored for Bala Town', 'club' => ['slug' => 'bala-town'], 'fixture' => ['id' => (string) $this->played->id]],
        ]);

    $this->graphQL('{ fixture(id: 999999) { id } }')->assertExactJson(['data' => ['fixture' => null]]);
});
