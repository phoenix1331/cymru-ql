import { useSyncExternalStore } from 'react';

/** A minimal observable store for app-wide state shared outside React. */
export function createStore<T>(initial: T) {
    let state = initial;
    const listeners = new Set<() => void>();

    return {
        get: (): T => state,
        set(next: T | ((current: T) => T)): void {
            state = typeof next === 'function' ? (next as (current: T) => T)(state) : next;
            listeners.forEach((listener) => listener());
        },
        subscribe(listener: () => void): () => void {
            listeners.add(listener);
            return () => listeners.delete(listener);
        },
    };
}

export type Store<T> = ReturnType<typeof createStore<T>>;

export function useStore<T>(store: Store<T>): T {
    return useSyncExternalStore(store.subscribe, store.get, store.get);
}
