<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the football data from the committed JSON in database/data.
     */
    public function run(): void
    {
        $this->call([
            LeagueSeeder::class,
            SeasonSeeder::class,
            GroundSeeder::class,
            ClubSeeder::class,
            ClubSeasonSeeder::class,
            DistanceSeeder::class,
        ]);
    }
}
