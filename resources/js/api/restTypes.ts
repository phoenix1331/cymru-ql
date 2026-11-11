/** Response shapes of the REST API (app/Http/Resources). */

export interface Envelope<T> {
    data: T;
}

export interface Paginated<T> extends Envelope<T[]> {
    meta: { current_page: number; last_page: number; per_page: number; total: number };
}

export interface Reference {
    slug: string;
    name: string;
}

export interface RestClub {
    slug: string;
    name: string;
    nickname: string | null;
    colour: string;
    founded: number | null;
    ground?: Reference;
    links: { self: string; seasons: string; ground?: string };
}

export interface RestClubSeason {
    season: Reference;
    league: Reference & { tier: number };
    position: number | null;
}

export interface RestGround {
    slug: string;
    name: string;
    town: string;
    latitude: number;
    longitude: number;
    capacity: number | null;
    opened: number | null;
    address: string | null;
    description: string | null;
    clubs?: (Reference & { link: string })[];
}

export interface RestLeague {
    slug: string;
    name: string;
    tier: number;
    region: string;
}

export interface RestSeason {
    slug: string;
    name: string;
    starts_on: string;
    ends_on: string;
    is_current: boolean;
}

export interface RestTableRow {
    position: number;
    club: Reference & { colour: string; link: string };
    played: number | null;
    won: number | null;
    drawn: number | null;
    lost: number | null;
    goals_for: number | null;
    goals_against: number | null;
    goal_difference: number | null;
    points: number | null;
    form: string[] | null;
}

export interface RestTable extends Envelope<RestTableRow[]> {
    meta: { league: RestLeague; season: RestSeason; basis: 'results' | 'final_positions' };
}

export interface RestDistance {
    from?: Reference;
    to: Reference & { town: string; latitude: number; longitude: number; link: string };
    road_km: number;
    straight_km: number;
}

export interface RestFixture {
    id: number;
    round: number;
    kicks_off_at: string;
    status: 'scheduled' | 'live' | 'finished';
    minute: number | null;
    home: Reference;
    away: Reference;
    score: { home: number; away: number } | null;
    ground: Reference;
}
