<?php

declare(strict_types=1);

namespace App\Data;

final readonly class GroundData
{
    public function __construct(
        public string $slug,
        public string $name,
        public string $town,
        public float $lat,
        public float $lng,
        public ?int $capacity = null,
        public ?int $opened = null,
        public ?string $address = null,
        public ?string $description = null,
    ) {}

    /**
     * @param  array<string, mixed>  $row
     */
    public static function fromArray(array $row): self
    {
        Row::require($row, ['slug', 'name', 'town', 'lat', 'lng']);

        return new self(
            slug: (string) $row['slug'],
            name: (string) $row['name'],
            town: (string) $row['town'],
            lat: (float) $row['lat'],
            lng: (float) $row['lng'],
            capacity: Row::nullableInt($row, 'capacity'),
            opened: Row::nullableInt($row, 'opened'),
            address: Row::nullableString($row, 'address'),
            description: Row::nullableString($row, 'description'),
        );
    }

    /**
     * Returns a copy with any empty details filled from the given values.
     */
    public function withMissingDetails(
        ?int $capacity = null,
        ?int $opened = null,
        ?string $address = null,
        ?string $description = null,
    ): self {
        return new self(
            slug: $this->slug,
            name: $this->name,
            town: $this->town,
            lat: $this->lat,
            lng: $this->lng,
            capacity: $this->capacity ?? $capacity,
            opened: $this->opened ?? $opened,
            address: $this->address ?? $address,
            description: $this->description ?? $description,
        );
    }

    public function fillMissingFrom(self $other): self
    {
        return $this->withMissingDetails($other->capacity, $other->opened, $other->address, $other->description);
    }

    /**
     * @return array{slug: string, name: string, town: string, lat: float, lng: float, capacity: ?int, opened: ?int, address: ?string, description: ?string}
     */
    public function toArray(): array
    {
        return [
            'slug' => $this->slug,
            'name' => $this->name,
            'town' => $this->town,
            'lat' => $this->lat,
            'lng' => $this->lng,
            'capacity' => $this->capacity,
            'opened' => $this->opened,
            'address' => $this->address,
            'description' => $this->description,
        ];
    }
}
