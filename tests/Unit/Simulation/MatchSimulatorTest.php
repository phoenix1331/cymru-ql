<?php

declare(strict_types=1);

use App\Enums\MatchEventType;
use App\Simulation\MatchSimulator;
use App\Simulation\SimulatedEvent;

it('plays the same match from the same seed', function () {
    $simulator = new MatchSimulator;

    expect($simulator->play(1.1, 0.9, 42))->toEqual($simulator->play(1.1, 0.9, 42));
});

it('runs from kick off to full time with a consistent score', function (int $seed) {
    $events = (new MatchSimulator)->play(1.0, 1.0, $seed);
    $minutes = array_map(fn (SimulatedEvent $event): int => $event->minute, $events);
    $sorted = $minutes;
    sort($sorted);

    $home = 0;
    $away = 0;
    foreach ($events as $event) {
        if ($event->type->isGoal()) {
            $event->forHome ? $home++ : $away++;
        }
        expect([$event->homeScore, $event->awayScore])->toBe([$home, $away]);
    }

    expect($events[0]->type)->toBe(MatchEventType::KickOff)
        ->and(end($events)->type)->toBe(MatchEventType::FullTime)
        ->and($minutes)->toBe($sorted)
        ->and(array_filter($events, fn (SimulatedEvent $event): bool => $event->type === MatchEventType::HalfTime))->toHaveCount(1);
})->with(range(1, 25));

it('favours the stronger club over many matches', function () {
    $simulator = new MatchSimulator;
    $strongGoals = 0;
    $weakGoals = 0;

    foreach (range(1, 300) as $seed) {
        $final = array_last($simulator->play(1.3, 0.8, $seed));
        $strongGoals += $final->homeScore;
        $weakGoals += $final->awayScore;
    }

    expect($strongGoals)->toBeGreaterThan($weakGoals * 2);
});
