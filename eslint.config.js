import js from '@eslint/js';
import globals from 'globals';
import reactHooks from 'eslint-plugin-react-hooks';
import tseslint from 'typescript-eslint';

export default tseslint.config(
    { ignores: ['public/**', 'vendor/**', 'node_modules/**', 'resources/js/graphql/generated/**'] },
    {
        files: ['resources/js/**/*.{ts,tsx}'],
        extends: [js.configs.recommended, ...tseslint.configs.recommended, reactHooks.configs.flat.recommended],
        languageOptions: { globals: globals.browser },
    },
);
