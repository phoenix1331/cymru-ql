<?php

declare(strict_types=1);

namespace App\GraphQL\Queries;

use App\Models\Club;
use App\Models\Distance;
use App\Models\Ground;
use App\Services\ClubService;
use App\Services\GroundService;
use Illuminate\Database\Eloquent\Collection;

/**
 * Distances from a ground to every ground in a league, nearest first.
 * On `Club.awayTrips` the league is the club's own league that season.
 */
final readonly class LeagueDistancesQuery
{
    public function __construct(
        private Lookup $lookup,
        private GroundService $grounds,
        private ClubService $clubs,
    ) {}

    /**
     * @param  array{league: string, season?: string|null}  $args
     * @return Collection<int, Distance>
     */
    public function fromGround(Ground $ground, array $args): Collection
    {
        return $this->grounds->distancesInLeague(
            $ground,
            $this->lookup->league($args['league']),
            $this->lookup->season($args['season'] ?? null),
        );
    }

    /**
     * @param  array{season?: string|null}  $args
     * @return Collection<int, Distance>
     */
    public function awayTrips(Club $club, array $args): Collection
    {
        $season = $this->lookup->season($args['season'] ?? null);
        $membership = $this->clubs->membership($club, $season);

        if ($membership === null) {
            return new Collection;
        }

        return $this->grounds->distancesInLeague($club->ground, $membership->league, $season);
    }
}
