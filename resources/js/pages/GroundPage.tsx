import { useParams } from 'react-router';

export function GroundPage() {
    const { slug } = useParams();

    return <h1 className="text-5xl">Ground: {slug}</h1>;
}
