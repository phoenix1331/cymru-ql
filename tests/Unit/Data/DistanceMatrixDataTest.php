<?php

declare(strict_types=1);

use App\Data\DistanceMatrixData;

function matrix(): DistanceMatrixData
{
    return new DistanceMatrixData('2026-11-11', 'test', ['a', 'b'], [[0.0, 12.5], [12.9, 0.0]], [[0.0, 9.1], [9.1, 0.0]]);
}

it('round-trips through its array shape', function () {
    expect(DistanceMatrixData::fromArray(matrix()->toArray()))->toEqual(matrix());
});

it('looks up a directed pair', function () {
    expect(matrix()->between('b', 'a'))->toBe(['road_km' => 12.9, 'straight_km' => 9.1]);
});

it('rejects unknown grounds', function () {
    matrix()->between('a', 'z');
})->throws(InvalidArgumentException::class, 'No distance between a and z.');

it('rejects matrices that do not match the grounds', function (array $road) {
    new DistanceMatrixData('2026-11-11', 'test', ['a', 'b'], $road, [[0.0, 1.0], [1.0, 0.0]]);
})->with([
    'too few rows' => [[[0.0, 1.0]]],
    'short row' => [[[0.0, 1.0], [1.0]]],
])->throws(InvalidArgumentException::class, 'must be 2 x 2');
