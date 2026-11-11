<?php

declare(strict_types=1);

use App\Models\Club;

it('reports query metrics in the response extensions', function () {
    Club::factory()->count(3)->create();

    $response = $this->graphQL('{ clubs(first: 10) { data { name ground { name } } } }')->assertOk();

    expect($response->json('extensions.metrics.query_count'))->toBe(2)
        ->and($response->json('extensions.metrics'))->toHaveKeys(['query_count', 'query_time_ms', 'response_time_ms'])
        ->and($response->headers->get('X-Query-Count'))->toBe('2');
});

it('leaves extensions out when metrics are off', function () {
    config(['cymru.metrics.enabled' => false]);

    $this->graphQL('{ seasons { slug } }')->assertOk()->assertJsonMissingPath('extensions.metrics');
});

it('keeps parsed queries out of the database so graphql pays no extra query', function () {
    $lighthouse = require config_path('lighthouse.php');

    expect($lighthouse['query_cache']['mode'])->toBe('opcache');
});
