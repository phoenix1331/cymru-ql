import { Link } from 'react-router';
import { Crest } from '@/components/Crest';
import { LeagueTable } from '@/components/LeagueTable';
import type { ClubView as Club } from './types';

function ordinal(position: number): string {
    const suffix = position % 100 >= 11 && position % 100 <= 13 ? 'th' : (['th', 'st', 'nd', 'rd'][position % 10] ?? 'th');
    return `${position}${suffix}`;
}

export function ClubView({ club }: { club: Club }) {
    const totalKm = club.awayTrips.reduce((total, trip) => total + trip.roadKm, 0);
    const longest = club.awayTrips.at(-1);

    return (
        <article className="flex flex-col gap-10">
            <header className="flex items-center gap-5">
                <Crest slug={club.slug} name={club.name} colour={club.colour} size={88} />
                <div>
                    <h1 className="text-5xl sm:text-6xl">{club.name}</h1>
                    <p className="mt-1 text-slate-soft">
                        {[club.nickname, club.founded ? `Founded ${club.founded}` : null].filter(Boolean).join('. ')}
                    </p>
                </div>
            </header>

            <section aria-labelledby="ground-heading">
                <h2 id="ground-heading" className="text-3xl">Home ground</h2>
                <p className="mt-2 text-lg">
                    <Link to={`/grounds/${club.ground.slug}`} className="font-bold text-pitch">
                        {club.ground.name}
                    </Link>
                    , {club.ground.town}.
                    {club.ground.capacity !== null && ` Holds ${club.ground.capacity.toLocaleString('en-GB')}.`}
                    {club.ground.opened !== null && ` Opened ${club.ground.opened}.`}
                </p>
            </section>

            {club.current && (
                <section aria-labelledby="table-heading">
                    <h2 id="table-heading" className="text-3xl">
                        <Link to={`/leagues/${club.current.leagueSlug}`} className="text-slate">
                            {club.current.leagueName}
                        </Link>{' '}
                        {club.current.seasonName}
                    </h2>
                    <div className="mt-3">
                        <LeagueTable rows={club.current.table} highlight={club.slug} caption={`${club.current.leagueName} table`} />
                    </div>
                </section>
            )}

            {club.awayTrips.length > 0 && (
                <section aria-labelledby="trips-heading">
                    <h2 id="trips-heading" className="text-3xl">Away trips</h2>
                    <p className="mt-2 max-w-prose">
                        {club.awayTrips.length} away grounds this season, {Math.round(totalKm).toLocaleString('en-GB')} km by road one way.
                        {longest && ` The longest trip is ${longest.roadKm} km to ${longest.groundName}.`}
                    </p>
                    <ol className="mt-4 grid gap-x-8 sm:grid-cols-2">
                        {club.awayTrips.map((trip) => (
                            <li key={trip.groundSlug} className="flex items-baseline justify-between gap-4 border-b border-mist py-2">
                                <span>
                                    <Link to={`/grounds/${trip.groundSlug}`} className="text-slate">
                                        {trip.groundName}
                                    </Link>
                                    <span className="block text-xs text-slate-soft">{trip.clubs.map((c) => c.name).join(', ') || trip.town}</span>
                                </span>
                                <span className="font-display text-xl font-semibold">{trip.roadKm} km</span>
                            </li>
                        ))}
                    </ol>
                </section>
            )}

            <section aria-labelledby="history-heading">
                <h2 id="history-heading" className="text-3xl">League history</h2>
                <ul className="mt-2">
                    {club.history.map((row) => (
                        <li key={row.seasonSlug} className="flex gap-4 border-b border-mist py-2">
                            <span className="w-16 font-display text-lg">{row.seasonName}</span>
                            <Link to={`/leagues/${row.leagueSlug}`} className="text-slate">
                                {row.leagueName}
                            </Link>
                            <span className="ml-auto text-slate-soft">{row.position === null ? 'In progress' : ordinal(row.position)}</span>
                        </li>
                    ))}
                </ul>
            </section>
        </article>
    );
}
