<?php

declare(strict_types=1);

namespace App\GraphQL\Queries;

use App\Enums\FixtureStatus;
use App\GraphQL\Exceptions\NotFound;
use App\Models\Club;
use App\Models\Fixture;
use App\Services\ClubService;
use App\Services\FixtureFilters;
use App\Services\FixtureService;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Builder for the paginated `fixtures` query, using the same filters as
 * GET /api/fixtures.
 */
final readonly class FixturesQuery
{
    public function __construct(
        private Lookup $lookup,
        private FixtureService $fixtures,
        private ClubService $clubs,
    ) {}

    /**
     * @param  array{season?: string|null, league?: string|null, club?: string|null, status?: FixtureStatus|null, from?: DateTimeInterface|null, to?: DateTimeInterface|null, round?: int|null}  $args
     * @return Builder<Fixture>
     */
    public function __invoke(mixed $root, array $args): Builder
    {
        return $this->fixtures->query(new FixtureFilters(
            season: $this->lookup->season($args['season'] ?? null),
            league: $this->lookup->leagueOrNull($args['league'] ?? null),
            club: isset($args['club']) ? $this->club($args['club']) : null,
            status: $args['status'] ?? null,
            from: isset($args['from']) ? CarbonImmutable::instance($args['from']) : null,
            to: isset($args['to']) ? CarbonImmutable::instance($args['to']) : null,
            round: $args['round'] ?? null,
        ));
    }

    private function club(string $slug): Club
    {
        try {
            return $this->clubs->find($slug);
        } catch (ModelNotFoundException) {
            throw NotFound::slug('Club', $slug);
        }
    }
}
