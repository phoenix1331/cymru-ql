import type { ReactNode } from 'react';
import { Link } from 'react-router';
import type { ScreenData } from '@/screens/shared/types';

/** Loading, not found and error states shared by every screen. */
export function ScreenState<T>({ state, what, children }: { state: ScreenData<T>; what: string; children: (data: T) => ReactNode }) {
    switch (state.status) {
        case 'ready':
            return children(state.data);
        case 'loading':
            return (
                <p role="status" className="text-slate-soft">
                    Loading {what}…
                </p>
            );
        case 'not-found':
            return (
                <div>
                    <h1 className="text-5xl">No such {what}</h1>
                    <p className="mt-4">
                        Check the address, or <Link to="/" className="text-pitch">start from a league</Link>.
                    </p>
                </div>
            );
        case 'error':
            return (
                <div role="alert">
                    <h1 className="text-4xl">The {what} did not load</h1>
                    <p className="mt-4">{state.message} Reload the page to try again.</p>
                </div>
            );
    }
}
