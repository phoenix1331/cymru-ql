<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Demo switch: a GraphQL request sending `X-Batchload: off` resolves
 * relations one parent at a time, showing the N+1 queries that Lighthouse's
 * batch loading normally removes. Ignored unless the toggle is allowed.
 *
 * Lighthouse reads the setting when it builds the schema for the request,
 * which happens after route middleware, so a per-request config change works
 * under php artisan serve and PHP-FPM (not under Octane's shared workers).
 */
final class ToggleBatchLoading
{
    public const string HEADER = 'X-Batchload';

    public function handle(Request $request, Closure $next): Response
    {
        if (config('cymru.demo.batchload_toggle') && strtolower((string) $request->header(self::HEADER)) === 'off') {
            config(['lighthouse.batchload_relations' => false]);
        }

        /** @var Response $response */
        $response = $next($request);

        $response->headers->set(self::HEADER, config('lighthouse.batchload_relations') ? 'on' : 'off');

        return $response;
    }
}
