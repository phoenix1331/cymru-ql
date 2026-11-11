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

export const GroundPageDocument = graphql(`
    query GroundPage($slug: String!) {
        ground(slug: $slug) {
            slug
            name
            town
            address
            capacity
            opened
            description
            latitude
            longitude
            clubs {
                slug
                name
                nickname
                colour
            }
            nearby(limit: 8) {
                roadKm
                straightKm
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
`);

export const LiveMatchesDocument = graphql(`
    query LiveMatches {
        live: fixtures(status: Live, first: 24) {
            data {
                ...LiveFixtureFields
            }
        }
        upcoming: fixtures(status: Scheduled, first: 24) {
            data {
                id
                round
                league {
                    name
                }
                homeClub {
                    name
                }
                awayClub {
                    name
                }
            }
        }
    }

    fragment LiveFixtureFields on Fixture {
        id
        round
        kicksOffAt
        status
        minute
        homeScore
        awayScore
        league {
            slug
            name
        }
        homeClub {
            slug
            name
            colour
        }
        awayClub {
            slug
            name
            colour
        }
    }
`);

export const MatchUpdatedDocument = graphql(`
    subscription MatchUpdated($fixtureId: ID!) {
        matchUpdated(fixtureId: $fixtureId) {
            ...LiveFixtureFields
            events {
                id
                minute
                type
                description
            }
        }
    }
`);
