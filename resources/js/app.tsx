import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import { QueryClientProvider } from '@tanstack/react-query';
import { RouterProvider } from 'react-router';
import { Provider as GraphQLProvider } from 'urql';
import { queryClient } from './api/rest';
import { graphqlClient } from './graphql/client';
import { router } from './router';

const root = document.getElementById('app');

if (root) {
    createRoot(root).render(
        <StrictMode>
            <QueryClientProvider client={queryClient}>
                <GraphQLProvider value={graphqlClient}>
                    <RouterProvider router={router} />
                </GraphQLProvider>
            </QueryClientProvider>
        </StrictMode>,
    );
}
