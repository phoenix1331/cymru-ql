import { useEffect } from 'react';
import { useQuery } from 'urql';
import { useScreenLoad } from '@/api/recorder';
import { LiveMatchesDocument } from '@/graphql/documents';
import { LiveMatch } from '@/screens/live/LiveMatch';

const CHECK_EVERY_MS = 10_000;

export function LivePage() {
    useScreenLoad('graphql', 'Live matches');

    const [{ data, fetching, error }, refetch] = useQuery({ query: LiveMatchesDocument });
    const live = data?.live.data ?? [];
    const upcoming = data?.upcoming.data ?? [];
    const nextRound = upcoming[0]?.round;

    // Nothing in play: check now and then for the simulator kicking off a round.
    useEffect(() => {
        if (fetching || live.length > 0) {
            return;
        }

        const timer = window.setInterval(() => refetch({ requestPolicy: 'network-only' }), CHECK_EVERY_MS);
        return () => window.clearInterval(timer);
    }, [fetching, live.length, refetch]);

    return (
        <article className="flex flex-col gap-6">
            <header className="max-w-2xl">
                <h1 className="text-5xl sm:text-6xl">Live matches</h1>
                <p className="mt-2 text-lg leading-relaxed">
                    Scores update as they happen through a GraphQL subscription over WebSockets. There is no REST equivalent: a REST
                    client would have to keep asking.
                </p>
            </header>

            {error && <p role="alert">The live matches did not load. {error.message}</p>}

            {live.length > 0 ? (
                <ul className="grid gap-4 xl:grid-cols-2">
                    {live.map((fixture) => (
                        <LiveMatch key={fixture.id} fixture={fixture} />
                    ))}
                </ul>
            ) : (
                !fetching && (
                    <section aria-labelledby="no-live-heading" className="max-w-2xl">
                        <h2 id="no-live-heading" className="text-3xl">No matches in play</h2>
                        <p className="mt-2">
                            Start the simulator with <code className="rounded bg-white px-1 font-mono text-sm">php artisan cymru:simulate</code>{' '}
                            (it runs as part of <code className="rounded bg-white px-1 font-mono text-sm">composer dev</code>). This page
                            checks every few seconds and picks the matches up when they kick off.
                        </p>
                        {nextRound !== undefined && (
                            <>
                                <h3 className="mt-6 text-2xl">Next up: round {nextRound}</h3>
                                <ul className="mt-2">
                                    {upcoming
                                        .filter((fixture) => fixture.round === nextRound)
                                        .map((fixture) => (
                                            <li key={fixture.id} className="flex justify-between gap-4 border-b border-mist py-2">
                                                <span>
                                                    {fixture.homeClub.name} v {fixture.awayClub.name}
                                                </span>
                                                <span className="text-sm text-slate-soft">{fixture.league.name}</span>
                                            </li>
                                        ))}
                                </ul>
                            </>
                        )}
                    </section>
                )
            )}
        </article>
    );
}
