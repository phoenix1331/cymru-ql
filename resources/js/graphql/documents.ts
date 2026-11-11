import { graphql } from './generated';

/**
 * Every GraphQL document the app sends. Kept in one place so the comparison
 * panel and GraphiQL examples can show exactly what each screen asks for.
 */

export const LeaguesDocument = graphql(`
    query Leagues {
        leagues {
            slug
            name
            tier
            region
        }
    }
`);
