<?php

declare(strict_types=1);

use App\Simulation\RoundRobin;

it('schedules every pairing home and away', function (int $size) {
    $clubs = range(1, $size);
    $rounds = RoundRobin::double($clubs);
    $pairings = array_merge(...$rounds);

    expect($rounds)->toHaveCount(2 * ($size - 1))
        ->and($pairings)->toHaveCount($size * ($size - 1))
        ->and(array_unique(array_map(fn (array $pair): string => "{$pair[0]}-{$pair[1]}", $pairings)))->toHaveCount($size * ($size - 1));

    foreach ($rounds as $round) {
        $playing = array_merge(...$round);
        sort($playing);
        expect($playing)->toBe($clubs);
    }

    $homeGames = array_count_values(array_column($pairings, 0));
    expect(array_values(array_unique($homeGames)))->toBe([$size - 1]);
})->with([2, 4, 16]);

it('rejects an odd number of clubs', function () {
    RoundRobin::double([1, 2, 3]);
})->throws(InvalidArgumentException::class, 'even number of clubs');
