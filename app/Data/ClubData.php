<?php

declare(strict_types=1);

namespace App\Data;

final readonly class ClubData
{
    public function __construct(
        public string $slug,
        public string $name,
        public string $ground,
        public string $colour,
        public ?string $nickname = null,
        public ?int $founded = null,
    ) {}

    /**
     * @param  array<string, mixed>  $row
     */
    public static function fromArray(array $row): self
    {
        Row::require($row, ['slug', 'name', 'ground', 'colour']);

        return new self(
            slug: (string) $row['slug'],
            name: (string) $row['name'],
            ground: (string) $row['ground'],
            colour: (string) $row['colour'],
            nickname: Row::nullableString($row, 'nickname'),
            founded: Row::nullableInt($row, 'founded'),
        );
    }

    /**
     * Returns a copy with any empty details filled from the given values.
     */
    public function withMissingDetails(?string $nickname = null, ?int $founded = null): self
    {
        return new self(
            slug: $this->slug,
            name: $this->name,
            ground: $this->ground,
            colour: $this->colour,
            nickname: $this->nickname ?? $nickname,
            founded: $this->founded ?? $founded,
        );
    }

    public function fillMissingFrom(self $other): self
    {
        return $this->withMissingDetails($other->nickname, $other->founded);
    }

    /**
     * @return array{slug: string, name: string, ground: string, colour: string, nickname: ?string, founded: ?int}
     */
    public function toArray(): array
    {
        return [
            'slug' => $this->slug,
            'name' => $this->name,
            'ground' => $this->ground,
            'colour' => $this->colour,
            'nickname' => $this->nickname,
            'founded' => $this->founded,
        ];
    }
}
