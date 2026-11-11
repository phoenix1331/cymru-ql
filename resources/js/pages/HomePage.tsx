import { Link } from 'react-router';
import { LEAGUES } from '@/leagues';

export function HomePage() {
    return (
        <div className="max-w-2xl">
            <h1 className="text-5xl sm:text-6xl">Same screen, two APIs</h1>
            <p className="mt-4 text-lg leading-relaxed">
                Every page here can load its data from a REST API or from GraphQL. Switch between them and see what each
                one cost: how many requests, how much data, how long it took and how many database queries ran.
            </p>
            <p className="mt-4 leading-relaxed">
                48 clubs across the top two tiers of Welsh football. Pick a league to start.
            </p>
            <ul className="mt-6 flex flex-col gap-2">
                {LEAGUES.map((league) => (
                    <li key={league.slug}>
                        <Link to={`/leagues/${league.slug}`} className="font-display text-2xl font-semibold text-pitch">
                            {league.name}
                        </Link>
                    </li>
                ))}
            </ul>
        </div>
    );
}
