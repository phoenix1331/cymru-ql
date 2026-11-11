<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Data\ClubData;
use App\Data\DataFiles;
use App\Models\Club;
use App\Models\Ground;
use Illuminate\Database\Seeder;

class ClubSeeder extends Seeder
{
    public function run(DataFiles $files): void
    {
        $grounds = SlugLookup::for(Ground::class, 'ground');

        $rows = array_map(fn (ClubData $club): array => [
            'slug' => $club->slug,
            'name' => $club->name,
            'ground_id' => $grounds->id($club->ground),
            'colour' => $club->colour,
            'nickname' => $club->nickname,
            'founded' => $club->founded,
        ], $files->clubs());

        Club::query()->upsert($rows, ['slug'], ['name', 'ground_id', 'colour', 'nickname', 'founded']);
    }
}
