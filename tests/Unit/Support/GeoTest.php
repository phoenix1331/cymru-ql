<?php

declare(strict_types=1);

use App\Support\Geo;

it('returns zero for the same point', function () {
    expect(Geo::haversineKm(52.4, -3.5, 52.4, -3.5))->toBe(0.0);
});

it('measures cardiff to bangor as the crow flies', function () {
    // Cardiff City Stadium to Nantporth, Bangor: roughly 200 km.
    expect(Geo::haversineKm(51.4728, -3.2030, 53.2253, -4.1489))->toBeBetween(200.0, 210.0);
});
