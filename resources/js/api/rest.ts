import { QueryClient } from '@tanstack/react-query';
import { instrumentedFetch } from './instrumentedFetch';

export const queryClient = new QueryClient({
    defaultOptions: {
        // Every screen load goes to the network so the comparison panel always
        // shows real requests; in-flight duplicates are still shared.
        queries: { staleTime: 0, gcTime: 0, retry: false, refetchOnWindowFocus: false },
    },
});

export class ApiError extends Error {
    constructor(
        public readonly status: number,
        message: string,
    ) {
        super(message);
    }
}

/** GET a REST endpoint under /api and return its JSON, recording the cost. */
export async function restGet<T>(path: string, params: Record<string, string | number | undefined> = {}): Promise<T> {
    const query = new URLSearchParams(
        Object.entries(params).flatMap(([key, value]) => (value === undefined ? [] : [[key, String(value)]])),
    ).toString();

    const response = await instrumentedFetch('rest', `/api/${path}${query ? `?${query}` : ''}`, {
        headers: { Accept: 'application/json' },
    });

    if (!response.ok) {
        const body = (await response.json().catch(() => ({}))) as { message?: string };
        throw new ApiError(response.status, body.message ?? `Request failed with status ${response.status}.`);
    }

    return (await response.json()) as T;
}
