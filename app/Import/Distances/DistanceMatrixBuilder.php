<?php

declare(strict_types=1);

namespace App\Import\Distances;

use App\Data\DistanceMatrixData;
use App\Data\GroundData;
use App\Support\Geo;
use Illuminate\Support\Carbon;

/**
 * Builds the committed ground-to-ground matrix: OSRM road distances plus
 * haversine straight-line distances. Pairs OSRM cannot route fall back to
 * straight line x ROAD_FACTOR.
 */
final readonly class DistanceMatrixBuilder
{
    public const float ROAD_FACTOR = 1.3;

    public const string SOURCE = 'OSRM public demo server (road), haversine (straight line); unroutable pairs use straight line x 1.3';

    public function __construct(private OsrmClient $osrm) {}

    /**
     * @param  list<GroundData>  $grounds
     * @return array{matrix: DistanceMatrixData, fallbacks: int}
     */
    public function build(array $grounds): array
    {
        $metres = $this->osrm->distances($grounds);
        $fallbacks = 0;
        $roadKm = [];
        $straightKm = [];

        foreach ($grounds as $i => $from) {
            foreach ($grounds as $j => $to) {
                $straight = Geo::haversineKm($from->lat, $from->lng, $to->lat, $to->lng);
                $road = $metres[$i][$j] ?? null;

                if ($road === null && $i !== $j) {
                    $fallbacks++;
                }

                $straightKm[$i][$j] = round($straight, 1);
                $roadKm[$i][$j] = round($road === null ? $straight * self::ROAD_FACTOR : $road / 1000, 1);
            }
        }

        return [
            'matrix' => new DistanceMatrixData(
                generatedAt: Carbon::today()->toDateString(),
                source: self::SOURCE,
                grounds: array_map(fn (GroundData $ground): string => $ground->slug, $grounds),
                roadKm: $roadKm,
                straightKm: $straightKm,
            ),
            'fallbacks' => $fallbacks,
        ];
    }
}
