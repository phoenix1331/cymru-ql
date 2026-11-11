<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Data\DataFiles;
use App\Models\Distance;
use App\Models\Ground;
use Illuminate\Database\Seeder;

class DistanceSeeder extends Seeder
{
    private const int CHUNK = 500;

    public function run(DataFiles $files): void
    {
        $matrix = $files->distances();
        $grounds = SlugLookup::for(Ground::class, 'ground');
        $ids = array_map(fn (string $slug): int => $grounds->id($slug), $matrix->grounds);

        $rows = [];

        foreach ($ids as $i => $fromId) {
            foreach ($ids as $j => $toId) {
                if ($i === $j) {
                    continue;
                }

                $rows[] = [
                    'from_ground_id' => $fromId,
                    'to_ground_id' => $toId,
                    'road_km' => $matrix->roadKm[$i][$j],
                    'straight_km' => $matrix->straightKm[$i][$j],
                ];
            }
        }

        foreach (array_chunk($rows, self::CHUNK) as $chunk) {
            Distance::query()->upsert($chunk, ['from_ground_id', 'to_ground_id'], ['road_km', 'straight_km']);
        }
    }
}
