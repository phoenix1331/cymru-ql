<?php

declare(strict_types=1);

use App\Data\DataFiles;
use App\Data\LeagueData;
use App\Data\SeasonData;

it('resolves the data directory from config', function () {
    config(['cymru.data_path' => '/tmp/somewhere']);

    expect(app(DataFiles::class)->path(DataFiles::LEAGUES))->toBe('/tmp/somewhere/leagues.json');
});

it('ships the three leagues in scope', function () {
    $leagues = app(DataFiles::class)->leagues();

    expect(array_map(fn (LeagueData $league): string => $league->slug, $leagues))
        ->toBe(['cymru-premier', 'cymru-north', 'cymru-south'])
        ->and(array_map(fn (LeagueData $league): int => $league->tier, $leagues))
        ->toBe([1, 2, 2]);
});

it('ships exactly one current season', function () {
    $current = array_filter(app(DataFiles::class)->seasons(), fn (SeasonData $season): bool => $season->isCurrent);

    expect($current)->toHaveCount(1)
        ->and(array_values($current)[0]->slug)->toBe('2026-27');
});
