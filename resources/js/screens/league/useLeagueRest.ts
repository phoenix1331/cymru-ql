import { useQuery } from '@tanstack/react-query';
import { restGet } from '@/api/rest';
import type { Envelope, RestSeason, RestTable } from '@/api/restTypes';
import { restStatus } from '../shared/status';
import { tableRowFromRest, type ScreenData } from '../shared/types';
import type { LeagueView } from './types';

/** The league screen from REST: the seasons list and the table, in parallel. */
export function useLeagueRest(slug: string, season: string | undefined): ScreenData<LeagueView> {
    const seasons = useQuery({ queryKey: ['seasons'], queryFn: () => restGet<Envelope<RestSeason[]>>('seasons') });
    const table = useQuery({
        queryKey: ['table', slug, season],
        queryFn: () => restGet<RestTable>(`leagues/${slug}/table`, { season }),
    });

    return restStatus([seasons, table], () => {
        const { league, season: current } = table.data!.meta;

        return {
            slug: league.slug,
            name: league.name,
            tier: league.tier,
            region: league.region,
            season: { slug: current.slug, name: current.name },
            seasons: seasons.data!.data.map((s) => ({ slug: s.slug, name: s.name, isCurrent: s.is_current })),
            table: table.data!.data.map(tableRowFromRest),
        };
    });
}
