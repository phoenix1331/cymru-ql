import { record } from './recorder';
import type { ApiSource } from './settings';

interface GraphQLMetrics {
    query_count?: number;
    query_time_ms?: number;
    batchload?: boolean;
}

function headerNumber(response: Response, name: string): number | null {
    const value = response.headers.get(name);
    return value === null ? null : Number(value);
}

function graphqlMetrics(text: string): GraphQLMetrics {
    try {
        return (JSON.parse(text) as { extensions?: { metrics?: GraphQLMetrics } }).extensions?.metrics ?? {};
    } catch {
        return {};
    }
}

/**
 * fetch, plus a record of what the request cost: payload size, browser
 * timing, and the server's database query count from the X-Query-Count header
 * (REST) or extensions.metrics (GraphQL).
 */
export async function instrumentedFetch(api: ApiSource, input: RequestInfo | URL, init?: RequestInit): Promise<Response> {
    const started = performance.now();
    const response = await fetch(input, init);
    const text = await response.clone().text();
    const durationMs = performance.now() - started;
    const metrics = api === 'graphql' ? graphqlMetrics(text) : {};

    record({
        api,
        method: init?.method ?? 'GET',
        url: typeof input === 'string' ? input : input instanceof URL ? input.href : input.url,
        body: typeof init?.body === 'string' ? init.body : null,
        status: response.status,
        bytes: new TextEncoder().encode(text).length,
        durationMs,
        queryCount: metrics.query_count ?? headerNumber(response, 'X-Query-Count'),
        queryTimeMs: metrics.query_time_ms ?? headerNumber(response, 'X-Query-Time-Ms'),
        batchload: api === 'graphql' ? (metrics.batchload ?? null) : null,
    });

    return response;
}
