import { execFileSync } from 'node:child_process';
import { closeSync, mkdirSync, openSync, rmSync } from 'node:fs';
import path from 'node:path';
import { laravelE2eEnv, playwrightDatabase } from './laravel-env.js';

export default async function globalSetup() {
    if (process.env.PLAYWRIGHT_SKIP_DB_PREP === '1') {
        return;
    }

    if (laravelE2eEnv.DB_CONNECTION === 'sqlite' && playwrightDatabase !== ':memory:') {
        mkdirSync(path.dirname(playwrightDatabase), { recursive: true });
        try {
            rmSync(playwrightDatabase, { force: true });
            rmSync(`${playwrightDatabase}-journal`, { force: true });
        } catch (error) {
            // Ignore permission errors, file might be locked
            if (error.code !== 'EPERM' && error.code !== 'ENOENT') {
                throw error;
            }
        }
        closeSync(openSync(playwrightDatabase, 'a'));
    }

    execFileSync('php', ['artisan', 'migrate:fresh', '--seed', '--force'], {
        cwd: process.cwd(),
        env: laravelE2eEnv,
        stdio: 'inherit',
    });
}
