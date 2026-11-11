import { defineConfig, devices } from '@playwright/test';

const port = Number(process.env.PLAYWRIGHT_PORT ?? 8000);

/**
 * Browser smoke tests against the real app on php artisan serve, with the
 * database seeded by composer setup.
 */
export default defineConfig({
    testDir: 'e2e',
    fullyParallel: false,
    retries: process.env.CI ? 1 : 0,
    reporter: process.env.CI ? 'github' : 'list',
    use: {
        baseURL: `http://127.0.0.1:${port}`,
        trace: 'retain-on-failure',
    },
    projects: [{ name: 'chromium', use: { ...devices['Desktop Chrome'] } }],
    webServer: {
        command: `php artisan serve --port=${port}`,
        url: `http://127.0.0.1:${port}/up`,
        reuseExistingServer: !process.env.CI,
        timeout: 60_000,
    },
});
