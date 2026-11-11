<?php

declare(strict_types=1);

namespace App\Import\Distances;

use App\Data\GroundData;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Fetches a road distance matrix from the OSRM table service in one request.
 */
class OsrmClient
{
    public const string ENDPOINT = 'https://router.project-osrm.org/table/v1/driving/';

    public function __construct(private readonly string $userAgent) {}

    /**
     * @param  list<GroundData>  $grounds
     * @return list<list<?float>> metres from row ground to column ground, null when no route was found
     */
    public function distances(array $grounds): array
    {
        $coordinates = implode(';', array_map(
            fn (GroundData $ground): string => "{$ground->lng},{$ground->lat}",
            $grounds,
        ));

        $response = Http::withUserAgent($this->userAgent)
            ->timeout(120)
            ->get(self::ENDPOINT.$coordinates, ['annotations' => 'distance'])
            ->throw();

        if ($response->json('code') !== 'Ok') {
            throw new RuntimeException('OSRM table request failed: '.(string) ($response->json('message') ?? $response->json('code')));
        }

        /** @var list<list<?float>> $distances */
        $distances = $response->json('distances');

        return $distances;
    }
}
