<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\FixtureStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\ListFixturesRequest;
use App\Http\Resources\FixtureResource;
use App\Http\Resources\MatchEventResource;
use App\Services\ClubService;
use App\Services\FixtureFilters;
use App\Services\FixtureService;
use App\Services\LeagueService;
use App\Services\SeasonService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class FixtureController extends Controller
{
    private const array RELATIONS = ['homeClub', 'awayClub', 'ground'];

    public function __construct(private readonly FixtureService $fixtures) {}

    public function index(
        ListFixturesRequest $request,
        SeasonService $seasons,
        LeagueService $leagues,
        ClubService $clubs,
    ): AnonymousResourceCollection {
        $filters = new FixtureFilters(
            season: $seasons->resolve($request->string('season')->value()),
            league: $request->filled('league') ? $leagues->find($request->string('league')->value()) : null,
            club: $request->filled('club') ? $clubs->find($request->string('club')->value()) : null,
            status: $request->enum('status', FixtureStatus::class),
            from: $request->filled('from') ? CarbonImmutable::parse($request->string('from')->value()) : null,
            to: $request->filled('to') ? CarbonImmutable::parse($request->string('to')->value()) : null,
            round: $request->filled('round') ? $request->integer('round') : null,
        );

        return FixtureResource::collection(
            $this->fixtures->paginate($filters, $request->integer('per_page', 20), self::RELATIONS)->withQueryString(),
        );
    }

    public function show(int $fixture): FixtureResource
    {
        return new FixtureResource($this->fixtures->find($fixture)->load(self::RELATIONS));
    }

    public function events(int $fixture): AnonymousResourceCollection
    {
        return MatchEventResource::collection($this->fixtures->find($fixture)->events()->with('club')->get());
    }
}
