<?php

declare(strict_types=1);

namespace App\Simulation;

use App\Enums\FixtureStatus;
use App\Models\Club;
use App\Models\ClubSeason;
use App\Models\Fixture;
use App\Models\MatchEvent;
use App\Models\Season;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Creates every league's fixtures for a season, one round per Saturday from
 * the season start, and simulates the results of fixtures already played.
 */
final readonly class SeasonGenerator
{
    /** A fixture counts as played once it is this many minutes past kick off. */
    public const int MATCH_LENGTH_MINUTES = 110;

    public function __construct(
        private MatchSimulator $simulator,
        private ClubRatings $ratings,
        private int $seed,
        private string $kickOffTime = '14:30',
        private string $timezone = 'Europe/London',
    ) {}

    /**
     * @return array{fixtures: int, finished: int}
     */
    public function generate(Season $season, CarbonImmutable $asOf): array
    {
        $ratings = $this->ratings->forSeason($season);
        $created = ['fixtures' => 0, 'finished' => 0];

        DB::transaction(function () use ($season, $asOf, $ratings, &$created): void {
            Fixture::query()->whereBelongsTo($season)->delete();

            $memberships = ClubSeason::query()->with('club')->whereBelongsTo($season)->get()->groupBy('league_id');

            foreach ($memberships as $leagueId => $rows) {
                $clubs = $rows->pluck('club')->sortBy('slug')->keyBy('id');

                foreach (RoundRobin::double($clubs->keys()->all()) as $index => $pairs) {
                    $kickOff = $this->kickOff($season, $index);

                    foreach ($pairs as [$homeId, $awayId]) {
                        $played = $kickOff->addMinutes(self::MATCH_LENGTH_MINUTES)->lessThanOrEqualTo($asOf);

                        $this->createFixture($season, (int) $leagueId, $clubs[$homeId], $clubs[$awayId], $index + 1, $kickOff, $played, $ratings);

                        $created['fixtures']++;
                        $created['finished'] += (int) $played;
                    }
                }
            }
        });

        return $created;
    }

    public function kickOff(Season $season, int $roundIndex): CarbonImmutable
    {
        $firstSaturday = CarbonImmutable::parse($season->starts_on->toDateString().' '.$this->kickOffTime, $this->timezone);

        while (! $firstSaturday->isSaturday()) {
            $firstSaturday = $firstSaturday->addDay();
        }

        return $firstSaturday->addWeeks($roundIndex)->utc();
    }

    /**
     * @param  array<int, float>  $ratings
     */
    private function createFixture(
        Season $season,
        int $leagueId,
        Club $home,
        Club $away,
        int $round,
        CarbonImmutable $kickOff,
        bool $played,
        array $ratings,
    ): void {
        $fixture = Fixture::query()->create([
            'season_id' => $season->id,
            'league_id' => $leagueId,
            'home_club_id' => $home->id,
            'away_club_id' => $away->id,
            'ground_id' => $home->ground_id,
            'round' => $round,
            'kicks_off_at' => $kickOff,
            'status' => FixtureStatus::Scheduled,
        ]);

        if (! $played) {
            return;
        }

        $events = $this->simulator->play(
            $ratings[$home->id] ?? ClubRatings::NEW_CLUB,
            $ratings[$away->id] ?? ClubRatings::NEW_CLUB,
            crc32("{$this->seed}|{$season->slug}|{$home->slug}|{$away->slug}"),
        );

        $final = $events[array_key_last($events)];
        $now = now();

        MatchEvent::query()->insert(array_map(fn (SimulatedEvent $event): array => [
            'fixture_id' => $fixture->id,
            'club_id' => $event->forHome === null ? null : ($event->forHome ? $home->id : $away->id),
            'type' => $event->type->value,
            'minute' => $event->minute,
            'home_score' => $event->homeScore,
            'away_score' => $event->awayScore,
            'description' => $this->describe($event, $event->forHome ? $home : $away),
            'created_at' => $now,
            'updated_at' => $now,
        ], $events));

        $fixture->update([
            'status' => FixtureStatus::Finished,
            'home_score' => $final->homeScore,
            'away_score' => $final->awayScore,
            'minute' => 90,
        ]);
    }

    private function describe(SimulatedEvent $event, Club $club): string
    {
        return match ($event->type->value) {
            'kick_off' => 'Kick off',
            'half_time' => "Half time, {$event->homeScore}-{$event->awayScore}",
            'full_time' => "Full time, {$event->homeScore}-{$event->awayScore}",
            'goal' => "Goal for {$club->name}",
            'penalty_goal' => "Penalty scored for {$club->name}",
            'own_goal' => "Own goal, credited to {$club->name}",
            'yellow_card' => "Yellow card for {$club->name}",
            default => "Red card for {$club->name}",
        };
    }
}
