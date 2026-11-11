<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Data\DataFiles;
use App\Data\GroundData;
use App\Models\Ground;
use Illuminate\Database\Seeder;

class GroundSeeder extends Seeder
{
    public function run(DataFiles $files): void
    {
        $rows = array_map(fn (GroundData $ground): array => [
            'slug' => $ground->slug,
            'name' => $ground->name,
            'town' => $ground->town,
            'latitude' => $ground->lat,
            'longitude' => $ground->lng,
            'capacity' => $ground->capacity,
            'opened' => $ground->opened,
            'address' => $ground->address,
            'description' => $ground->description,
        ], $files->grounds());

        Ground::query()->upsert(
            $rows,
            ['slug'],
            ['name', 'town', 'latitude', 'longitude', 'capacity', 'opened', 'address', 'description'],
        );
    }
}
