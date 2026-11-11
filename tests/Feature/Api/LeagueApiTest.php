<?php

declare(strict_types=1);

use App\Models\Club;
use App\Models\ClubSeason;
use App\Models\Fixture;
use App\Models\League;
use App\Models\Season;

beforeEach(function () {
    $this->premier = League::factory()->create(['slug' => 'cymru-premier', 'name' => 'Cymru Premier', 'tier' => 1, 'region' => 'National']);
    League::factory()->create(['slug' => 'cymru-north', 'name' => 'Cymru North', 'tier' => 2, 'region' => 'North']);
    $this->old = Season::factory()->create(['slug' => '2025-26', 'name' => '2025/26', 'starts_on' => '2025-08-01', 'ends_on' => '2026-05-31']);
    $this->current = Season::factory()->current()->create(['slug' => '2026-27', 'name' => '2026/27', 'starts_on' => '2026-08-01', 'ends_on' => '2027-05-31']);

    $this->tns = Club::factory()->create(['slug' => 'the-new-saints', 'name' => 'The New Saints', 'colour' => '#003DA5']);
    $this->bala = Club::factory()->create(['slug' => 'bala-town', 'name' => 'Bala Town']);

    foreach ([[$this->tns, 1], [$this->bala, 11]] as [$club, $position]) {
        ClubSeason::factory()->for($club)->for($this->premier)->for($this->old)->create(['position' => $position]);
        ClubSeason::factory()->for($club)->for($this->premier)->for($this->current)->create();
    }

    Fixture::factory()->for($this->premier)->for($this->current)->finished(1, 3)->create([
        'home_club_id' => $this->tns->id, 'away_club_id' => $this->bala->id,
    ]);
});

it('lists seasons newest first', function () {
    $this->getJson('/api/seasons')
        ->assertOk()
        ->assertExactJson(['data' => [
            ['slug' => '2026-27', 'name' => '2026/27', 'starts_on' => '2026-08-01', 'ends_on' => '2027-05-31', 'is_current' => true],
            ['slug' => '2025-26', 'name' => '2025/26', 'starts_on' => '2025-08-01', 'ends_on' => '2026-05-31', 'is_current' => false],
        ]]);
});

it('lists leagues by tier with links', function () {
    $this->getJson('/api/leagues')
        ->assertOk()
        ->assertJsonPath('data.*.slug', ['cymru-premier', 'cymru-north'])
        ->assertJsonPath('data.0.links.table', url('/api/leagues/cymru-premier/table'))
        ->assertJsonPath('data.0.links.clubs', url('/api/clubs?league=cymru-premier'));
});

it('shows a league', function () {
    $this->getJson('/api/leagues/cymru-north')->assertOk()->assertJsonPath('data.region', 'North');
    $this->getJson('/api/leagues/ardal')->assertNotFound();
});

it('returns the current table built from results', function () {
    $this->getJson('/api/leagues/cymru-premier/table')
        ->assertOk()
        ->assertJsonPath('meta.basis', 'results')
        ->assertJsonPath('meta.season.slug', '2026-27')
        ->assertJsonPath('meta.league.name', 'Cymru Premier')
        ->assertJsonPath('data.0', [
            'position' => 1,
            'club' => ['slug' => 'bala-town', 'name' => 'Bala Town', 'colour' => $this->bala->colour, 'link' => url('/api/clubs/bala-town')],
            'played' => 1, 'won' => 1, 'drawn' => 0, 'lost' => 0,
            'goals_for' => 3, 'goals_against' => 1, 'goal_difference' => 2, 'points' => 3, 'form' => ['W'],
        ])
        ->assertJsonPath('data.1.club.slug', 'the-new-saints')
        ->assertJsonPath('data.1.form', ['L']);
});

it('returns a past table from final positions', function () {
    $this->getJson('/api/leagues/cymru-premier/table?season=2025-26')
        ->assertOk()
        ->assertJsonPath('meta.basis', 'final_positions')
        ->assertJsonPath('data.*.position', [1, 11])
        ->assertJsonPath('data.0.points', null);
});

it('validates the season', function () {
    $this->getJson('/api/leagues/cymru-premier/table?season=1999-00')
        ->assertUnprocessable()
        ->assertJsonValidationErrors('season');
});
