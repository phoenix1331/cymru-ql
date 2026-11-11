<?php

declare(strict_types=1);

namespace App\GraphQL\Queries;

use App\Services\LeagueTableService;
use App\Services\TableRow;

final readonly class TableQuery
{
    public function __construct(
        private Lookup $lookup,
        private LeagueTableService $tables,
    ) {}

    /**
     * @param  array{league: string, season?: string|null}  $args
     * @return list<TableRow>
     */
    public function __invoke(mixed $root, array $args): array
    {
        return $this->tables->table($this->lookup->league($args['league']), $this->lookup->season($args['season'] ?? null));
    }
}
