import { useQuery } from 'urql';
import { GroundPageDocument } from '@/graphql/documents';
import { graphqlStatus } from '../shared/status';
import type { ScreenData } from '../shared/types';
import type { GroundView } from './types';

/** The ground screen from GraphQL: one request. */
export function useGroundGraphQL(slug: string): ScreenData<GroundView> {
    const [{ data, fetching, error }] = useQuery({ query: GroundPageDocument, variables: { slug } });
    const ground = data?.ground;

    return graphqlStatus(
        fetching,
        error,
        ground === undefined
            ? undefined
            : ground && {
                  ...ground,
                  address: ground.address ?? null,
                  capacity: ground.capacity ?? null,
                  opened: ground.opened ?? null,
                  description: ground.description ?? null,
                  clubs: ground.clubs.map((club) => ({ ...club, nickname: club.nickname ?? null })),
                  nearby: ground.nearby.map((trip) => ({ ...trip.to, roadKm: trip.roadKm, straightKm: trip.straightKm })),
              },
    );
}
