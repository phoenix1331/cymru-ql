<?php

declare(strict_types=1);

namespace App\Import\Enrichment;

final readonly class WikidataClub
{
    public function __construct(
        public string $entity,
        public ?int $founded = null,
        public bool $dissolved = false,
        public ?float $venueLat = null,
        public ?float $venueLng = null,
        public ?int $venueCapacity = null,
        public ?int $venueOpened = null,
    ) {}

    public function hasVenueCoordinates(): bool
    {
        return $this->venueLat !== null && $this->venueLng !== null;
    }
}
