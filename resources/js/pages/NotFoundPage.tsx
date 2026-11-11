import { Link } from 'react-router';

export function NotFoundPage() {
    return (
        <div className="max-w-xl">
            <h1 className="text-5xl">Page not found</h1>
            <p className="mt-4">
                There is nothing at this address. <Link to="/" className="text-pitch">Go to the start page</Link> and pick a league.
            </p>
        </div>
    );
}
