<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Counts database queries and their total time for the current request.
 * Fed by a DB::listen hook registered in AppServiceProvider.
 */
final class QueryMetrics
{
    private int $count = 0;

    private float $milliseconds = 0.0;

    private float $startedAt;

    public function __construct()
    {
        $this->startedAt = hrtime(true) / 1e6;
    }

    public function reset(): void
    {
        $this->count = 0;
        $this->milliseconds = 0.0;
        $this->startedAt = hrtime(true) / 1e6;
    }

    public function record(float $milliseconds): void
    {
        $this->count++;
        $this->milliseconds += $milliseconds;
    }

    public function count(): int
    {
        return $this->count;
    }

    public function queryMilliseconds(): float
    {
        return round($this->milliseconds, 2);
    }

    public function elapsedMilliseconds(): float
    {
        return round(hrtime(true) / 1e6 - $this->startedAt, 2);
    }

    /**
     * @return array{query_count: int, query_time_ms: float, response_time_ms: float}
     */
    public function snapshot(): array
    {
        return [
            'query_count' => $this->count(),
            'query_time_ms' => $this->queryMilliseconds(),
            'response_time_ms' => $this->elapsedMilliseconds(),
        ];
    }
}
