<?php

declare(strict_types=1);

namespace App\Live;

use App\Enums\FixtureStatus;
use App\Enums\MatchEventType;
use App\Models\Fixture;
use App\Models\MatchEvent;
use App\Models\Season;
use App\Simulation\ClubRatings;
use App\Simulation\MatchPlanner;
use App\Simulation\SimulatedEvent;
use Closure;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Sleep;

/**
 * Plays a round of fixtures live: one match minute per tick, recording each
 * event as it happens and publishing every change to GraphQL subscribers.
 */
final readonly class LiveRound
{
    public const int FULL_TIME = 90;

    public function __construct(
        private MatchPlanner $planner,
        private ClubRatings $ratings,
        private MatchFeed $feed,
    ) {}

    /**
     * The earliest round in the season that still has fixtures to play.
     *
     * @return Collection<int, Fixture>
     */
    public function next(Season $season): Collection
    {
        $round = Fixture::query()->whereBelongsTo($season)->where('status', FixtureStatus::Scheduled)->min('round');

        if ($round === null) {
            return new Collection;
        }

        return Fixture::query()
            ->with(['homeClub', 'awayClub', 'season'])
            ->whereBelongsTo($season)
            ->where('status', FixtureStatus::Scheduled)
            ->where('round', $round)
            ->orderBy('id')
            ->get();
    }

    /**
     * @param  Collection<int, Fixture>  $fixtures
     * @param  Closure(Fixture, MatchEvent): void|null  $onEvent  called for every event as it is recorded
     */
    public function play(Collection $fixtures, float $secondsPerMinute = 1.0, ?Closure $onEvent = null): void
    {
        if ($fixtures->isEmpty()) {
            return;
        }

        $ratings = $this->ratings->forSeason($fixtures->firstOrFail()->season);

        /** @var array<int, list<SimulatedEvent>> $plans */
        $plans = [];

        foreach ($fixtures as $fixture) {
            $plans[$fixture->id] = $this->planner->plan($fixture->season, $fixture->homeClub, $fixture->awayClub, $ratings);

            $fixture->update([
                'status' => FixtureStatus::Live,
                'kicks_off_at' => now(),
                'minute' => 0,
                'home_score' => 0,
                'away_score' => 0,
            ]);
        }

        for ($minute = 0; $minute <= self::FULL_TIME; $minute++) {
            foreach ($fixtures as $fixture) {
                $this->playMinute($fixture, $plans[$fixture->id], $minute, $onEvent);
            }

            if ($minute < self::FULL_TIME && $secondsPerMinute > 0) {
                Sleep::for((int) round($secondsPerMinute * 1000))->milliseconds();
            }
        }
    }

    /**
     * @param  list<SimulatedEvent>  $plan
     * @param  Closure(Fixture, MatchEvent): void|null  $onEvent
     */
    private function playMinute(Fixture $fixture, array $plan, int $minute, ?Closure $onEvent): void
    {
        $events = array_filter($plan, fn (SimulatedEvent $event): bool => $event->minute === $minute);

        // Full time is always the last event of the 90th minute.
        usort($events, fn (SimulatedEvent $a, SimulatedEvent $b): int => (int) ($a->type === MatchEventType::FullTime) <=> (int) ($b->type === MatchEventType::FullTime));

        foreach ($events as $event) {
            $recorded = $fixture->events()->create(MatchPlanner::row($event, $fixture->homeClub, $fixture->awayClub));

            $fixture->fill(['home_score' => $event->homeScore, 'away_score' => $event->awayScore]);

            if ($event->type === MatchEventType::FullTime) {
                $fixture->status = FixtureStatus::Finished;
            }

            if ($onEvent !== null) {
                $onEvent($fixture, $recorded);
            }
        }

        $fixture->minute = $minute;
        $fixture->save();

        $this->feed->publish($fixture);
    }
}
