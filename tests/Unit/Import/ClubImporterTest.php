<?php

declare(strict_types=1);

use App\Data\ClubData;
use App\Data\GroundData;
use App\Import\ClubImporter;

function ottRow(array $overrides = []): array
{
    return array_merge([
        'club' => 'Bala Town', 'slug' => 'bala-town', 'stadium' => 'Maes Tegid', 'city' => 'Bala',
        'lat' => 52.9089, 'lng' => -3.5963, 'capacity' => 3000, 'opened' => 1950,
        'nickname' => 'The Lakesiders', 'colour' => '#FFFFFF', 'description' => 'By the lake.',
    ], $overrides);
}

it('maps a club and its ground', function () {
    $result = (new ClubImporter)->map([ottRow()]);

    expect($result['clubs'])->toEqual([
        new ClubData('bala-town', 'Bala Town', 'maes-tegid-bala', '#FFFFFF', 'The Lakesiders'),
    ])->and($result['grounds'])->toEqual([
        new GroundData('maes-tegid-bala', 'Maes Tegid', 'Bala', 52.9089, -3.5963, 3000, 1950, null, 'By the lake.'),
    ]);
});

it('shares a ground when the name matches and the coordinates are close', function () {
    $result = (new ClubImporter)->map([
        ottRow(),
        ottRow(['club' => 'Bala Reserves', 'slug' => 'bala-reserves', 'lat' => 52.9090, 'lng' => -3.5962]),
    ]);

    expect($result['grounds'])->toHaveCount(1)
        ->and($result['clubs'][1]->ground)->toBe('maes-tegid-bala');
});

it('keeps grounds with the same name in different towns apart', function () {
    $result = (new ClubImporter)->map([
        ottRow(['stadium' => 'The Oval']),
        ottRow(['slug' => 'other', 'stadium' => 'The Oval', 'city' => 'Ynyshir', 'lat' => 51.62, 'lng' => -3.41]),
    ]);

    expect(array_map(fn (GroundData $ground): string => $ground->slug, $result['grounds']))
        ->toBe(['the-oval-bala', 'the-oval-ynyshir']);
});

it('rejects rows missing required fields', function () {
    (new ClubImporter)->map([ottRow(['stadium' => null])]);
})->throws(InvalidArgumentException::class, 'stadium');
