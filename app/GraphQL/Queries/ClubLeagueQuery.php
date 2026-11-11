<?php

declare(strict_types=1);

namespace App\GraphQL\Queries;

use App\GraphQL\Types\LeagueSeason;
use App\Models\Club;
use App\Services\ClubService;

/**
 * The league a club played in for a season (current by default).
 */
final readonly class ClubLeagueQuery
{
    public function __construct(
        private Lookup $lookup,
        private ClubService $clubs,
    ) {}

    /**
     * @param  array{season?: string|null}  $args
     */
    public function __invoke(Club $club, array $args): ?LeagueSeason
    {
        $season = $this->lookup->season($args['season'] ?? null);
        $membership = $this->clubs->membership($club, $season);

        return $membership === null ? null : new LeagueSeason($membership->league, $season);
    }
}
