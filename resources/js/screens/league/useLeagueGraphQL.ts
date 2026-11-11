import { useQuery } from 'urql';
import { LeaguePageDocument } from '@/graphql/documents';
import { graphqlStatus } from '../shared/status';
import { tableRowFromGraphQL, type ScreenData } from '../shared/types';
import type { LeagueView } from './types';

/** The league screen from GraphQL: seasons and table in one request. */
export function useLeagueGraphQL(slug: string, season: string | undefined): ScreenData<LeagueView> {
    const [{ data, fetching, error }] = useQuery({ query: LeaguePageDocument, variables: { slug, season } });

    return graphqlStatus(
        fetching,
        error,
        data && {
            ...data.league.league,
            season: data.league.season,
            seasons: data.seasons,
            table: data.league.table.map(tableRowFromGraphQL),
        },
    );
}
