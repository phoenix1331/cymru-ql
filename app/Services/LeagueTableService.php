<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Club;
use App\Models\ClubSeason;
use App\Models\Fixture;
use App\Models\League;
use App\Models\Season;

/**
 * Builds a league table from finished fixtures (three points for a win),
 * ordered by points, goal difference, goals scored, then name. Seasons
 * without fixtures fall back to the recorded final positions.
 */
final class LeagueTableService
{
    public const int FORM_LENGTH = 5;

    /**
     * @return list<TableRow>
     */
    public function table(League $league, Season $season): array
    {
        $memberships = ClubSeason::query()
            ->with('club')
            ->whereBelongsTo($league)
            ->whereBelongsTo($season)
            ->get();

        $hasFixtures = Fixture::query()->whereBelongsTo($league)->whereBelongsTo($season)->exists();

        if (! $hasFixtures) {
            return $memberships
                ->sortBy(fn (ClubSeason $row): string => sprintf('%03d %s', $row->position ?? 999, $row->club->name))
                ->values()
                ->map(fn (ClubSeason $row, int $index): TableRow => new TableRow($row->position ?? $index + 1, $row->club))
                ->all();
        }

        $fixtures = Fixture::query()
            ->whereBelongsTo($league)
            ->whereBelongsTo($season)
            ->finished()
            ->orderBy('kicks_off_at')
            ->get(['home_club_id', 'away_club_id', 'home_score', 'away_score']);

        $rows = $memberships->map(fn (ClubSeason $row): array => $this->tally($row->club, $fixtures->all()));

        return $rows
            ->sort(fn (array $a, array $b): int => [$b['points'], $b['gd'], $b['for'], $a['club']->name]
                <=> [$a['points'], $a['gd'], $a['for'], $b['club']->name])
            ->values()
            ->map(fn (array $row, int $index): TableRow => new TableRow(
                position: $index + 1,
                club: $row['club'],
                played: $row['played'],
                won: $row['won'],
                drawn: $row['drawn'],
                lost: $row['lost'],
                goalsFor: $row['for'],
                goalsAgainst: $row['against'],
                points: $row['points'],
                form: array_slice($row['results'], -self::FORM_LENGTH),
            ))
            ->all();
    }

    /**
     * @param  list<Fixture>  $fixtures  finished fixtures in kick off order
     * @return array{club: Club, played: int, won: int, drawn: int, lost: int, for: int, against: int, gd: int, points: int, results: list<string>}
     */
    private function tally(Club $club, array $fixtures): array
    {
        $row = ['club' => $club, 'played' => 0, 'won' => 0, 'drawn' => 0, 'lost' => 0, 'for' => 0, 'against' => 0, 'gd' => 0, 'points' => 0, 'results' => []];

        foreach ($fixtures as $fixture) {
            $isHome = $fixture->home_club_id === $club->id;

            if (! $isHome && $fixture->away_club_id !== $club->id) {
                continue;
            }

            $for = (int) ($isHome ? $fixture->home_score : $fixture->away_score);
            $against = (int) ($isHome ? $fixture->away_score : $fixture->home_score);
            $result = $for <=> $against;

            $row['played']++;
            $row['for'] += $for;
            $row['against'] += $against;
            $row['won'] += (int) ($result === 1);
            $row['drawn'] += (int) ($result === 0);
            $row['lost'] += (int) ($result === -1);
            $row['results'][] = ['L', 'D', 'W'][$result + 1];
        }

        $row['gd'] = $row['for'] - $row['against'];
        $row['points'] = $row['won'] * 3 + $row['drawn'];

        return $row;
    }
}
