<?php

declare(strict_types=1);

use App\Data\ClubData;
use App\Data\ClubSeasonData;
use App\Data\GroundData;
use App\Data\LeagueData;
use App\Data\SeasonData;

it('round-trips each data object through its array shape', function (string $class, array $row) {
    expect($class::fromArray($row)->toArray())->toBe($row);
})->with([
    'league' => [LeagueData::class, ['slug' => 'cymru-north', 'name' => 'Cymru North', 'tier' => 2, 'region' => 'North']],
    'season' => [SeasonData::class, ['slug' => '2026-27', 'name' => '2026/27', 'starts_on' => '2026-08-01', 'ends_on' => '2027-05-31', 'is_current' => true]],
    'ground' => [GroundData::class, ['slug' => 'park-hall-oswestry', 'name' => 'Park Hall', 'town' => 'Oswestry', 'lat' => 52.87591, 'lng' => -3.02637, 'capacity' => 3000, 'opened' => 1993, 'address' => null, 'description' => 'Just outside Oswestry.']],
    'club' => [ClubData::class, ['slug' => 'the-new-saints', 'name' => 'The New Saints', 'ground' => 'park-hall-oswestry', 'colour' => '#003DA5', 'nickname' => 'The Saints', 'founded' => null]],
    'club season' => [ClubSeasonData::class, ['club' => 'the-new-saints', 'season' => '2025-26', 'league' => 'cymru-premier', 'position' => 1]],
]);

it('casts loosely typed values', function () {
    $ground = GroundData::fromArray([
        'slug' => 'g', 'name' => 'G', 'town' => 'T', 'lat' => 52, 'lng' => '-3.5', 'capacity' => '500', 'address' => '',
    ]);

    expect($ground->lat)->toBe(52.0)
        ->and($ground->lng)->toBe(-3.5)
        ->and($ground->capacity)->toBe(500)
        ->and($ground->opened)->toBeNull()
        ->and($ground->address)->toBeNull();
});

it('rejects rows with missing required keys', function () {
    LeagueData::fromArray(['slug' => 'cymru-north', 'name' => null]);
})->throws(InvalidArgumentException::class, 'missing required keys: name, tier, region');

it('fills only missing ground details', function () {
    $ground = new GroundData('g', 'G', 'T', 51.0, -3.0, capacity: 500);
    $other = new GroundData('g', 'G', 'T', 51.0, -3.0, capacity: 900, opened: 1920, address: 'Road, Town', description: 'Old ground.');

    expect($ground->fillMissingFrom($other)->toArray())->toMatchArray([
        'capacity' => 500, 'opened' => 1920, 'address' => 'Road, Town', 'description' => 'Old ground.',
    ]);
});

it('fills only missing club details', function () {
    $club = new ClubData('c', 'C', 'g', '#fff', nickname: 'The Cs');

    expect($club->fillMissingFrom(new ClubData('c', 'C', 'g', '#fff', 'Other', 1901))->toArray())
        ->toMatchArray(['nickname' => 'The Cs', 'founded' => 1901]);
});
