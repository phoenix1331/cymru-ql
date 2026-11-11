<?php

declare(strict_types=1);

namespace App\Data;

final readonly class SeasonData
{
    public function __construct(
        public string $slug,
        public string $name,
        public string $startsOn,
        public string $endsOn,
        public bool $isCurrent,
    ) {}

    /**
     * @param  array<string, mixed>  $row
     */
    public static function fromArray(array $row): self
    {
        Row::require($row, ['slug', 'name', 'starts_on', 'ends_on', 'is_current']);

        return new self(
            slug: (string) $row['slug'],
            name: (string) $row['name'],
            startsOn: (string) $row['starts_on'],
            endsOn: (string) $row['ends_on'],
            isCurrent: (bool) $row['is_current'],
        );
    }

    /**
     * @return array{slug: string, name: string, starts_on: string, ends_on: string, is_current: bool}
     */
    public function toArray(): array
    {
        return [
            'slug' => $this->slug,
            'name' => $this->name,
            'starts_on' => $this->startsOn,
            'ends_on' => $this->endsOn,
            'is_current' => $this->isCurrent,
        ];
    }
}
