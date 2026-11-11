<?php

declare(strict_types=1);

namespace App\Data;

use InvalidArgumentException;

/**
 * Ground-to-ground distances. Row i, column j is the distance from
 * grounds[i] to grounds[j] in kilometres.
 */
final readonly class DistanceMatrixData
{
    /**
     * @param  list<string>  $grounds
     * @param  list<list<float>>  $roadKm
     * @param  list<list<float>>  $straightKm
     */
    public function __construct(
        public string $generatedAt,
        public string $source,
        public array $grounds,
        public array $roadKm,
        public array $straightKm,
    ) {
        $size = count($grounds);

        foreach ([$roadKm, $straightKm] as $matrix) {
            if (count($matrix) !== $size || array_any($matrix, fn (array $row): bool => count($row) !== $size)) {
                throw new InvalidArgumentException("Distance matrix must be {$size} x {$size} to match its grounds.");
            }
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        Row::require($data, ['generated_at', 'source', 'grounds', 'road_km', 'straight_km']);

        /** @var list<string> $grounds */
        $grounds = $data['grounds'];

        /** @var list<list<float>> $roadKm */
        $roadKm = $data['road_km'];

        /** @var list<list<float>> $straightKm */
        $straightKm = $data['straight_km'];

        return new self((string) $data['generated_at'], (string) $data['source'], $grounds, $roadKm, $straightKm);
    }

    /**
     * @return array{generated_at: string, source: string, grounds: list<string>, road_km: list<list<float>>, straight_km: list<list<float>>}
     */
    public function toArray(): array
    {
        return [
            'generated_at' => $this->generatedAt,
            'source' => $this->source,
            'grounds' => $this->grounds,
            'road_km' => $this->roadKm,
            'straight_km' => $this->straightKm,
        ];
    }

    /**
     * @return array{road_km: float, straight_km: float}
     */
    public function between(string $from, string $to): array
    {
        $i = array_search($from, $this->grounds, true);
        $j = array_search($to, $this->grounds, true);

        if ($i === false || $j === false) {
            throw new InvalidArgumentException("No distance between {$from} and {$to}.");
        }

        return ['road_km' => $this->roadKm[$i][$j], 'straight_km' => $this->straightKm[$i][$j]];
    }
}
