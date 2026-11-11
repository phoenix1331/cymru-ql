<?php

declare(strict_types=1);

use App\Models\Club;
use App\Models\ClubSeason;
use App\Models\Ground;
use App\Models\League;
use App\Models\Season;

beforeEach(function () {
    $this->ground = Ground::factory()->create([
        'slug' => 'maes-tegid-bala', 'name' => 'Maes Tegid', 'town' => 'Bala', 'latitude' => 52.9089, 'longitude' => -3.5963, 'capacity' => 3000, 'opened' => null,
    ]);
    $this->club = Club::factory()->for($this->ground)->create(['slug' => 'bala-town', 'name' => 'Bala Town', 'nickname' => 'The Lakesiders', 'colour' => '#FFFFFF', 'founded' => 1880]);
    $this->premier = League::factory()->create(['slug' => 'cymru-premier', 'name' => 'Cymru Premier', 'tier' => 1, 'region' => 'National']);
    $this->north = League::factory()->create(['slug' => 'cymru-north', 'name' => 'Cymru North', 'tier' => 2, 'region' => 'North']);
    League::factory()->create(['slug' => 'cymru-south', 'name' => 'Cymru South', 'tier' => 2, 'region' => 'South']);
    $this->old = Season::factory()->create(['slug' => '2025-26', 'name' => '2025/26', 'starts_on' => '2025-08-01', 'ends_on' => '2026-05-31']);
    $this->current = Season::factory()->current()->create(['slug' => '2026-27', 'name' => '2026/27', 'starts_on' => '2026-08-01', 'ends_on' => '2027-05-31']);
    ClubSeason::factory()->for($this->club)->for($this->premier)->for($this->old)->create(['position' => 11]);
    ClubSeason::factory()->for($this->club)->for($this->north)->for($this->current)->create();
});

it('fetches a club with its ground and league history in one request', function () {
    $this->graphQL(/** @lang GraphQL */ '
        query ($slug: String!) {
            club(slug: $slug) {
                name nickname colour founded
                ground { name town capacity opened }
                seasons { position season { slug isCurrent } league { name tier } }
            }
        }
    ', ['slug' => 'bala-town'])
        ->assertOk()
        ->assertHeader('X-Query-Count', '5')
        ->assertExactJson(['data' => ['club' => [
            'name' => 'Bala Town',
            'nickname' => 'The Lakesiders',
            'colour' => '#FFFFFF',
            'founded' => 1880,
            'ground' => ['name' => 'Maes Tegid', 'town' => 'Bala', 'capacity' => 3000, 'opened' => null],
            'seasons' => [
                ['position' => 11, 'season' => ['slug' => '2025-26', 'isCurrent' => false], 'league' => ['name' => 'Cymru Premier', 'tier' => 1]],
                ['position' => null, 'season' => ['slug' => '2026-27', 'isCurrent' => true], 'league' => ['name' => 'Cymru North', 'tier' => 2]],
            ],
        ]]]);
});

it('returns null for an unknown club', function () {
    $this->graphQL('{ club(slug: "nope") { name } }')->assertExactJson(['data' => ['club' => null]]);
});

it('lists seasons and leagues in order', function () {
    $this->graphQL('{ seasons { slug startsOn endsOn } leagues { slug } season(slug: "2025-26") { name } }')
        ->assertJsonPath('data.seasons', [
            ['slug' => '2026-27', 'startsOn' => '2026-08-01', 'endsOn' => '2027-05-31'],
            ['slug' => '2025-26', 'startsOn' => '2025-08-01', 'endsOn' => '2026-05-31'],
        ])
        ->assertJsonPath('data.leagues.*.slug', ['cymru-premier', 'cymru-north', 'cymru-south'])
        ->assertJsonPath('data.season.name', '2025/26');
});

it('paginates clubs alphabetically and caps the page size', function () {
    Club::factory()->create(['name' => 'Aberystwyth Town']);
    Club::factory()->create(['name' => 'Caersws']);

    $this->graphQL('{ clubs(first: 2) { data { name } paginatorInfo { total currentPage lastPage } } }')
        ->assertJsonPath('data.clubs.data.*.name', ['Aberystwyth Town', 'Bala Town'])
        ->assertJsonPath('data.clubs.paginatorInfo', ['total' => 3, 'currentPage' => 1, 'lastPage' => 2]);

    $this->graphQL('{ clubs(first: 500) { data { name } } }')->assertGraphQLErrorMessage('Maximum number of 100 requested items exceeded, got 500. Fetch smaller chunks.');
});

it('shows a ground with the clubs that play there', function () {
    Club::factory()->for($this->ground)->create(['name' => 'Bala Reserves']);

    $this->graphQL('{ ground(slug: "maes-tegid-bala") { name latitude longitude clubs { name } } grounds { paginatorInfo { total } } }')
        ->assertJsonPath('data.ground.latitude', 52.9089)
        ->assertJsonPath('data.ground.clubs.*.name', ['Bala Town', 'Bala Reserves'])
        ->assertJsonPath('data.grounds.paginatorInfo.total', 1);
});

it('batch loads relations so the query count does not grow with the page', function (int $extraClubs) {
    Club::factory()->count($extraClubs)->create();

    $this->graphQL('{ clubs(first: 50) { data { name ground { name } } } }')
        ->assertOk()
        ->assertHeader('X-Query-Count', '2');
})->with([0, 10, 30]);
