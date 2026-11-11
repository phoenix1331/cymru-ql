<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ListClubsRequest;
use App\Http\Resources\ClubResource;
use App\Http\Resources\ClubSeasonResource;
use App\Services\ClubService;
use App\Services\LeagueService;
use App\Services\SeasonService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class ClubController extends Controller
{
    public function __construct(
        private readonly ClubService $clubs,
        private readonly LeagueService $leagues,
        private readonly SeasonService $seasons,
    ) {}

    public function index(ListClubsRequest $request): AnonymousResourceCollection
    {
        $league = $request->filled('league') ? $this->leagues->find($request->string('league')->value()) : null;
        $season = $league === null ? null : $this->seasons->resolve($request->string('season')->value());

        return ClubResource::collection(
            $this->clubs->paginate($league, $season, $request->integer('per_page', 20))->withQueryString(),
        );
    }

    public function show(string $club): ClubResource
    {
        return new ClubResource($this->clubs->find($club)->load('ground'));
    }

    public function seasons(string $club): AnonymousResourceCollection
    {
        return ClubSeasonResource::collection($this->clubs->history($this->clubs->find($club)));
    }
}
