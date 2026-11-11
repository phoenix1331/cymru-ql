import { useLayoutEffect } from 'react';
import { createStore, useStore } from '@/lib/store';
import type { ApiSource } from './settings';

/** One HTTP request made while loading a screen, and what it cost. */
export interface RequestRecord {
    id: number;
    loadId: number;
    api: ApiSource;
    method: string;
    url: string;
    /** Request body as sent: the GraphQL query and variables, or null for GET. */
    body: string | null;
    status: number;
    /** Size of the response body in bytes, as received. */
    bytes: number;
    /** Round trip measured in the browser, in milliseconds. */
    durationMs: number;
    /** performance.now() when the request started and finished. */
    startedAt: number;
    endedAt: number;
    /** Database queries the server ran for this request. */
    queryCount: number | null;
    /** Time the server spent in the database, in milliseconds. */
    queryTimeMs: number | null;
    /** Whether GraphQL relations were batch loaded (GraphQL only). */
    batchload: boolean | null;
}

export interface ScreenLoad {
    id: number;
    api: ApiSource;
    /** What is being loaded, e.g. "Club: caernarfon-town". */
    label: string;
}

interface RecorderState {
    load: ScreenLoad | null;
    records: RequestRecord[];
    /** Latest load id for each screen and API, keyed "label|api". */
    latest: Record<string, number>;
}

const MAX_RECORDS = 200;

export const recorder = createStore<RecorderState>({ load: null, records: [], latest: {} });

let nextLoadId = 1;
let nextRecordId = 1;

export function screenKey(label: string, api: ApiSource): string {
    return `${label}|${api}`;
}

export function beginLoad(api: ApiSource, label: string): void {
    const id = nextLoadId++;
    recorder.set((state) => ({
        ...state,
        load: { id, api, label },
        latest: { ...state.latest, [screenKey(label, api)]: id },
    }));
}

/** The load a request belongs to, taken when it starts so late responses are not misfiled. */
export function currentLoadId(): number {
    return recorder.get().load?.id ?? 0;
}

export function record(entry: Omit<RequestRecord, 'id'>): void {
    recorder.set((state) => ({
        ...state,
        records: [...state.records, { ...entry, id: nextRecordId++ }].slice(-MAX_RECORDS),
    }));
}

/**
 * Starts a new screen load whenever the screen, the API or a variant of the
 * same screen (such as batch loading on or off) changes. A layout effect
 * runs before the data hooks' effects, so their requests land in this load.
 */
export function useScreenLoad(api: ApiSource, label: string, variant: unknown = null): void {
    useLayoutEffect(() => beginLoad(api, label), [api, label, variant]);
}

export interface LoadSummary {
    requests: number;
    bytes: number;
    /** Wall clock time from the first request starting to the last finishing. */
    durationMs: number;
    queryCount: number;
    /** False when any GraphQL request in the load ran without batch loading. */
    batchload: boolean | null;
}

export function summarise(records: RequestRecord[]): LoadSummary | null {
    if (records.length === 0) {
        return null;
    }

    return {
        requests: records.length,
        bytes: records.reduce((total, entry) => total + entry.bytes, 0),
        durationMs: Math.max(...records.map((entry) => entry.endedAt)) - Math.min(...records.map((entry) => entry.startedAt)),
        queryCount: records.reduce((total, entry) => total + (entry.queryCount ?? 0), 0),
        batchload: records.some((entry) => entry.batchload === false) ? false : (records[0]?.batchload ?? null),
    };
}

export interface ScreenComparison {
    load: ScreenLoad | null;
    records: RequestRecord[];
    rest: LoadSummary | null;
    graphql: LoadSummary | null;
}

/** The current screen load, plus the latest REST and GraphQL loads of the same screen. */
export function useScreenComparison(): ScreenComparison {
    const { load, records, latest } = useStore(recorder);

    const forLoad = (id: number | undefined) => (id === undefined ? [] : records.filter((entry) => entry.loadId === id));
    const latestFor = (api: ApiSource) => (load ? summarise(forLoad(latest[screenKey(load.label, api)])) : null);

    return {
        load,
        records: forLoad(load?.id),
        rest: latestFor('rest'),
        graphql: latestFor('graphql'),
    };
}
