<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Club;
use App\Models\ClubSeason;
use App\Models\League;
use App\Models\Season;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClubSeason>
 */
class ClubSeasonFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'club_id' => Club::factory(),
            'season_id' => Season::factory(),
            'league_id' => League::factory(),
            'position' => null,
        ];
    }
}
