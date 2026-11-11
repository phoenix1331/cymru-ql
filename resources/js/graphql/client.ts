import { Client, cacheExchange, fetchExchange } from 'urql';
import { instrumentedFetch } from '@/api/instrumentedFetch';
import { settings } from '@/api/settings';

/**
 * urql client for the Lighthouse endpoint. Every request goes through
 * instrumentedFetch so the comparison panel sees it, and carries the
 * X-Batchload header from the batch loading switch.
 */
export const graphqlClient = new Client({
    url: '/graphql',
    exchanges: [cacheExchange, fetchExchange],
    preferGetMethod: false,
    fetch: (input, init) => instrumentedFetch('graphql', input, init),
    fetchOptions: () => ({
        headers: { 'X-Batchload': settings.get().batchload ? 'on' : 'off' },
    }),
});
