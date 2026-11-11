<?php

declare(strict_types=1);

namespace App\Simulation;

use App\Enums\MatchEventType;

final readonly class SimulatedEvent
{
    public function __construct(
        public MatchEventType $type,
        public int $minute,
        public int $homeScore,
        public int $awayScore,
        public ?bool $forHome = null,
    ) {}
}
