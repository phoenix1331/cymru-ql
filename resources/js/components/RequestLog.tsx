import type { RequestRecord } from '@/api/recorder';
import { formatBytes, formatGraphQLBody, formatMs } from '@/lib/format';

function RequestDetail({ entry }: { entry: RequestRecord }) {
    if (entry.body === null) {
        return <pre className="overflow-x-auto p-3 font-mono text-xs leading-relaxed">{`${entry.method} ${entry.url}`}</pre>;
    }

    const { query, variables } = formatGraphQLBody(entry.body);

    return (
        <div className="overflow-x-auto p-3 font-mono text-xs leading-relaxed">
            <pre>{`${entry.method} ${entry.url}\n\n${query}`}</pre>
            {variables && <pre className="mt-3 text-slate-soft">{`Variables\n${variables}`}</pre>}
        </div>
    );
}

/** Every request the current screen load made, with the raw request on demand. */
export function RequestLog({ records }: { records: RequestRecord[] }) {
    if (records.length === 0) {
        return <p className="text-sm text-slate-soft">Requests appear here as the screen loads.</p>;
    }

    return (
        <ol className="flex flex-col gap-2">
            {records.map((entry) => (
                <li key={entry.id} className="rounded-md border border-mist bg-white">
                    <details>
                        <summary className={`cursor-pointer list-none border-l-4 px-3 py-2 ${entry.api === 'rest' ? 'border-rest' : 'border-graphql'}`}>
                            <span className="block truncate font-mono text-xs">
                                {entry.method} {entry.url.replace(window.location.origin, '')}
                            </span>
                            <span className="mt-1 flex flex-wrap gap-x-3 text-xs text-slate-soft">
                                <span className={entry.status >= 400 ? 'text-graphql' : undefined}>{entry.status}</span>
                                <span>{formatBytes(entry.bytes)}</span>
                                <span>{formatMs(entry.durationMs)}</span>
                                {entry.queryCount !== null && <span>{entry.queryCount} {entry.queryCount === 1 ? 'query' : 'queries'}</span>}
                                {entry.batchload === false && <span className="text-graphql">batch loading off</span>}
                            </span>
                        </summary>
                        <div className="border-t border-mist">
                            <RequestDetail entry={entry} />
                        </div>
                    </details>
                </li>
            ))}
        </ol>
    );
}
