import path from 'node:path';

export const playwrightDatabase = path.resolve(
    process.cwd(),
    process.env.PLAYWRIGHT_DB_DATABASE ?? 'database/playwright.sqlite',
);

export const laravelE2eEnv = {
    ...process.env,
    APP_ENV: process.env.PLAYWRIGHT_APP_ENV ?? 'testing',
    CACHE_STORE: process.env.PLAYWRIGHT_CACHE_STORE ?? 'array',
    DB_CONNECTION: process.env.PLAYWRIGHT_DB_CONNECTION ?? 'sqlite',
    DB_DATABASE: playwrightDatabase,
    MAIL_MAILER: process.env.PLAYWRIGHT_MAIL_MAILER ?? 'array',
    QUEUE_CONNECTION: process.env.PLAYWRIGHT_QUEUE_CONNECTION ?? 'sync',
    SESSION_DRIVER: process.env.PLAYWRIGHT_SESSION_DRIVER ?? 'file',
    TELESCOPE_ENABLED: process.env.PLAYWRIGHT_TELESCOPE_ENABLED ?? 'false',
};
