import { useParams, useSearchParams } from 'react-router';
import { useScreenLoad } from '@/api/recorder';
import { useSettings } from '@/api/settings';
import { ScreenState } from '@/components/ScreenState';
import { LeagueView } from '@/screens/league/LeagueView';
import { useLeagueGraphQL } from '@/screens/league/useLeagueGraphQL';
import { useLeagueRest } from '@/screens/league/useLeagueRest';

interface Props {
    slug: string;
    season: string | undefined;
}

function LeagueFromRest({ slug, season }: Props) {
    return <ScreenState state={useLeagueRest(slug, season)} what="league">{(league) => <LeagueView league={league} />}</ScreenState>;
}

function LeagueFromGraphQL({ slug, season }: Props) {
    return <ScreenState state={useLeagueGraphQL(slug, season)} what="league">{(league) => <LeagueView league={league} />}</ScreenState>;
}

export function LeaguePage() {
    const slug = useParams().slug ?? '';
    const season = useSearchParams()[0].get('season') ?? undefined;
    const { source, batchload } = useSettings();

    useScreenLoad(source, `League: ${slug} ${season ?? 'current'}`, batchload);

    return source === 'rest' ? <LeagueFromRest slug={slug} season={season} /> : <LeagueFromGraphQL key={String(batchload)} slug={slug} season={season} />;
}
