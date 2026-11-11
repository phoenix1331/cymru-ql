<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SeasonRequest;
use App\Http\Resources\LeagueResource;
use App\Http\Resources\SeasonResource;
use App\Http\Resources\TableRowResource;
use App\Services\LeagueService;
use App\Services\LeagueTableService;
use App\Services\SeasonService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class LeagueController extends Controller
{
    public function __construct(
        private readonly LeagueService $leagues,
        private readonly SeasonService $seasons,
    ) {}

    public function index(): AnonymousResourceCollection
    {
        return LeagueResource::collection($this->leagues->all());
    }

    public function show(string $league): LeagueResource
    {
        return new LeagueResource($this->leagues->find($league));
    }

    public function table(SeasonRequest $request, string $league, LeagueTableService $tables): AnonymousResourceCollection
    {
        $league = $this->leagues->find($league);
        $season = $this->seasons->resolve($request->seasonSlug());
        $rows = $tables->table($league, $season);

        return TableRowResource::collection($rows)->additional(['meta' => [
            'league' => (new LeagueResource($league))->resolve($request),
            'season' => (new SeasonResource($season))->resolve($request),
            'basis' => $rows !== [] && $rows[0]->played === null ? 'final_positions' : 'results',
        ]]);
    }
}
