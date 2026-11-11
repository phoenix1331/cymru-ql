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

it('lists the current season\'s fixtures in kick off order', function () {
    $this->getJson('/api/fixtures')
        ->assertOk()
        ->assertHeader('X-Query-Count', '6')
        ->assertJsonPath('meta.total', 2)
        ->assertJsonPath('data.0', [
            'id' => $this->played->id,
            'round' => 1,
            'kicks_off_at' => '2026-08-01T13:30:00+00:00',
            'status' => 'finished',
            'minute' => 90,
            'home' => ['slug' => 'bala-town', 'name' => 'Bala Town', 'link' => url('/api/clubs/bala-town')],
            'away' => ['slug' => 'rhyl', 'name' => 'Rhyl', 'link' => url('/api/clubs/rhyl')],
            'score' => ['home' => 2, 'away' => 1],
            'ground' => ['slug' => $this->bala->ground->slug, 'name' => $this->bala->ground->name],
            'links' => [
                'self' => url("/api/fixtures/{$this->played->id}"),
                'events' => url("/api/fixtures/{$this->played->id}/events"),
            ],
        ])
        ->assertJsonPath('data.1.score', null);
});

it('filters fixtures', function (string $query, array $expectedRounds) {
    $this->getJson("/api/fixtures?{$query}")->assertOk()->assertJsonPath('data.*.round', $expectedRounds);
})->with([
    'status' => ['status=scheduled', [18]],
    'club' => ['club=bala-town', [1, 18]],
    'league' => ['league=cymru-premier', [1, 18]],
    'dates' => ['from=2026-11-01&to=2026-12-31', [18]],
    'round' => ['round=1', [1]],
    'season' => ['season=2025-26', [40]],
]);

it('validates fixture filters', function () {
    $this->getJson('/api/fixtures?status=abandoned&from=2026-12-01&to=2026-11-01&club=nobody')
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['status', 'to', 'club']);
});

it('shows a fixture and its events', function () {
    MatchEvent::factory()->for($this->played)->for($this->bala)->create(['type' => MatchEventType::Goal, 'minute' => 12, 'home_score' => 1, 'description' => 'Goal for Bala Town']);
    MatchEvent::factory()->for($this->played)->create(['type' => MatchEventType::KickOff, 'minute' => 0, 'description' => 'Kick off']);

    $this->getJson("/api/fixtures/{$this->played->id}")->assertOk()->assertJsonPath('data.home.slug', 'bala-town');

    $this->getJson("/api/fixtures/{$this->played->id}/events")
        ->assertOk()
        ->assertJsonPath('data.*.type', ['kick_off', 'goal'])
        ->assertJsonPath('data.0.club', null)
        ->assertJsonPath('data.1.club', ['slug' => 'bala-town', 'name' => 'Bala Town'])
        ->assertJsonPath('data.1.score', ['home' => 1, 'away' => 0]);

    $this->getJson('/api/fixtures/999999')->assertNotFound();
});
