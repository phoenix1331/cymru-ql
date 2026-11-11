import type { RestTableRow } from '@/api/restTypes';
import type { TableRowFieldsFragment } from '@/graphql/generated/graphql';

export interface ClubRef {
    slug: string;
    name: string;
    colour?: string;
}

export interface TableRowView {
    position: number;
    club: ClubRef & { colour: string };
    played: number | null;
    won: number | null;
    drawn: number | null;
    lost: number | null;
    goalDifference: number | null;
    points: number | null;
    form: string[] | null;
}

/** The state every screen's data hook returns, whichever API it uses. */
export type ScreenData<T> = { status: 'loading' } | { status: 'error'; message: string } | { status: 'not-found' } | { status: 'ready'; data: T };

export function tableRowFromRest(row: RestTableRow): TableRowView {
    return {
        position: row.position,
        club: { slug: row.club.slug, name: row.club.name, colour: row.club.colour },
        played: row.played,
        won: row.won,
        drawn: row.drawn,
        lost: row.lost,
        goalDifference: row.goal_difference,
        points: row.points,
        form: row.form,
    };
}

export function tableRowFromGraphQL(row: TableRowFieldsFragment): TableRowView {
    return { ...row, form: row.form ?? null };
}
