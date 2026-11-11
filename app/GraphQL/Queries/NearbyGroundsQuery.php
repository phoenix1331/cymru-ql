<?php

declare(strict_types=1);

namespace App\GraphQL\Queries;

use App\Models\Distance;
use App\Models\Ground;
use App\Services\GroundService;
use Illuminate\Database\Eloquent\Collection;

/**
 * Nearest grounds by road. Used for the top level `nearbyGrounds` query and
 * the `Ground.nearby` field, where the root is the ground.
 */
final readonly class NearbyGroundsQuery
{
    public function __construct(
        private Lookup $lookup,
        private GroundService $grounds,
    ) {}

    /**
     * @param  array{ground?: string, limit: int, league?: string|null, season?: string|null}  $args
     * @return Collection<int, Distance>
     */
    public function __invoke(mixed $root, array $args): Collection
    {
        $ground = $root instanceof Ground ? $root : $this->lookup->ground((string) ($args['ground'] ?? ''));
        $league = $this->lookup->leagueOrNull($args['league'] ?? null);

        return $this->grounds->nearby(
            $ground,
            $args['limit'],
            $league,
            $league === null ? null : $this->lookup->season($args['season'] ?? null),
        );
    }
}
