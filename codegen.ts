import type { CodegenConfig } from '@graphql-codegen/cli';

/**
 * Typed GraphQL documents for the React app. The schema is printed from
 * Lighthouse by `npm run codegen`, so it always matches graphql/schema.graphql
 * plus everything Lighthouse adds (pagination types, registered enums).
 */
const config: CodegenConfig = {
    schema: 'resources/js/graphql/lighthouse-schema.graphql',
    documents: ['resources/js/**/*.{ts,tsx}', '!resources/js/graphql/generated/**'],
    ignoreNoDocuments: true,
    generates: {
        'resources/js/graphql/generated/': {
            preset: 'client',
            presetConfig: { fragmentMasking: false },
            config: {
                enumsAsTypes: true,
                useTypeImports: true,
                scalars: { Date: 'string', DateTimeTz: 'string' },
            },
        },
    },
};

export default config;
