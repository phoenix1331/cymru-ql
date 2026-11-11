<?php

declare(strict_types=1);

namespace App\GraphQL\Listeners;

use App\Support\QueryMetrics;
use Nuwave\Lighthouse\Events\BuildExtensionsResponse;
use Nuwave\Lighthouse\Execution\ExtensionsResponse;

/**
 * Adds `extensions.metrics` to every GraphQL result: the same query count and
 * timings the REST API sends as headers, but inside the response body.
 */
final readonly class AddQueryMetricsExtension
{
    public function __construct(private QueryMetrics $metrics) {}

    public function handle(BuildExtensionsResponse $event): ?ExtensionsResponse
    {
        if (! config('cymru.metrics.enabled')) {
            return null;
        }

        return new ExtensionsResponse('metrics', $this->metrics->snapshot());
    }
}
