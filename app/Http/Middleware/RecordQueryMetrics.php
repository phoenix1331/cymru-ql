<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\QueryMetrics;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Adds the request's database query count and timings to the response so the
 * UI can compare what REST and GraphQL cost for the same screen.
 */
final readonly class RecordQueryMetrics
{
    public const array HEADERS = ['X-Query-Count', 'X-Query-Time-Ms', 'X-Response-Time-Ms', 'Server-Timing'];

    public function __construct(private QueryMetrics $metrics) {}

    public function handle(Request $request, Closure $next): Response
    {
        $this->metrics->reset();

        /** @var Response $response */
        $response = $next($request);

        if (! config('cymru.metrics.enabled')) {
            return $response;
        }

        $snapshot = $this->metrics->snapshot();

        $response->headers->add([
            'X-Query-Count' => (string) $snapshot['query_count'],
            'X-Query-Time-Ms' => (string) $snapshot['query_time_ms'],
            'X-Response-Time-Ms' => (string) $snapshot['response_time_ms'],
            'Server-Timing' => sprintf(
                'db;dur=%s;desc="%d queries", app;dur=%s',
                $snapshot['query_time_ms'],
                $snapshot['query_count'],
                $snapshot['response_time_ms'],
            ),
            'Access-Control-Expose-Headers' => implode(', ', self::HEADERS),
        ]);

        return $response;
    }
}
