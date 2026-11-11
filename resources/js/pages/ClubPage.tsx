import { useParams } from 'react-router';
import { useScreenLoad } from '@/api/recorder';
import { useSettings } from '@/api/settings';
import { ScreenState } from '@/components/ScreenState';
import { ClubView } from '@/screens/club/ClubView';
import { useClubGraphQL } from '@/screens/club/useClubGraphQL';
import { useClubRest } from '@/screens/club/useClubRest';

function ClubFromRest({ slug }: { slug: string }) {
    return <ScreenState state={useClubRest(slug)} what="club">{(club) => <ClubView club={club} />}</ScreenState>;
}

function ClubFromGraphQL({ slug }: { slug: string }) {
    return <ScreenState state={useClubGraphQL(slug)} what="club">{(club) => <ClubView club={club} />}</ScreenState>;
}

export function ClubPage() {
    const slug = useParams().slug ?? '';
    const { source, batchload } = useSettings();

    useScreenLoad(source, `Club: ${slug}`, batchload);

    return source === 'rest' ? <ClubFromRest key={slug} slug={slug} /> : <ClubFromGraphQL key={`${slug}-${batchload}`} slug={slug} />;
}
