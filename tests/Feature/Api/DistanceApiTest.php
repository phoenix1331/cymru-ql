<?php

declare(strict_types=1);

use App\Models\Club;
use App\Models\ClubSeason;
use App\Models\Distance;
use App\Models\Ground;
use App\Models\League;
use App\Models\Season;

beforeEach(function () {
    $this->home = Ground::factory()->create(['slug' => 'the-oval-caernarfon', 'name' => 'The Oval', 'town' => 'Caernarfon']);
    $this->grounds = collect([
        'nantporth-bangor' => 15.3,
        'y-traeth-porthmadog' => 31.3,
        'maesdu-park-llandudno' => 45.9,
        'jenner-park-barry' => 275.4,
    ])->map(function (float $km, string $slug) {
        $ground = Ground::factory()->create(['slug' => $slug]);
        Distance::factory()->create(['from_ground_id' => $this->home->id, 'to_ground_id' => $ground->id, 'road_km' => $km, 'straight_km' => round($km * 0.8, 1)]);

        return $ground;
    });

    $this->premier = League::factory()->create(['slug' => 'cymru-premier']);
    $this->old = Season::factory()->create(['slug' => '2025-26']);
    $this->current = Season::factory()->current()->create(['slug' => '2026-27']);
    foreach (['maesdu-park-llandudno', 'jenner-park-barry'] as $slug) {
        ClubSeason::factory()->for(Club::factory()->for($this->grounds[$slug]))->for($this->premier)->for($this->current)->create();
    }
    ClubSeason::factory()->for(Club::factory()->for($this->grounds['nantporth-bangor']))->for($this->premier)->for($this->old)->create();
});

it('lists the nearest grounds by road', function () {
    $this->getJson('/api/grounds/the-oval-caernarfon/nearby?limit=3')
        ->assertOk()
        ->assertHeader('X-Query-Count', '3')
        ->assertJsonPath('data.*.to.slug', ['nantporth-bangor', 'y-traeth-porthmadog', 'maesdu-park-llandudno'])
        ->assertJsonPath('data.0.road_km', 15.3)
        ->assertJsonPath('data.0.straight_km', 12.2)
        ->assertJsonPath('data.0.to.link', url('/api/grounds/nantporth-bangor'))
        ->assertJsonMissingPath('data.0.from');
});

it('defaults to five nearby grounds and can limit to a league', function () {
    $this->getJson('/api/grounds/the-oval-caernarfon/nearby')->assertJsonCount(4, 'data');

    $this->getJson('/api/grounds/the-oval-caernarfon/nearby?league=cymru-premier')
        ->assertJsonPath('data.*.to.slug', ['maesdu-park-llandudno', 'jenner-park-barry']);

    $this->getJson('/api/grounds/the-oval-caernarfon/nearby?league=cymru-premier&season=2025-26')
        ->assertJsonPath('data.*.to.slug', ['nantporth-bangor']);
});

it('lists every ground in a league with a total', function () {
    $this->getJson('/api/grounds/the-oval-caernarfon/distances?league=cymru-premier')
        ->assertOk()
        ->assertJsonPath('data.*.to.slug', ['maesdu-park-llandudno', 'jenner-park-barry'])
        ->assertJsonPath('meta', [
            'from' => ['slug' => 'the-oval-caernarfon', 'name' => 'The Oval'],
            'league' => 'cymru-premier',
            'season' => '2026-27',
            'grounds' => 2,
            'total_road_km' => 321.3,
        ]);
});

it('shows the distance between two grounds', function () {
    $this->getJson('/api/grounds/the-oval-caernarfon/distances/jenner-park-barry')
        ->assertOk()
        ->assertJsonPath('data.from.slug', 'the-oval-caernarfon')
        ->assertJsonPath('data.to.slug', 'jenner-park-barry')
        ->assertJsonPath('data.road_km', 275.4);

    $this->getJson('/api/grounds/jenner-park-barry/distances/the-oval-caernarfon')->assertNotFound();
});

it('validates distance queries', function () {
    $this->getJson('/api/grounds/the-oval-caernarfon/nearby?limit=99&league=ardal')
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['limit', 'league']);

    $this->getJson('/api/grounds/the-oval-caernarfon/distances')
        ->assertUnprocessable()
        ->assertJsonValidationErrors('league');
});

it('links grounds to their nearby list', function () {
    $this->getJson('/api/grounds/the-oval-caernarfon')
        ->assertJsonPath('data.links.nearby', url('/api/grounds/the-oval-caernarfon/nearby'));
});
