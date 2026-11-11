import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import { RouterProvider } from 'react-router';
import { Provider as GraphQLProvider } from 'urql';
import { graphqlClient } from './graphql/client';
import { router } from './router';

const root = document.getElementById('app');

if (root) {
    createRoot(root).render(
        <StrictMode>
            <GraphQLProvider value={graphqlClient}>
                <RouterProvider router={router} />
            </GraphQLProvider>
        </StrictMode>,
    );
}
