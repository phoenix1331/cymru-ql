import { useSearchParams } from 'react-router';
import { LeagueTable } from '@/components/LeagueTable';
import type { LeagueView as League } from './types';

export function LeagueView({ league }: { league: League }) {
    const [, setSearchParams] = useSearchParams();
    const hasResults = league.table.some((row) => row.played !== null);

    return (
        <article className="flex flex-col gap-6">
            <header className="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h1 className="text-5xl sm:text-6xl">{league.name}</h1>
                    <p className="mt-1 text-slate-soft">
                        Tier {league.tier} of the Welsh pyramid, {league.region === 'National' ? 'national' : `${league.region.toLowerCase()} region`}.
                    </p>
                </div>
                <label className="flex flex-col gap-1 text-sm text-slate-soft">
                    Season
                    <select
                        value={league.season.slug}
                        onChange={(event) => setSearchParams({ season: event.target.value })}
                        className="rounded-md border border-mist bg-white px-3 py-2 font-display text-lg text-slate"
                    >
                        {league.seasons.map((season) => (
                            <option key={season.slug} value={season.slug}>
                                {season.name}
                                {season.isCurrent ? ' (current)' : ''}
                            </option>
                        ))}
                    </select>
                </label>
            </header>

            {league.table.length === 0 ? (
                <p>No clubs are recorded in this league for {league.season.name}.</p>
            ) : (
                <>
                    {!hasResults && (
                        <p className="max-w-prose text-slate-soft">
                            Only final positions are recorded for {league.season.name}, so there are no results to show. Clubs that have
                            since left the top two tiers are not listed, which leaves gaps in the positions.
                        </p>
                    )}
                    <LeagueTable rows={league.table} caption={`${league.name} ${league.season.name} table`} />
                </>
            )}
        </article>
    );
}
