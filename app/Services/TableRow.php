<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Club;

/**
 * One line of a league table. Stats are null for seasons recorded only by
 * final position (no results in the database).
 */
final readonly class TableRow
{
    /**
     * @param  list<string>|null  $form  last five results, oldest first (W, D or L)
     */
    public function __construct(
        public int $position,
        public Club $club,
        public ?int $played = null,
        public ?int $won = null,
        public ?int $drawn = null,
        public ?int $lost = null,
        public ?int $goalsFor = null,
        public ?int $goalsAgainst = null,
        public ?int $points = null,
        public ?array $form = null,
    ) {}

    public function goalDifference(): ?int
    {
        return $this->goalsFor === null || $this->goalsAgainst === null ? null : $this->goalsFor - $this->goalsAgainst;
    }
}
