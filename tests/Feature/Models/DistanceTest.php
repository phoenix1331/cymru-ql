<?php

declare(strict_types=1);

use App\Models\Distance;
use App\Models\Ground;
use Illuminate\Database\QueryException;

it('links a directed distance to both grounds', function () {
    [$from, $to] = Ground::factory()->count(2)->create();

    Distance::factory()->create([
        'from_ground_id' => $from->id,
        'to_ground_id' => $to->id,
        'road_km' => 221.1,
        'straight_km' => 163.7,
    ]);

    $distance = $from->distances()->sole();

    expect($distance->fromGround->is($from))->toBeTrue()
        ->and($distance->toGround->is($to))->toBeTrue()
        ->and($distance->road_km)->toBe(221.1)
        ->and($distance->straight_km)->toBe(163.7)
        ->and($to->distances)->toBeEmpty();
});

it('stores one distance per ordered pair', function () {
    $distance = Distance::factory()->create();

    Distance::factory()->create([
        'from_ground_id' => $distance->from_ground_id,
        'to_ground_id' => $distance->to_ground_id,
    ]);
})->throws(QueryException::class);
