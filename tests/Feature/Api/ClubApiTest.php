<?php

declare(strict_types=1);

use App\Models\Club;
use App\Models\ClubSeason;
use App\Models\Ground;
use App\Models\League;
use App\Models\Season;

beforeEach(function () {
    $this->ground = Ground::factory()->create(['slug' => 'maes-tegid-bala', 'name' => 'Maes Tegid']);
    $this->club = Club::factory()->for($this->ground)->create([
        'slug' => 'bala-town', 'name' => 'Bala Town', 'nickname' => 'The Lakesiders', 'colour' => '#ffffff', 'founded' => 1880,
    ]);
    $this->premier = League::factory()->create(['slug' => 'cymru-premier', 'name' => 'Cymru Premier', 'tier' => 1]);
    $this->north = League::factory()->create(['slug' => 'cymru-north', 'name' => 'Cymru North', 'tier' => 2]);
    $this->old = Season::factory()->create(['slug' => '2025-26', 'name' => '2025/26', 'starts_on' => '2025-08-01']);
    $this->current = Season::factory()->current()->create(['slug' => '2026-27', 'name' => '2026/27', 'starts_on' => '2026-08-01']);
    ClubSeason::factory()->for($this->club)->for($this->premier)->for($this->old)->create(['position' => 11]);
    ClubSeason::factory()->for($this->club)->for($this->north)->for($this->current)->create();
});

it('shows a club with a reference to its ground', function () {
    $this->getJson('/api/clubs/bala-town')
        ->assertOk()
        ->assertHeader('X-Query-Count', '2')
        ->assertExactJson(['data' => [
            'slug' => 'bala-town',
            'name' => 'Bala Town',
            'nickname' => 'The Lakesiders',
            'colour' => '#ffffff',
            'founded' => 1880,
            'ground' => ['slug' => 'maes-tegid-bala', 'name' => 'Maes Tegid'],
            'links' => [
                'self' => url('/api/clubs/bala-town'),
                'seasons' => url('/api/clubs/bala-town/seasons'),
                'ground' => url('/api/grounds/maes-tegid-bala'),
            ],
        ]]);
});

it('returns 404 json for an unknown club', function () {
    $this->getJson('/api/clubs/nope')->assertNotFound()->assertJsonStructure(['message']);
});

it('lists clubs alphabetically with pagination', function () {
    Club::factory()->create(['name' => 'Aberystwyth Town']);
    Club::factory()->create(['name' => 'Caersws']);

    $this->getJson('/api/clubs?per_page=2')
        ->assertOk()
        ->assertJsonPath('data.*.name', ['Aberystwyth Town', 'Bala Town'])
        ->assertJsonPath('meta.total', 3)
        ->assertJsonPath('meta.per_page', 2)
        ->assertJsonMissingPath('data.0.ground');
});

it('filters clubs by league, defaulting to the current season', function () {
    Club::factory()->create();

    $this->getJson('/api/clubs?league=cymru-north')->assertOk()->assertJsonPath('data.*.slug', ['bala-town']);
    $this->getJson('/api/clubs?league=cymru-north&season=2025-26')->assertOk()->assertJsonCount(0, 'data');
    $this->getJson('/api/clubs?league=cymru-premier&season=2025-26')->assertOk()->assertJsonPath('data.*.slug', ['bala-town']);
});

it('validates club filters', function () {
    $this->getJson('/api/clubs?league=ardal&season=1999-00&per_page=500')
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['league', 'season', 'per_page']);
});

it('lists a club\'s seasons newest first', function () {
    $this->getJson('/api/clubs/bala-town/seasons')
        ->assertOk()
        ->assertExactJson(['data' => [
            ['season' => ['slug' => '2026-27', 'name' => '2026/27'], 'league' => ['slug' => 'cymru-north', 'name' => 'Cymru North', 'tier' => 2], 'position' => null],
            ['season' => ['slug' => '2025-26', 'name' => '2025/26'], 'league' => ['slug' => 'cymru-premier', 'name' => 'Cymru Premier', 'tier' => 1], 'position' => 11],
        ]]);
});
