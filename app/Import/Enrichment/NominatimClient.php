<?php

declare(strict_types=1);

namespace App\Import\Enrichment;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;

/**
 * Reverse geocodes coordinates to a short postal address using the
 * OpenStreetMap Nominatim API (ODbL). Honours the one request per second
 * usage policy.
 */
class NominatimClient
{
    public const string ENDPOINT = 'https://nominatim.openstreetmap.org/reverse';

    private const array LOCALITY_KEYS = ['town', 'city', 'village', 'suburb', 'hamlet'];

    private bool $hasRequested = false;

    public function __construct(private readonly string $userAgent) {}

    public function address(float $lat, float $lng): ?string
    {
        if ($this->hasRequested) {
            Sleep::for(1)->second();
        }

        $this->hasRequested = true;

        $response = Http::withUserAgent($this->userAgent)
            ->timeout(30)
            ->get(self::ENDPOINT, [
                'format' => 'jsonv2',
                'lat' => $lat,
                'lon' => $lng,
                'zoom' => 18,
                'addressdetails' => 1,
            ]);

        if ($response->failed()) {
            return null;
        }

        /** @var array<string, string> $address */
        $address = $response->json('address', []);

        $locality = collect(self::LOCALITY_KEYS)->map(fn (string $key): ?string => $address[$key] ?? null)->filter()->first();

        $parts = array_filter([$address['road'] ?? null, $locality, $address['postcode'] ?? null]);

        return $parts === [] ? null : implode(', ', $parts);
    }
}
