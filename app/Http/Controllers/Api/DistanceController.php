<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LeagueDistancesRequest;
use App\Http\Requests\NearbyGroundsRequest;
use App\Http\Resources\DistanceResource;
use App\Services\GroundService;
use App\Services\LeagueService;
use App\Services\SeasonService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class DistanceController extends Controller
{
    public function __construct(
        private readonly GroundService $grounds,
        private readonly LeagueService $leagues,
        private readonly SeasonService $seasons,
    ) {}

    /**
     * The closest grounds by road, optionally limited to one league.
     */
    public function nearby(NearbyGroundsRequest $request, string $ground): AnonymousResourceCollection
    {
        $league = $request->filled('league') ? $this->leagues->find($request->string('league')->value()) : null;
        $season = $league === null ? null : $this->seasons->resolve($request->string('season')->value());

        $distances = $this->grounds->nearby($this->grounds->find($ground), $request->integer('limit', 5), $league, $season);

        return DistanceResource::collection($distances->load('toGround'));
    }

    /**
     * Distances to every ground in a league for a season, nearest first.
     */
    public function inLeague(LeagueDistancesRequest $request, string $ground): AnonymousResourceCollection
    {
        $from = $this->grounds->find($ground);
        $league = $this->leagues->find($request->string('league')->value());
        $season = $this->seasons->resolve($request->string('season')->value());

        $distances = $this->grounds->distancesInLeague($from, $league, $season)->load('toGround');

        return DistanceResource::collection($distances)->additional(['meta' => [
            'from' => ['slug' => $from->slug, 'name' => $from->name],
            'league' => $league->slug,
            'season' => $season->slug,
            'grounds' => $distances->count(),
            'total_road_km' => round($distances->sum('road_km'), 1),
        ]]);
    }

    public function between(string $from, string $to): DistanceResource
    {
        $distance = $this->grounds->distanceBetween($this->grounds->find($from), $this->grounds->find($to));

        return new DistanceResource($distance->load(['fromGround', 'toGround']));
    }
}
