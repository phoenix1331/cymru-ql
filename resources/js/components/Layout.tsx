import { Link, NavLink, Outlet } from 'react-router';
import { LEAGUES } from '@/leagues';

export function Layout() {
    return (
        <div className="flex min-h-screen flex-col">
            <header className="border-b border-mist bg-white">
                <div className="mx-auto flex max-w-7xl flex-wrap items-baseline gap-x-8 gap-y-2 px-4 py-4 sm:px-6">
                    <Link to="/" className="font-display text-3xl font-bold text-slate no-underline">
                        Cymru<span className="text-pitch">QL</span>
                    </Link>
                    <nav aria-label="Leagues" className="flex flex-wrap gap-x-6 gap-y-1">
                        {LEAGUES.map((league) => (
                            <NavLink
                                key={league.slug}
                                to={`/leagues/${league.slug}`}
                                className={({ isActive }) =>
                                    `font-display text-lg font-semibold no-underline hover:text-pitch ${isActive ? 'text-pitch underline decoration-2' : 'text-slate-soft'}`
                                }
                            >
                                {league.name}
                            </NavLink>
                        ))}
                    </nav>
                </div>
            </header>

            <main className="mx-auto w-full max-w-7xl flex-1 px-4 py-8 sm:px-6">
                <Outlet />
            </main>

            <footer className="border-t border-mist">
                <p className="mx-auto max-w-7xl px-4 py-6 text-sm text-slate-soft sm:px-6">
                    A hobby project with no connection to the Football Association of Wales, the Cymru Leagues or any club.
                    Fixtures and results are simulated. Ground data from Over the Terraces, Wikidata and OpenStreetMap
                    contributors.
                </p>
            </footer>
        </div>
    );
}
