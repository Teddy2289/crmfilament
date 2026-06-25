import { defineConfig, devices } from '@playwright/test';
import { laravelE2eEnv } from './tests/e2e/support/laravel-env.js';

const port = process.env.PLAYWRIGHT_PORT ?? '8001';
const baseURL = process.env.PLAYWRIGHT_BASE_URL ?? process.env.APP_URL ?? `http://127.0.0.1:${port}`;
const shouldStartServer = process.env.PLAYWRIGHT_START_SERVER !== '0' && !process.env.PLAYWRIGHT_BASE_URL;
const reuseExistingServer = process.env.PLAYWRIGHT_REUSE_SERVER === '1';

export default defineConfig({
    testDir: './tests/e2e',
    testMatch: '**/*.spec.js',
    globalSetup: './tests/e2e/support/global-setup.js',
    timeout: 60_000,
    expect: {
        timeout: 15_000,
    },
    fullyParallel: false,
    forbidOnly: Boolean(process.env.CI),
    retries: process.env.CI ? 2 : 0,
    workers: process.env.CI ? 1 : undefined,
    outputDir: 'test-results/e2e',
    reporter: [
        ['list'],
        ['html', { open: 'never', outputFolder: 'playwright-report' }],
    ],
    use: {
        baseURL,
        locale: 'fr-FR',
        screenshot: 'on',
        timezoneId: 'Europe/Paris',
        trace: 'on-first-retry',
        video: 'retain-on-failure',
    },
    webServer: shouldStartServer
        ? {
            command: `node tests/e2e/support/serve-laravel.js ${port} ${baseURL}`,
            env: {
                ...laravelE2eEnv,
                APP_URL: baseURL,
            },
            reuseExistingServer,
            timeout: 120_000,
            url: baseURL,
        }
        : undefined,
    projects: [
        {
            name: 'chromium',
            use: { ...devices['Desktop Chrome'] },
        },
    ],
});
