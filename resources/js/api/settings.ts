import { createStore, useStore } from '@/lib/store';

export type ApiSource = 'rest' | 'graphql';

export interface Settings {
    /** Which API the screens load their data from. */
    source: ApiSource;
    /** Whether GraphQL relations are batch loaded (off shows the N+1 cost). */
    batchload: boolean;
}

const STORAGE_KEY = 'cymruql.settings';
const DEFAULTS: Settings = { source: 'graphql', batchload: true };

function load(): Settings {
    try {
        const saved = JSON.parse(window.localStorage.getItem(STORAGE_KEY) ?? 'null') as Partial<Settings> | null;
        return { ...DEFAULTS, ...saved };
    } catch {
        return DEFAULTS;
    }
}

export const settings = createStore<Settings>(load());

settings.subscribe(() => {
    try {
        window.localStorage.setItem(STORAGE_KEY, JSON.stringify(settings.get()));
    } catch {
        // Private browsing or blocked storage: the setting lasts for this visit only.
    }
});

export function useSettings(): Settings {
    return useStore(settings);
}

export function updateSettings(change: Partial<Settings>): void {
    settings.set((current) => ({ ...current, ...change }));
}
