<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\League;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<League>
 */
class LeagueFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = 'Cymru '.fake()->unique()->word();

        return [
            'slug' => Str::slug($name),
            'name' => $name,
            'tier' => 2,
            'region' => fake()->randomElement(['North', 'South']),
        ];
    }
}
