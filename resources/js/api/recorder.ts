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
}

const MAX_RECORDS = 200;

export const recorder = createStore<RecorderState>({ load: null, records: [] });

let nextLoadId = 1;
let nextRecordId = 1;

export function beginLoad(api: ApiSource, label: string): void {
    recorder.set((state) => ({ ...state, load: { id: nextLoadId++, api, label } }));
}

export function record(entry: Omit<RequestRecord, 'id' | 'loadId'>): void {
    recorder.set((state) => ({
        ...state,
        records: [...state.records, { ...entry, id: nextRecordId++, loadId: state.load?.id ?? 0 }].slice(-MAX_RECORDS),
    }));
}

/**
 * Starts a new screen load whenever the screen or API changes. A layout effect
 * runs before the data hooks' effects, so their requests land in this load.
 */
export function useScreenLoad(api: ApiSource, label: string): void {
    useLayoutEffect(() => beginLoad(api, label), [api, label]);
}

export function useCurrentLoad(): { load: ScreenLoad | null; records: RequestRecord[] } {
    const { load, records } = useStore(recorder);
    return { load, records: load ? records.filter((entry) => entry.loadId === load.id) : [] };
}
