<?php

declare(strict_types=1);

namespace App\Import;

use App\Data\ClubData;
use App\Data\GroundData;

/**
 * Applies hand-maintained corrections from database/data/overrides.json
 * after import and enrichment, so known bad source values never return.
 */
final readonly class Overrides
{
    /**
     * @param  array<string, array<string, mixed>>  $clubs  field overrides keyed by club slug
     * @param  array<string, array<string, mixed>>  $grounds  field overrides keyed by ground slug
     */
    public function __construct(
        private array $clubs = [],
        private array $grounds = [],
    ) {}

    /**
     * @param  array<array-key, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        /** @var array<string, array<string, mixed>> $clubs */
        $clubs = (array) ($data['clubs'] ?? []);

        /** @var array<string, array<string, mixed>> $grounds */
        $grounds = (array) ($data['grounds'] ?? []);

        return new self($clubs, $grounds);
    }

    /**
     * @param  list<ClubData>  $clubs
     * @return list<ClubData>
     */
    public function applyToClubs(array $clubs): array
    {
        return array_map(
            fn (ClubData $club): ClubData => isset($this->clubs[$club->slug])
                ? ClubData::fromArray([...$club->toArray(), ...$this->clubs[$club->slug], 'slug' => $club->slug])
                : $club,
            $clubs,
        );
    }

    /**
     * @param  list<GroundData>  $grounds
     * @return list<GroundData>
     */
    public function applyToGrounds(array $grounds): array
    {
        return array_map(
            fn (GroundData $ground): GroundData => isset($this->grounds[$ground->slug])
                ? GroundData::fromArray([...$ground->toArray(), ...$this->grounds[$ground->slug], 'slug' => $ground->slug])
                : $ground,
            $grounds,
        );
    }
}
