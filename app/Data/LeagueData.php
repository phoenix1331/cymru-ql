<?php

declare(strict_types=1);

namespace App\Data;

final readonly class LeagueData
{
    public function __construct(
        public string $slug,
        public string $name,
        public int $tier,
        public string $region,
    ) {}

    /**
     * @param  array<string, mixed>  $row
     */
    public static function fromArray(array $row): self
    {
        Row::require($row, ['slug', 'name', 'tier', 'region']);

        return new self(
            slug: (string) $row['slug'],
            name: (string) $row['name'],
            tier: (int) $row['tier'],
            region: (string) $row['region'],
        );
    }

    /**
     * @return array{slug: string, name: string, tier: int, region: string}
     */
    public function toArray(): array
    {
        return [
            'slug' => $this->slug,
            'name' => $this->name,
            'tier' => $this->tier,
            'region' => $this->region,
        ];
    }
}
