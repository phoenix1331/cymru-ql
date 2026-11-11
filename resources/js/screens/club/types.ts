import type { ClubRef, TableRowView } from '../shared/types';

export interface ClubView {
    slug: string;
    name: string;
    nickname: string | null;
    colour: string;
    founded: number | null;
    ground: { slug: string; name: string; town: string; capacity: number | null; opened: number | null };
    history: { seasonSlug: string; seasonName: string; leagueSlug: string; leagueName: string; tier: number; position: number | null }[];
    current: { leagueSlug: string; leagueName: string; seasonName: string; table: TableRowView[] } | null;
    awayTrips: { groundSlug: string; groundName: string; town: string; clubs: ClubRef[]; roadKm: number }[];
}
