<?php

declare(strict_types=1);

namespace App\Import;

use App\Data\ClubData;
use App\Data\GroundData;
use App\Data\Row;
use App\Support\Geo;
use Illuminate\Support\Str;

/**
 * Maps Over the Terraces club rows to the clubs and grounds seed data.
 *
 * Grounds are shared when two clubs list the same ground name within
 * SHARED_GROUND_KM of each other; otherwise each club gets its own ground.
 */
final class ClubImporter
{
    public const float SHARED_GROUND_KM = 0.5;

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return array{clubs: list<ClubData>, grounds: list<GroundData>}
     */
    public function map(array $rows): array
    {
        $clubs = [];

        /** @var array<string, GroundData> $grounds */
        $grounds = [];

        foreach ($rows as $row) {
            Row::require($row, ['club', 'slug', 'stadium', 'city', 'lat', 'lng', 'colour']);

            $ground = $this->findSharedGround($grounds, $row) ?? $this->groundFromRow($row);
            $grounds[$ground->slug] ??= $ground;

            $clubs[] = new ClubData(
                slug: (string) $row['slug'],
                name: (string) $row['club'],
                ground: $ground->slug,
                colour: (string) $row['colour'],
                nickname: Row::nullableString($row, 'nickname'),
            );
        }

        return ['clubs' => $clubs, 'grounds' => array_values($grounds)];
    }

    /**
     * @param  array<string, GroundData>  $grounds
     * @param  array<string, mixed>  $row
     */
    private function findSharedGround(array $grounds, array $row): ?GroundData
    {
        foreach ($grounds as $ground) {
            $sameName = $ground->name === (string) $row['stadium'];
            $distance = Geo::haversineKm($ground->lat, $ground->lng, (float) $row['lat'], (float) $row['lng']);

            if ($sameName && $distance <= self::SHARED_GROUND_KM) {
                return $ground;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function groundFromRow(array $row): GroundData
    {
        $name = (string) $row['stadium'];
        $town = (string) $row['city'];

        return new GroundData(
            slug: Str::slug("{$name} {$town}"),
            name: $name,
            town: $town,
            lat: (float) $row['lat'],
            lng: (float) $row['lng'],
            capacity: Row::nullableInt($row, 'capacity'),
            opened: Row::nullableInt($row, 'opened'),
            description: Row::nullableString($row, 'description'),
        );
    }
}
