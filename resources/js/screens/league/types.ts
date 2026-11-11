import type { TableRowView } from '../shared/types';

export interface LeagueView {
    slug: string;
    name: string;
    tier: number;
    region: string;
    season: { slug: string; name: string };
    seasons: { slug: string; name: string; isCurrent: boolean }[];
    table: TableRowView[];
}
