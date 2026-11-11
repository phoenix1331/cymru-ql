<?php

declare(strict_types=1);

namespace App\Simulation;

use App\Enums\MatchEventType;
use App\Models\Club;
use App\Models\Season;

/**
 * Plans a fixture's match events from the club ratings and a seed derived from
 * the season and the pairing, so the same fixture always plays out the same
 * way whether it is generated in bulk or played live.
 */
final readonly class MatchPlanner
{
    public function __construct(
        private MatchSimulator $simulator,
        private int $seed,
    ) {}

    /**
     * @param  array<int, float>  $ratings  club ratings keyed by club id
     * @return list<SimulatedEvent>
     */
    public function plan(Season $season, Club $home, Club $away, array $ratings): array
    {
        return $this->simulator->play(
            $ratings[$home->id] ?? ClubRatings::NEW_CLUB,
            $ratings[$away->id] ?? ClubRatings::NEW_CLUB,
            crc32("{$this->seed}|{$season->slug}|{$home->slug}|{$away->slug}"),
        );
    }

    /**
     * Database row for a planned event (without fixture id or timestamps).
     *
     * @return array{club_id: int|null, type: string, minute: int, home_score: int, away_score: int, description: string}
     */
    public static function row(SimulatedEvent $event, Club $home, Club $away): array
    {
        $club = $event->forHome === null ? null : ($event->forHome ? $home : $away);

        return [
            'club_id' => $club?->id,
            'type' => $event->type->value,
            'minute' => $event->minute,
            'home_score' => $event->homeScore,
            'away_score' => $event->awayScore,
            'description' => self::describe($event, $club),
        ];
    }

    public static function describe(SimulatedEvent $event, ?Club $club): string
    {
        $name = $club->name ?? '';

        return match ($event->type) {
            MatchEventType::KickOff => 'Kick off',
            MatchEventType::HalfTime => "Half time, {$event->homeScore}-{$event->awayScore}",
            MatchEventType::FullTime => "Full time, {$event->homeScore}-{$event->awayScore}",
            MatchEventType::Goal => "Goal for {$name}",
            MatchEventType::PenaltyGoal => "Penalty scored for {$name}",
            MatchEventType::OwnGoal => "Own goal, credited to {$name}",
            MatchEventType::YellowCard => "Yellow card for {$name}",
            MatchEventType::RedCard => "Red card for {$name}",
        };
    }
}
