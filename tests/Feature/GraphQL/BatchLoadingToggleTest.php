<?php

declare(strict_types=1);

use App\Models\Club;
use App\Models\ClubSeason;
use App\Models\Ground;
use App\Models\League;
use App\Models\Season;

const LEAGUE_QUERY = '{ clubs(first: 50) { data { name ground { name clubs { name } } seasons { league { name } } } } }';

beforeEach(function () {
    $league = League::factory()->create();
    $season = Season::factory()->current()->create();

    foreach (Ground::factory()->count(6)->create() as $ground) {
        ClubSeason::factory()->for(Club::factory()->for($ground))->for($league)->for($season)->create();
    }
});

it('batch loads relations by default', function () {
    $response = $this->graphQL(LEAGUE_QUERY)->assertOk()->assertHeader('X-Batchload', 'on');

    // clubs, grounds, ground clubs, club seasons, leagues
    expect($response->json('extensions.metrics.query_count'))->toBe(5)
        ->and($response->json('extensions.metrics.batchload'))->toBeTrue();
});

it('resolves relations one by one when asked to switch batch loading off', function () {
    $response = $this->graphQL(LEAGUE_QUERY, [], [], ['X-Batchload' => 'off'])->assertOk()->assertHeader('X-Batchload', 'off');

    // clubs, then per club: ground, ground clubs, club seasons, league
    expect($response->json('extensions.metrics.query_count'))->toBe(1 + 6 * 4)
        ->and($response->json('extensions.metrics.batchload'))->toBeFalse()
        ->and($response->json('data.clubs.data'))->toHaveCount(6);
});

it('ignores the header when the toggle is not allowed', function () {
    config(['cymru.demo.batchload_toggle' => false]);

    $response = $this->graphQL(LEAGUE_QUERY, [], [], ['X-Batchload' => 'off'])->assertHeader('X-Batchload', 'on');

    expect($response->json('extensions.metrics.query_count'))->toBe(5);
});
