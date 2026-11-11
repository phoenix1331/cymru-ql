import { useParams } from 'react-router';
import { useScreenLoad } from '@/api/recorder';
import { useSettings } from '@/api/settings';
import { ScreenState } from '@/components/ScreenState';
import { GroundView } from '@/screens/ground/GroundView';
import { useGroundGraphQL } from '@/screens/ground/useGroundGraphQL';
import { useGroundRest } from '@/screens/ground/useGroundRest';

function GroundFromRest({ slug }: { slug: string }) {
    return <ScreenState state={useGroundRest(slug)} what="ground">{(ground) => <GroundView ground={ground} />}</ScreenState>;
}

function GroundFromGraphQL({ slug }: { slug: string }) {
    return <ScreenState state={useGroundGraphQL(slug)} what="ground">{(ground) => <GroundView ground={ground} />}</ScreenState>;
}

export function GroundPage() {
    const slug = useParams().slug ?? '';
    const { source, batchload } = useSettings();

    useScreenLoad(source, `Ground: ${slug}`, batchload);

    return source === 'rest' ? <GroundFromRest key={slug} slug={slug} /> : <GroundFromGraphQL key={`${slug}-${batchload}`} slug={slug} />;
}
