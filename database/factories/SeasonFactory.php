<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Season;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Season>
 */
class SeasonFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = fake()->unique()->numberBetween(1990, 2090);

        return [
            'slug' => sprintf('%d-%02d', $start, ($start + 1) % 100),
            'name' => sprintf('%d/%02d', $start, ($start + 1) % 100),
            'starts_on' => "{$start}-08-01",
            'ends_on' => ($start + 1).'-05-31',
            'is_current' => false,
        ];
    }

    public function current(): static
    {
        return $this->state(['is_current' => true]);
    }
}
