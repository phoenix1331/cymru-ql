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

export const ClubPageDocument = graphql(`
    query ClubPage($slug: String!) {
        club(slug: $slug) {
            slug
            name
            nickname
            colour
            founded
            ground {
                slug
                name
                town
                capacity
                opened
            }
            seasons {
                position
                season {
                    slug
                    name
                }
                league {
                    slug
                    name
                    tier
                }
            }
            league {
                league {
                    slug
                    name
                }
                season {
                    name
                }
                table {
                    ...TableRowFields
                }
            }
            awayTrips {
                roadKm
                to {
                    slug
                    name
                    town
                    clubs {
                        slug
                        name
                    }
                }
            }
        }
    }

    fragment TableRowFields on TableRow {
        position
        played
        won
        drawn
        lost
        goalDifference
        points
        form
        club {
            slug
            name
            colour
        }
    }
`);

export const LeaguePageDocument = graphql(`
    query LeaguePage($slug: String!, $season: String) {
        seasons {
            slug
            name
            isCurrent
        }
        league(slug: $slug, season: $season) {
            league {
                slug
                name
                tier
                region
            }
            season {
                slug
                name
            }
            table {
                ...TableRowFields
            }
        }
    }
`);
