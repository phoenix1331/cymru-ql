<?php

declare(strict_types=1);

use App\Models\Club;
use App\Support\QueryMetrics;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    Route::middleware('query.metrics')->get('/_test/clubs/{count}', function (int $count) {
        foreach (range(1, $count) as $ignored) {
            Club::query()->count();
        }

        return ['ok' => true];
    });
});

it('reports how many queries a request ran', function (int $count) {
    $response = $this->getJson("/_test/clubs/{$count}")->assertOk();

    expect($response->headers->get('X-Query-Count'))->toBe((string) $count)
        ->and((float) $response->headers->get('X-Query-Time-Ms'))->toBeGreaterThanOrEqual(0.0)
        ->and((float) $response->headers->get('X-Response-Time-Ms'))->toBeGreaterThan(0.0)
        ->and($response->headers->get('Server-Timing'))->toMatch("/^db;dur=[\\d.]+;desc=\"{$count} queries\", app;dur=[\\d.]+$/")
        ->and($response->headers->get('Access-Control-Expose-Headers'))->toContain('X-Query-Count');
})->with([1, 3]);

it('starts counting afresh for every request', function () {
    app(QueryMetrics::class)->record(5.0);

    expect($this->getJson('/_test/clubs/2')->headers->get('X-Query-Count'))->toBe('2');
});

it('can be switched off', function () {
    config(['cymru.metrics.enabled' => false]);

    expect($this->getJson('/_test/clubs/1')->headers->has('X-Query-Count'))->toBeFalse();
});
