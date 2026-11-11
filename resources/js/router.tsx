import { createBrowserRouter } from 'react-router';
import { Layout } from './components/Layout';
import { ClubPage } from './pages/ClubPage';
import { GroundPage } from './pages/GroundPage';
import { HomePage } from './pages/HomePage';
import { LeaguePage } from './pages/LeaguePage';
import { LivePage } from './pages/LivePage';
import { NotFoundPage } from './pages/NotFoundPage';

export const router = createBrowserRouter([
    {
        element: <Layout />,
        children: [
            { index: true, element: <HomePage /> },
            { path: 'leagues/:slug', element: <LeaguePage /> },
            { path: 'clubs/:slug', element: <ClubPage /> },
            { path: 'grounds/:slug', element: <GroundPage /> },
            { path: 'live', element: <LivePage /> },
            { path: '*', element: <NotFoundPage /> },
        ],
    },
]);
