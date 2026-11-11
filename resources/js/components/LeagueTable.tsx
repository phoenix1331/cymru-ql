import { Link } from 'react-router';
import type { TableRowView } from '@/screens/shared/types';
import { Crest } from './Crest';

const FORM_STYLE: Record<string, string> = {
    W: 'bg-pitch text-white',
    D: 'bg-mist text-slate',
    L: 'bg-slate text-white',
};

function stat(value: number | null): string {
    return value === null ? '' : String(value);
}

/** A league table; stats columns are hidden when only final positions are known. */
export function LeagueTable({ rows, highlight, caption }: { rows: TableRowView[]; highlight?: string; caption: string }) {
    const hasResults = rows.some((row) => row.played !== null);

    return (
        <div className="overflow-x-auto">
            <table className="w-full text-left text-sm">
                <caption className="sr-only">{caption}</caption>
                <thead className="border-b-2 border-slate font-display text-base">
                    <tr>
                        <th scope="col" className="w-10 py-2 pr-2 text-right font-semibold">
                            <abbr title="Position">Pos</abbr>
                        </th>
                        <th scope="col" className="py-2 pl-2 font-semibold">Club</th>
                        {hasResults && (
                            <>
                                {['Played', 'Won', 'Drawn', 'Lost', 'Goal difference', 'Points'].map((label) => (
                                    <th key={label} scope="col" className="px-2 py-2 text-right font-semibold">
                                        <abbr title={label}>{label === 'Goal difference' ? 'GD' : label === 'Points' ? 'Pts' : label[0]}</abbr>
                                    </th>
                                ))}
                                <th scope="col" className="hidden py-2 pl-3 font-semibold sm:table-cell">Form</th>
                            </>
                        )}
                    </tr>
                </thead>
                <tbody>
                    {rows.map((row) => (
                        <tr
                            key={row.club.slug}
                            aria-current={row.club.slug === highlight ? 'true' : undefined}
                            className={`border-b border-mist ${row.club.slug === highlight ? 'bg-white font-bold' : ''}`}
                        >
                            <td className="py-2 pr-2 text-right font-display text-lg">{row.position}</td>
                            <td className="py-2 pl-2">
                                <Link to={`/clubs/${row.club.slug}`} className="flex items-center gap-2 text-slate no-underline hover:underline">
                                    <Crest slug={row.club.slug} name={row.club.name} colour={row.club.colour} size={24} />
                                    {row.club.name}
                                </Link>
                            </td>
                            {hasResults && (
                                <>
                                    {[row.played, row.won, row.drawn, row.lost].map((value, index) => (
                                        <td key={index} className="px-2 py-2 text-right">{stat(value)}</td>
                                    ))}
                                    <td className="px-2 py-2 text-right">
                                        {row.goalDifference !== null && row.goalDifference > 0 ? `+${row.goalDifference}` : stat(row.goalDifference)}
                                    </td>
                                    <td className="px-2 py-2 text-right font-display text-lg font-semibold">{stat(row.points)}</td>
                                    <td className="hidden py-2 pl-3 sm:table-cell">
                                        <span className="flex gap-1" aria-label={`Form, oldest first: ${(row.form ?? []).join(' ')}`}>
                                            {(row.form ?? []).map((result, index) => (
                                                <span key={index} aria-hidden="true" className={`grid size-5 place-items-center rounded-sm text-xs font-bold ${FORM_STYLE[result] ?? ''}`}>
                                                    {result}
                                                </span>
                                            ))}
                                        </span>
                                    </td>
                                </>
                            )}
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}
