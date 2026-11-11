<?php

declare(strict_types=1);

namespace App\GraphQL\Queries;

use App\GraphQL\Types\LeagueSeason;

final readonly class LeagueQuery
{
    public function __construct(private Lookup $lookup) {}

    /**
     * @param  array{slug: string, season?: string|null}  $args
     */
    public function __invoke(mixed $root, array $args): LeagueSeason
    {
        return new LeagueSeason($this->lookup->league($args['slug']), $this->lookup->season($args['season'] ?? null));
    }
}
