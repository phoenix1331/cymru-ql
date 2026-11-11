<?php

declare(strict_types=1);

use App\Support\QueryMetrics;

it('totals queries and resets', function () {
    $metrics = new QueryMetrics;
    $metrics->record(1.234);
    $metrics->record(2.0);

    expect($metrics->count())->toBe(2)
        ->and($metrics->queryMilliseconds())->toBe(3.23)
        ->and($metrics->snapshot())->toHaveKeys(['query_count', 'query_time_ms', 'response_time_ms']);

    $metrics->reset();

    expect($metrics->snapshot()['query_count'])->toBe(0)
        ->and($metrics->queryMilliseconds())->toBe(0.0)
        ->and($metrics->elapsedMilliseconds())->toBeGreaterThanOrEqual(0.0);
});
