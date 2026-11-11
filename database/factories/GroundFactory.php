<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Ground;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Ground>
 */
class GroundFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->lastName().' Park';
        $town = fake()->city();

        return [
            'slug' => Str::slug("{$name} {$town}"),
            'name' => $name,
            'town' => $town,
            'latitude' => fake()->randomFloat(6, 51.4, 53.4),
            'longitude' => fake()->randomFloat(6, -5.2, -2.7),
            'capacity' => fake()->numberBetween(500, 5000),
            'opened' => fake()->numberBetween(1880, 2020),
            'address' => null,
            'description' => null,
        ];
    }
}
