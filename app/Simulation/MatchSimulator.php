<?php

declare(strict_types=1);

namespace App\Simulation;

use App\Enums\MatchEventType;
use Random\Engine\Mt19937;
use Random\Randomizer;

/**
 * Plays a match from two club ratings. Goals are Poisson distributed around
 * an expected value that favours the stronger side and the home club.
 * The same seed always produces the same match.
 */
final class MatchSimulator
{
    private const float HOME_GOALS = 1.45;

    private const float AWAY_GOALS = 1.15;

    private const float CARDS_PER_SIDE = 1.7;

    private const float RED_CARD_CHANCE = 0.04;

    private const float PENALTY_SHARE = 0.1;

    private const float OWN_GOAL_SHARE = 0.04;

    /**
     * @return list<SimulatedEvent> ordered by minute, from kick off to full time
     */
    public function play(float $homeRating, float $awayRating, int $seed): array
    {
        $random = new Randomizer(new Mt19937($seed));

        $incidents = [
            ...$this->goals($random, $this->poisson($random, self::HOME_GOALS * $homeRating / $awayRating), true),
            ...$this->goals($random, $this->poisson($random, self::AWAY_GOALS * $awayRating / $homeRating), false),
            ...$this->cards($random, true),
            ...$this->cards($random, false),
            [MatchEventType::HalfTime, 45, null],
        ];

        usort($incidents, fn (array $a, array $b): int => $a[1] <=> $b[1]);

        return $this->withRunningScore([[MatchEventType::KickOff, 0, null], ...$incidents, [MatchEventType::FullTime, 90, null]]);
    }

    /**
     * @return list<array{0: MatchEventType, 1: int, 2: bool}>
     */
    private function goals(Randomizer $random, int $count, bool $forHome): array
    {
        $goals = [];

        for ($i = 0; $i < $count; $i++) {
            $roll = $random->getFloat(0, 1);
            $type = match (true) {
                $roll < self::OWN_GOAL_SHARE => MatchEventType::OwnGoal,
                $roll < self::OWN_GOAL_SHARE + self::PENALTY_SHARE => MatchEventType::PenaltyGoal,
                default => MatchEventType::Goal,
            };

            $goals[] = [$type, $this->minute($random), $forHome];
        }

        return $goals;
    }

    /**
     * @return list<array{0: MatchEventType, 1: int, 2: bool}>
     */
    private function cards(Randomizer $random, bool $forHome): array
    {
        $cards = [];

        for ($i = $this->poisson($random, self::CARDS_PER_SIDE); $i > 0; $i--) {
            $cards[] = [MatchEventType::YellowCard, $this->minute($random), $forHome];
        }

        if ($random->getFloat(0, 1) < self::RED_CARD_CHANCE) {
            $cards[] = [MatchEventType::RedCard, $this->minute($random), $forHome];
        }

        return $cards;
    }

    /**
     * @param  list<array{0: MatchEventType, 1: int, 2: ?bool}>  $incidents
     * @return list<SimulatedEvent>
     */
    private function withRunningScore(array $incidents): array
    {
        $home = 0;
        $away = 0;
        $events = [];

        foreach ($incidents as [$type, $minute, $forHome]) {
            if ($type->isGoal()) {
                $forHome ? $home++ : $away++;
            }

            $events[] = new SimulatedEvent($type, $minute, $home, $away, $forHome);
        }

        return $events;
    }

    private function minute(Randomizer $random): int
    {
        return $random->getInt(1, 90);
    }

    private function poisson(Randomizer $random, float $lambda): int
    {
        $limit = exp(-$lambda);
        $count = 0;
        $product = $random->getFloat(0, 1);

        while ($product > $limit) {
            $count++;
            $product *= $random->getFloat(0, 1);
        }

        return $count;
    }
}
