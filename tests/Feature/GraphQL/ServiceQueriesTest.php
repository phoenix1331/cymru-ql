<?php

declare(strict_types=1);

use App\Models\Club;
use App\Models\ClubSeason;
use App\Models\Distance;
use App\Models\Fixture;
use App\Models\Ground;
use App\Models\League;
use App\Models\Season;

beforeEach(function () {
    $this->premier = League::factory()->create(['slug' => 'cymru-premier', 'name' => 'Cymru Premier', 'tier' => 1]);
    $this->north = League::factory()->create(['slug' => 'cymru-north', 'name' => 'Cymru North', 'tier' => 2]);
    $this->old = Season::factory()->create(['slug' => '2025-26', 'starts_on' => '2025-08-01', 'ends_on' => '2026-05-31']);
    $this->current = Season::factory()->current()->create(['slug' => '2026-27', 'starts_on' => '2026-08-01', 'ends_on' => '2027-05-31']);

    $this->grounds = [];
    $this->clubs = [];
    foreach (['caernarfon' => 'the-oval-caernarfon', 'bangor' => 'nantporth-bangor', 'barry' => 'jenner-park-barry'] as $key => $slug) {
        $this->grounds[$key] = Ground::factory()->create(['slug' => $slug, 'name' => ucfirst($key).' Ground']);
        $this->clubs[$key] = Club::factory()->for($this->grounds[$key])->create(['slug' => "{$key}-town", 'name' => ucfirst($key).' Town']);
    }
    $wrexham = Ground::factory()->create(['slug' => 'racecourse-wrexham', 'name' => 'Racecourse']);

    foreach (['caernarfon', 'barry'] as $key) {
        ClubSeason::factory()->for($this->clubs[$key])->for($this->premier)->for($this->current)->create();
    }
    ClubSeason::factory()->for($this->clubs['bangor'])->for($this->north)->for($this->current)->create();
    ClubSeason::factory()->for($this->clubs['bangor'])->for($this->premier)->for($this->old)->create(['position' => 3]);

    $km = [
        ['caernarfon', 'bangor', 15.3], ['caernarfon', 'barry', 275.4], ['bangor', 'caernarfon', 15.4],
        ['bangor', 'barry', 280.0], ['barry', 'caernarfon', 276.0], ['barry', 'bangor', 281.0],
    ];
    foreach ($km as [$from, $to, $road]) {
        Distance::factory()->create(['from_ground_id' => $this->grounds[$from]->id, 'to_ground_id' => $this->grounds[$to]->id, 'road_km' => $road, 'straight_km' => round($road * 0.75, 1)]);
    }
    Distance::factory()->create(['from_ground_id' => $this->grounds['caernarfon']->id, 'to_ground_id' => $wrexham->id, 'road_km' => 120.0]);

    Fixture::factory()->for($this->premier)->for($this->current)->finished(0, 2)->create([
        'home_club_id' => $this->clubs['caernarfon']->id, 'away_club_id' => $this->clubs['barry']->id,
    ]);
});

it('answers the showcase query in one request', function () {
    $response = $this->graphQL(/** @lang GraphQL */ '
        query ($slug: String!) {
            club(slug: $slug) {
                name
                ground { name }
                league {
                    league { name }
                    season { slug }
                    table { position club { name } points goalDifference form }
                }
                awayTrips { roadKm to { name clubs { name } } }
            }
        }
    ', ['slug' => 'caernarfon-town'])->assertOk();

    $response->assertExactJson(['data' => ['club' => [
        'name' => 'Caernarfon Town',
        'ground' => ['name' => 'Caernarfon Ground'],
        'league' => [
            'league' => ['name' => 'Cymru Premier'],
            'season' => ['slug' => '2026-27'],
            'table' => [
                ['position' => 1, 'club' => ['name' => 'Barry Town'], 'points' => 3, 'goalDifference' => 2, 'form' => ['W']],
                ['position' => 2, 'club' => ['name' => 'Caernarfon Town'], 'points' => 0, 'goalDifference' => -2, 'form' => ['L']],
            ],
        ],
        'awayTrips' => [
            ['roadKm' => 275.4, 'to' => ['name' => 'Barry Ground', 'clubs' => [['name' => 'Barry Town']]]],
        ],
    ]]]);
});

it('returns a league for a season with its clubs and table', function () {
    $this->graphQL('{ league(slug: "cymru-premier", season: "2025-26") { league { slug } season { slug } clubs { slug } table { position played points club { slug } } } }')
        ->assertExactJson(['data' => ['league' => [
            'league' => ['slug' => 'cymru-premier'],
            'season' => ['slug' => '2025-26'],
            'clubs' => [['slug' => 'bangor-town']],
            'table' => [['position' => 3, 'played' => null, 'points' => null, 'club' => ['slug' => 'bangor-town']]],
        ]]]);
});

it('returns a table directly', function () {
    $this->graphQL('{ table(league: "cymru-premier") { position won lost goalsFor goalsAgainst drawn played club { slug } } }')
        ->assertJsonPath('data.table.0', ['position' => 1, 'won' => 1, 'lost' => 0, 'goalsFor' => 2, 'goalsAgainst' => 0, 'drawn' => 0, 'played' => 1, 'club' => ['slug' => 'barry-town']]);
});

it('finds nearby grounds at the top level and on a ground', function () {
    $this->graphQL('{
        nearbyGrounds(ground: "the-oval-caernarfon", limit: 2) { roadKm straightKm from { slug } to { slug } }
        inLeague: nearbyGrounds(ground: "the-oval-caernarfon", league: "cymru-premier") { to { slug } }
        ground(slug: "nantporth-bangor") { nearby(limit: 1) { to { slug } } distancesInLeague(league: "cymru-premier") { to { slug } } }
    }')
        ->assertJsonPath('data.nearbyGrounds.0', ['roadKm' => 15.3, 'straightKm' => 11.5, 'from' => ['slug' => 'the-oval-caernarfon'], 'to' => ['slug' => 'nantporth-bangor']])
        ->assertJsonPath('data.nearbyGrounds.*.to.slug', ['nantporth-bangor', 'racecourse-wrexham'])
        ->assertJsonPath('data.inLeague.*.to.slug', ['jenner-park-barry'])
        ->assertJsonPath('data.ground.nearby.*.to.slug', ['the-oval-caernarfon'])
        ->assertJsonPath('data.ground.distancesInLeague.*.to.slug', ['the-oval-caernarfon', 'jenner-park-barry']);
});

it('returns no league or away trips for a season the club was not in', function () {
    $club = Club::factory()->create(['slug' => 'newcomers']);

    $this->graphQL('{ club(slug: "newcomers") { league { league { slug } } awayTrips { roadKm } } }')
        ->assertExactJson(['data' => ['club' => ['league' => null, 'awayTrips' => []]]]);

    expect($club->exists)->toBeTrue();
});

it('reports unknown slugs as client errors', function (string $query, string $message) {
    $this->graphQL($query)->assertGraphQLErrorMessage($message);
})->with([
    ['{ table(league: "ardal") { position } }', 'League "ardal" not found.'],
    ['{ table(league: "cymru-premier", season: "1999-00") { position } }', 'Season "1999-00" not found.'],
    ['{ nearbyGrounds(ground: "nowhere") { roadKm } }', 'Ground "nowhere" not found.'],
    ['{ league(slug: "ardal") { league { name } } }', 'League "ardal" not found.'],
]);

it('validates the nearby limit', function () {
    $this->graphQL('{ nearbyGrounds(ground: "the-oval-caernarfon", limit: 99) { roadKm } }')
        ->assertGraphQLValidationKeys(['limit']);

    $this->graphQL('{ ground(slug: "the-oval-caernarfon") { nearby(limit: 0) { roadKm } } }')
        ->assertGraphQLValidationKeys(['limit']);
});
