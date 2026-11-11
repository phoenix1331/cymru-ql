import { Link } from 'react-router';
import { useSubscription } from 'urql';
import { Crest } from '@/components/Crest';
import { MatchUpdatedDocument } from '@/graphql/documents';
import type { LiveFixtureFieldsFragment } from '@/graphql/generated/graphql';

const NOTABLE = new Set(['Goal', 'PenaltyGoal', 'OwnGoal', 'RedCard', 'HalfTime', 'FullTime']);

function Side({ club, align }: { club: LiveFixtureFieldsFragment['homeClub']; align: 'left' | 'right' }) {
    return (
        <Link
            to={`/clubs/${club.slug}`}
            className={`flex min-w-0 items-center gap-2 text-slate no-underline hover:underline ${align === 'right' ? 'flex-row-reverse text-right' : ''}`}
        >
            <Crest slug={club.slug} name={club.name} colour={club.colour} size={32} />
            <span className="truncate font-display text-xl font-semibold">{club.name}</span>
        </Link>
    );
}

/** One fixture in play, kept up to date by the matchUpdated subscription. */
export function LiveMatch({ fixture }: { fixture: LiveFixtureFieldsFragment }) {
    const [{ data }] = useSubscription({ query: MatchUpdatedDocument, variables: { fixtureId: fixture.id } });
    // Events arrive with the first update, one match minute after loading.
    const match = data?.matchUpdated ?? fixture;
    const events = data?.matchUpdated?.events ?? [];
    const finished = match.status === 'Finished';
    const notable = events.filter((event) => NOTABLE.has(event.type)).slice(-4);

    return (
        <li className="rounded-md border border-mist bg-white p-4">
            <div className="flex items-center justify-between text-sm text-slate-soft">
                <span>{match.league.name}</span>
                <span aria-live="polite" className={finished ? '' : 'font-bold text-graphql'}>
                    {finished ? 'Full time' : `${match.minute ?? 0}'`}
                </span>
            </div>
            <div className="mt-2 grid grid-cols-[1fr_auto_1fr] items-center gap-3">
                <Side club={match.homeClub} align="left" />
                <span className="font-display text-4xl font-bold tabular-nums" aria-label={`Score ${match.homeScore ?? 0} to ${match.awayScore ?? 0}`}>
                    {match.homeScore ?? 0}-{match.awayScore ?? 0}
                </span>
                <Side club={match.awayClub} align="right" />
            </div>
            {notable.length > 0 && (
                <ol className="mt-3 border-t border-mist pt-2 text-sm text-slate-soft">
                    {notable.map((event) => (
                        <li key={event.id}>
                            {event.minute}' {event.description}
                        </li>
                    ))}
                </ol>
            )}
        </li>
    );
}
