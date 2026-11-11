import type { CombinedError } from 'urql';
import { ApiError } from '@/api/rest';
import type { ScreenData } from './types';

/** Maps REST query state to the shared screen state. */
export function restStatus<T>(
    queries: { isPending: boolean; error: Error | null }[],
    build: () => T,
): ScreenData<T> {
    const failed = queries.find((query) => query.error);

    if (failed?.error instanceof ApiError && failed.error.status === 404) {
        return { status: 'not-found' };
    }

    if (failed?.error) {
        return { status: 'error', message: failed.error.message };
    }

    return queries.some((query) => query.isPending) ? { status: 'loading' } : { status: 'ready', data: build() };
}

/** Maps urql query state to the shared screen state. */
export function graphqlStatus<T>(fetching: boolean, error: CombinedError | undefined, data: T | null | undefined): ScreenData<T> {
    if (error) {
        const notFound = error.graphQLErrors.some((graphQLError) => graphQLError.message.endsWith('not found.'));
        return notFound ? { status: 'not-found' } : { status: 'error', message: error.message };
    }

    if (fetching || data === undefined) {
        return { status: 'loading' };
    }

    return data === null ? { status: 'not-found' } : { status: 'ready', data };
}
