import { useQueries, useQuery } from '@tanstack/react-query';
import { restGet } from '@/api/rest';
import type { Envelope, Paginated, RestClub, RestClubSeason, RestDistance, RestGround, RestTable } from '@/api/restTypes';
import { restStatus } from '../shared/status';
import { tableRowFromRest, type ScreenData } from '../shared/types';
import type { ClubView } from './types';

/**
 * The club screen from the REST API: the club and its seasons first, then
 * its ground, its league table and its away distances once those are known,
 * then each away ground to find out which clubs play there.
 */
export function useClubRest(slug: string): ScreenData<ClubView> {
    const club = useQuery({ queryKey: ['club', slug], queryFn: () => restGet<Envelope<RestClub>>(`clubs/${slug}`) });
    const seasons = useQuery({ queryKey: ['club-seasons', slug], queryFn: () => restGet<Envelope<RestClubSeason[]>>(`clubs/${slug}/seasons`) });

    const groundSlug = club.data?.data.ground?.slug;
    const current = seasons.data?.data[0];

    const ground = useQuery({
        queryKey: ['ground', groundSlug],
        queryFn: () => restGet<Envelope<RestGround>>(`grounds/${groundSlug}`),
        enabled: groundSlug !== undefined,
    });
    const table = useQuery({
        queryKey: ['table', current?.league.slug, current?.season.slug],
        queryFn: () => restGet<RestTable>(`leagues/${current?.league.slug}/table`, { season: current?.season.slug }),
        enabled: current !== undefined,
    });
    const distances = useQuery({
        queryKey: ['distances', groundSlug, current?.league.slug, current?.season.slug],
        queryFn: () =>
            restGet<Paginated<RestDistance>>(`grounds/${groundSlug}/distances`, { league: current?.league.slug, season: current?.season.slug }),
        enabled: groundSlug !== undefined && current !== undefined,
    });

    const awayGrounds = useQueries({
        queries: (distances.data?.data ?? []).map((trip) => ({
            queryKey: ['ground', trip.to.slug],
            queryFn: () => restGet<Envelope<RestGround>>(`grounds/${trip.to.slug}`),
        })),
    });

    const waitingOn = [
        club,
        seasons,
        ...(club.data ? [ground] : []),
        ...(seasons.data?.data.length ? [table, distances] : []),
        ...awayGrounds,
    ];

    return restStatus(waitingOn, () => {
        const c = club.data!.data;
        const g = ground.data!.data;

        return {
            slug: c.slug,
            name: c.name,
            nickname: c.nickname,
            colour: c.colour,
            founded: c.founded,
            ground: { slug: g.slug, name: g.name, town: g.town, capacity: g.capacity, opened: g.opened },
            history: seasons.data!.data.map((row) => ({
                seasonSlug: row.season.slug,
                seasonName: row.season.name,
                leagueSlug: row.league.slug,
                leagueName: row.league.name,
                tier: row.league.tier,
                position: row.position,
            })),
            current:
                current && table.data
                    ? {
                          leagueSlug: current.league.slug,
                          leagueName: current.league.name,
                          seasonName: current.season.name,
                          table: table.data.data.map(tableRowFromRest),
                      }
                    : null,
            awayTrips: (distances.data?.data ?? []).map((trip, index) => ({
                groundSlug: trip.to.slug,
                groundName: trip.to.name,
                town: trip.to.town,
                clubs: (awayGrounds[index]?.data?.data.clubs ?? []).map(({ slug, name }) => ({ slug, name })),
                roadKm: trip.road_km,
            })),
        };
    });
}
