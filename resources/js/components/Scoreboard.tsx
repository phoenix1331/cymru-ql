import type { LoadSummary } from '@/api/recorder';
import { formatBytes, formatMs } from '@/lib/format';

interface Row {
    label: string;
    value: (summary: LoadSummary) => number;
    format: (value: number) => string;
}

const ROWS: Row[] = [
    { label: 'Requests', value: (s) => s.requests, format: String },
    { label: 'Data received', value: (s) => s.bytes, format: formatBytes },
    { label: 'Time', value: (s) => s.durationMs, format: formatMs },
    { label: 'Database queries', value: (s) => s.queryCount, format: String },
];

function Cell({ summary, other, row }: { summary: LoadSummary | null; other: LoadSummary | null; row: Row }) {
    if (!summary) {
        return <td className="px-3 py-2 text-right font-display text-2xl text-white/35">-</td>;
    }

    const value = row.value(summary);
    const better = other !== null && value < row.value(other);

    return (
        <td className="px-3 py-2 text-right">
            <span className={`whitespace-nowrap font-display text-2xl font-semibold ${better ? 'text-white' : 'text-white/70'}`}>{row.format(value)}</span>
            {better && <span className="sr-only"> (lower)</span>}
        </td>
    );
}

/**
 * The matchday scoreboard: what the latest REST and GraphQL loads of this
 * screen each cost. The lower figure on each row is shown brighter.
 */
export function Scoreboard({ rest, graphql }: { rest: LoadSummary | null; graphql: LoadSummary | null }) {
    return (
        <table className="w-full overflow-hidden rounded-md bg-slate text-white">
            <caption className="sr-only">Cost of loading this screen with each API</caption>
            <thead>
                <tr>
                    <th scope="col" className="px-3 pt-3 pb-1 text-left text-sm font-normal text-white/60">
                        This screen
                    </th>
                    <th scope="col" className="border-t-4 border-rest px-3 pt-2 pb-1 text-right font-display text-xl font-semibold">
                        REST
                    </th>
                    <th scope="col" className="border-t-4 border-graphql px-3 pt-2 pb-1 text-right font-display text-xl font-semibold">
                        GraphQL
                    </th>
                </tr>
            </thead>
            <tbody>
                {ROWS.map((row) => (
                    <tr key={row.label} className="border-t border-white/10">
                        <th scope="row" className="px-3 py-2 text-left text-sm font-normal text-white/80">
                            {row.label}
                        </th>
                        <Cell summary={rest} other={graphql} row={row} />
                        <Cell summary={graphql} other={rest} row={row} />
                    </tr>
                ))}
            </tbody>
        </table>
    );
}
