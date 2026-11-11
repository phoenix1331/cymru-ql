<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Data\DataFiles;
use App\Data\LeagueData;
use App\Models\League;
use Illuminate\Database\Seeder;

class LeagueSeeder extends Seeder
{
    public function run(DataFiles $files): void
    {
        League::query()->upsert(
            array_map(fn (LeagueData $league): array => $league->toArray(), $files->leagues()),
            ['slug'],
            ['name', 'tier', 'region'],
        );
    }
}
