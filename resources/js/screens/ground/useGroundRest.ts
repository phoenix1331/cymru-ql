import { useQueries, useQuery } from '@tanstack/react-query';
import { restGet } from '@/api/rest';
import type { Envelope, RestClub, RestDistance, RestGround } from '@/api/restTypes';
import { restStatus } from '../shared/status';
import type { ScreenData } from '../shared/types';
import { NEARBY_LIMIT, type GroundView } from './types';

/**
 * The ground screen from REST: the ground and its nearest grounds, then each
 * home club (for its colours) and each nearby ground (for its clubs).
 */
export function useGroundRest(slug: string): ScreenData<GroundView> {
    const ground = useQuery({ queryKey: ['ground', slug], queryFn: () => restGet<Envelope<RestGround>>(`grounds/${slug}`) });
    const nearby = useQuery({
        queryKey: ['nearby', slug],
        queryFn: () => restGet<Envelope<RestDistance[]>>(`grounds/${slug}/nearby`, { limit: NEARBY_LIMIT }),
    });

    const homeClubs = useQueries({
        queries: (ground.data?.data.clubs ?? []).map((club) => ({
            queryKey: ['club', club.slug],
            queryFn: () => restGet<Envelope<RestClub>>(`clubs/${club.slug}`),
        })),
    });
    const nearbyGrounds = useQueries({
        queries: (nearby.data?.data ?? []).map((trip) => ({
            queryKey: ['ground', trip.to.slug],
            queryFn: () => restGet<Envelope<RestGround>>(`grounds/${trip.to.slug}`),
        })),
    });

    return restStatus([ground, nearby, ...homeClubs, ...nearbyGrounds], () => {
        const g = ground.data!.data;

        return {
            slug: g.slug,
            name: g.name,
            town: g.town,
            address: g.address,
            capacity: g.capacity,
            opened: g.opened,
            description: g.description,
            latitude: g.latitude,
            longitude: g.longitude,
            clubs: homeClubs.map((query) => {
                const club = query.data!.data;
                return { slug: club.slug, name: club.name, colour: club.colour, nickname: club.nickname };
            }),
            nearby: nearby.data!.data.map((trip, index) => ({
                slug: trip.to.slug,
                name: trip.to.name,
                town: trip.to.town,
                clubs: (nearbyGrounds[index]?.data?.data.clubs ?? []).map(({ slug: clubSlug, name }) => ({ slug: clubSlug, name })),
                roadKm: trip.road_km,
                straightKm: trip.straight_km,
            })),
        };
    });
}
