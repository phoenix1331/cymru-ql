<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\FixtureStatus;
use App\Models\Club;
use App\Models\League;
use App\Models\Season;
use Carbon\CarbonImmutable;

final readonly class FixtureFilters
{
    public function __construct(
        public Season $season,
        public ?League $league = null,
        public ?Club $club = null,
        public ?FixtureStatus $status = null,
        public ?CarbonImmutable $from = null,
        public ?CarbonImmutable $to = null,
        public ?int $round = null,
    ) {}
}
