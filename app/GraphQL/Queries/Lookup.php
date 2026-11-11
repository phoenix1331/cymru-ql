<?php

declare(strict_types=1);

namespace App\GraphQL\Queries;

use App\GraphQL\Exceptions\NotFound;
use App\Models\Ground;
use App\Models\League;
use App\Models\Season;
use App\Services\GroundService;
use App\Services\LeagueService;
use App\Services\SeasonService;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Resolves slugs from GraphQL arguments through the shared services,
 * turning misses into client-safe errors.
 */
final readonly class Lookup
{
    public function __construct(
        private GroundService $grounds,
        private LeagueService $leagues,
        private SeasonService $seasons,
    ) {}

    public function ground(string $slug): Ground
    {
        return $this->attempt(fn (): Ground => $this->grounds->find($slug), 'Ground', $slug);
    }

    public function league(string $slug): League
    {
        return $this->attempt(fn (): League => $this->leagues->find($slug), 'League', $slug);
    }

    public function leagueOrNull(?string $slug): ?League
    {
        return $slug === null ? null : $this->league($slug);
    }

    public function season(?string $slug): Season
    {
        return $this->attempt(fn (): Season => $this->seasons->resolve($slug), 'Season', (string) $slug);
    }

    /**
     * @template T
     *
     * @param  callable(): T  $find
     * @return T
     */
    private function attempt(callable $find, string $type, string $slug): mixed
    {
        try {
            return $find();
        } catch (ModelNotFoundException) {
            throw NotFound::slug($type, $slug);
        }
    }
}
