<?php

declare(strict_types=1);

namespace App\Simulation;

use App\Models\ClubSeason;
use App\Models\Season;

/**
 * Rates each club from where it finished in the previous season: the top of
 * tier 1 is strongest, clubs new to the top two tiers are weakest. Ratings
 * only shape simulated results; they are not shown anywhere.
 */
final class ClubRatings
{
    public const float NEW_CLUB = 0.9;

    /**
     * @return array<int, float> rating keyed by club id
     */
    public function forSeason(Season $season): array
    {
        $previous = Season::query()
            ->where('ends_on', '<', $season->starts_on)
            ->orderByDesc('ends_on')
            ->first();

        if ($previous === null) {
            return [];
        }

        return ClubSeason::query()
            ->with('league')
            ->whereBelongsTo($previous)
            ->whereNotNull('position')
            ->get()
            ->mapWithKeys(fn (ClubSeason $row): array => [$row->club_id => $this->rating($row->league->tier, (int) $row->position)])
            ->all();
    }

    public function rating(int $tier, int $position): float
    {
        return $tier === 1
            ? 1.35 - 0.03 * ($position - 1)
            : 1.0 - 0.015 * ($position - 1);
    }
}
