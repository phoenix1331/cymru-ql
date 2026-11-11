<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\FixtureStatus;
use App\Models\Club;
use App\Models\Fixture;
use App\Models\League;
use App\Models\Season;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Fixture>
 */
class FixtureFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'season_id' => Season::factory(),
            'league_id' => League::factory(),
            'home_club_id' => Club::factory(),
            'away_club_id' => Club::factory(),
            'ground_id' => fn (array $attributes): int => Club::query()->findOrFail($attributes['home_club_id'])->ground_id,
            'round' => fake()->numberBetween(1, 30),
            'kicks_off_at' => fake()->dateTimeBetween('-3 months', '+3 months'),
            'status' => FixtureStatus::Scheduled,
            'home_score' => null,
            'away_score' => null,
            'minute' => null,
        ];
    }

    public function finished(int $homeScore = 2, int $awayScore = 1): static
    {
        return $this->state([
            'status' => FixtureStatus::Finished,
            'home_score' => $homeScore,
            'away_score' => $awayScore,
            'minute' => 90,
        ]);
    }

    public function live(int $minute = 30): static
    {
        return $this->state([
            'status' => FixtureStatus::Live,
            'home_score' => 0,
            'away_score' => 0,
            'minute' => $minute,
        ]);
    }
}
