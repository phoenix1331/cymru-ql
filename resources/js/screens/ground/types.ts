import type { ClubRef } from '../shared/types';

export interface GroundView {
    slug: string;
    name: string;
    town: string;
    address: string | null;
    capacity: number | null;
    opened: number | null;
    description: string | null;
    latitude: number;
    longitude: number;
    clubs: (ClubRef & { colour: string; nickname: string | null })[];
    nearby: { slug: string; name: string; town: string; clubs: ClubRef[]; roadKm: number; straightKm: number }[];
}

export const NEARBY_LIMIT = 8;
