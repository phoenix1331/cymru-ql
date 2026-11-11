<?php

declare(strict_types=1);

namespace App\Import;

use App\Data\ClubSeasonData;
use App\Data\LeagueData;
use App\Data\Row;
use App\Data\SeasonData;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Builds the current season's league membership from the Over the Terraces
 * `division` field. Rows for other seasons are hand-maintained and kept as-is.
 */
final class ClubSeasonImporter
{
    /**
     * @param  list<array<string, mixed>>  $rows
     * @param  list<ClubSeasonData>  $existing
     * @param  list<LeagueData>  $leagues
     * @return list<ClubSeasonData>
     */
    public function merge(array $rows, array $existing, SeasonData $current, array $leagues): array
    {
        $leagueSlugs = array_map(fn (LeagueData $league): string => $league->slug, $leagues);

        $kept = array_values(array_filter(
            $existing,
            fn (ClubSeasonData $clubSeason): bool => $clubSeason->season !== $current->slug,
        ));

        $currentRows = array_map(function (array $row) use ($current, $leagueSlugs): ClubSeasonData {
            Row::require($row, ['slug', 'division']);

            $league = Str::slug((string) $row['division']);

            if (! in_array($league, $leagueSlugs, true)) {
                throw new InvalidArgumentException("Unknown division \"{$row['division']}\" for club {$row['slug']}.");
            }

            return new ClubSeasonData((string) $row['slug'], $current->slug, $league);
        }, $rows);

        return [...$kept, ...$currentRows];
    }
}
