<?php

declare(strict_types=1);

namespace App\Data;

final readonly class ClubSeasonData
{
    public function __construct(
        public string $club,
        public string $season,
        public string $league,
        public ?int $position = null,
    ) {}

    /**
     * @param  array<string, mixed>  $row
     */
    public static function fromArray(array $row): self
    {
        Row::require($row, ['club', 'season', 'league']);

        return new self(
            club: (string) $row['club'],
            season: (string) $row['season'],
            league: (string) $row['league'],
            position: Row::nullableInt($row, 'position'),
        );
    }

    /**
     * @return array{club: string, season: string, league: string, position: ?int}
     */
    public function toArray(): array
    {
        return [
            'club' => $this->club,
            'season' => $this->season,
            'league' => $this->league,
            'position' => $this->position,
        ];
    }
}
