<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Club;
use App\Models\Ground;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Club>
 */
class ClubFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->city().' Town';

        return [
            'slug' => Str::slug($name),
            'name' => $name,
            'ground_id' => Ground::factory(),
            'colour' => fake()->hexColor(),
            'nickname' => null,
            'founded' => fake()->numberBetween(1870, 2020),
        ];
    }
}
