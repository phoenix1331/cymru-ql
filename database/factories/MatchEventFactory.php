<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\MatchEventType;
use App\Models\Fixture;
use App\Models\MatchEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MatchEvent>
 */
class MatchEventFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'fixture_id' => Fixture::factory()->live(),
            'club_id' => null,
            'type' => MatchEventType::KickOff,
            'minute' => 0,
            'home_score' => 0,
            'away_score' => 0,
            'description' => null,
        ];
    }
}
