import { Client, cacheExchange, fetchExchange } from 'urql';

/**
 * urql client for the Lighthouse endpoint. The document cache keeps repeat
 * visits to a screen instant; the comparison panel records cache misses only.
 */
export const graphqlClient = new Client({
    url: '/graphql',
    exchanges: [cacheExchange, fetchExchange],
    preferGetMethod: false,
});
