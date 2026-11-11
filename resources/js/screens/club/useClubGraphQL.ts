import { useQuery } from 'urql';
import { ClubPageDocument } from '@/graphql/documents';
import { graphqlStatus } from '../shared/status';
import { tableRowFromGraphQL, type ScreenData } from '../shared/types';
import type { ClubView } from './types';

/** The club screen from GraphQL: one request for everything. */
export function useClubGraphQL(slug: string): ScreenData<ClubView> {
    const [{ data, fetching, error }] = useQuery({ query: ClubPageDocument, variables: { slug } });
    const club = data?.club;

    return graphqlStatus(
        fetching,
        error,
        club === undefined
            ? undefined
            : club && {
                  slug: club.slug,
                  name: club.name,
                  nickname: club.nickname ?? null,
                  colour: club.colour,
                  founded: club.founded ?? null,
                  ground: { ...club.ground, capacity: club.ground.capacity ?? null, opened: club.ground.opened ?? null },
                  history: club.seasons
                      .map((row) => ({
                          seasonSlug: row.season.slug,
                          seasonName: row.season.name,
                          leagueSlug: row.league.slug,
                          leagueName: row.league.name,
                          tier: row.league.tier,
                          position: row.position ?? null,
                      }))
                      .sort((a, b) => b.seasonSlug.localeCompare(a.seasonSlug)),
                  current: club.league && {
                      leagueSlug: club.league.league.slug,
                      leagueName: club.league.league.name,
                      seasonName: club.league.season.name,
                      table: club.league.table.map(tableRowFromGraphQL),
                  },
                  awayTrips: club.awayTrips.map((trip) => ({
                      groundSlug: trip.to.slug,
                      groundName: trip.to.name,
                      town: trip.to.town,
                      clubs: trip.to.clubs,
                      roadKm: trip.roadKm,
                  })),
              },
    );
}
