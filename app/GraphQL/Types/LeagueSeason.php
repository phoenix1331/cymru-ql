<?php

declare(strict_types=1);

namespace App\GraphQL\Types;

use App\Models\Club;
use App\Models\League;
use App\Models\Season;
use App\Services\LeagueService;
use App\Services\LeagueTableService;
use App\Services\TableRow;
use Illuminate\Database\Eloquent\Collection;

/**
 * A league in one season: the clubs in it and its table.
 */
final readonly class LeagueSeason
{
    public function __construct(
        public League $league,
        public Season $season,
    ) {}

    /**
     * @return Collection<int, Club>
     */
    public function clubs(): Collection
    {
        return app(LeagueService::class)->clubs($this->league, $this->season);
    }

    /**
     * @return list<TableRow>
     */
    public function table(): array
    {
        return app(LeagueTableService::class)->table($this->league, $this->season);
    }
}
