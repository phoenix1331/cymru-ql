import { useParams } from 'react-router';

export function ClubPage() {
    const { slug } = useParams();

    return <h1 className="text-5xl">Club: {slug}</h1>;
}
