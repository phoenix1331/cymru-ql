<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Distance;
use App\Models\Ground;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Distance>
 */
class DistanceFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $straight = fake()->randomFloat(1, 5, 300);

        return [
            'from_ground_id' => Ground::factory(),
            'to_ground_id' => Ground::factory(),
            'road_km' => round($straight * fake()->randomFloat(2, 1.1, 1.5), 1),
            'straight_km' => $straight,
        ];
    }
}
