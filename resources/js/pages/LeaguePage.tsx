import { useParams } from 'react-router';

export function LeaguePage() {
    const { slug } = useParams();

    return <h1 className="text-5xl">League: {slug}</h1>;
}
