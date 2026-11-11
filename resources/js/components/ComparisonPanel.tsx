import { useScreenComparison } from '@/api/recorder';
import { ApiToggle } from './ApiToggle';
import { RequestLog } from './RequestLog';
import { Scoreboard } from './Scoreboard';

/** What the current screen cost to load, and how that compares across APIs. */
export function ComparisonPanel() {
    const { load, records, rest, graphql } = useScreenComparison();

    return (
        <aside aria-labelledby="comparison-heading" className="flex flex-col gap-5">
            <h2 id="comparison-heading" className="text-2xl">
                What this cost
            </h2>
            <ApiToggle />

            {load ? (
                <>
                    <Scoreboard rest={rest} graphql={graphql} />
                    {(rest === null || graphql === null) && (
                        <p className="text-sm text-slate-soft">
                            Switch API to load this screen the other way and fill in the other side.
                        </p>
                    )}
                    <section aria-labelledby="requests-heading">
                        <h3 id="requests-heading" className="mb-2 text-xl">
                            {records.length} {load.api === 'rest' ? 'REST' : 'GraphQL'} {records.length === 1 ? 'request' : 'requests'}
                        </h3>
                        <RequestLog records={records} />
                    </section>
                </>
            ) : (
                <p className="text-sm text-slate-soft">Open a league, club or ground to see what loading it costs.</p>
            )}
        </aside>
    );
}
