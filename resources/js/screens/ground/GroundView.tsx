import { Link } from 'react-router';
import { Crest } from '@/components/Crest';
import type { GroundView as Ground } from './types';

export function GroundView({ ground }: { ground: Ground }) {
    const facts = [
        ground.capacity !== null ? `Holds ${ground.capacity.toLocaleString('en-GB')}` : null,
        ground.opened !== null ? `Opened ${ground.opened}` : null,
    ].filter(Boolean);
    const osm = `https://www.openstreetmap.org/?mlat=${ground.latitude}&mlon=${ground.longitude}#map=16/${ground.latitude}/${ground.longitude}`;

    return (
        <article className="flex flex-col gap-10">
            <header>
                <h1 className="text-5xl sm:text-6xl">{ground.name}</h1>
                <p className="mt-1 text-lg">{ground.address ?? ground.town}</p>
                {facts.length > 0 && <p className="mt-1 text-slate-soft">{facts.join('. ')}.</p>}
            </header>

            {ground.description && <p className="max-w-prose text-lg leading-relaxed">{ground.description}</p>}

            <section aria-labelledby="home-clubs-heading">
                <h2 id="home-clubs-heading" className="text-3xl">
                    {ground.clubs.length === 1 ? 'Home club' : 'Home clubs'}
                </h2>
                <ul className="mt-3 flex flex-wrap gap-6">
                    {ground.clubs.map((club) => (
                        <li key={club.slug}>
                            <Link to={`/clubs/${club.slug}`} className="flex items-center gap-3 text-slate no-underline hover:underline">
                                <Crest slug={club.slug} name={club.name} colour={club.colour} size={48} />
                                <span>
                                    <span className="block font-display text-2xl font-semibold">{club.name}</span>
                                    {club.nickname && <span className="text-sm text-slate-soft">{club.nickname}</span>}
                                </span>
                            </Link>
                        </li>
                    ))}
                </ul>
            </section>

            <section aria-labelledby="nearby-heading">
                <h2 id="nearby-heading" className="text-3xl">Nearest grounds by road</h2>
                <table className="mt-3 w-full text-left">
                    <caption className="sr-only">The nearest grounds to {ground.name}, by road distance</caption>
                    <thead className="border-b-2 border-slate font-display text-base">
                        <tr>
                            <th scope="col" className="py-2 font-semibold">Ground</th>
                            <th scope="col" className="py-2 text-right font-semibold">By road</th>
                            <th scope="col" className="hidden py-2 text-right font-semibold sm:table-cell">As the crow flies</th>
                        </tr>
                    </thead>
                    <tbody>
                        {ground.nearby.map((trip) => (
                            <tr key={trip.slug} className="border-b border-mist">
                                <td className="py-2 pr-4">
                                    <Link to={`/grounds/${trip.slug}`} className="text-slate">
                                        {trip.name}
                                    </Link>
                                    <span className="block text-xs text-slate-soft">
                                        {trip.town}
                                        {trip.clubs.length > 0 && `, home of ${trip.clubs.map((club) => club.name).join(' and ')}`}
                                    </span>
                                </td>
                                <td className="py-2 text-right font-display text-xl font-semibold whitespace-nowrap">{trip.roadKm} km</td>
                                <td className="hidden py-2 text-right whitespace-nowrap text-slate-soft sm:table-cell">{trip.straightKm} km</td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </section>

            <p className="text-sm">
                <a href={osm} className="text-pitch">
                    View {ground.name} on OpenStreetMap
                </a>
            </p>
        </article>
    );
}
