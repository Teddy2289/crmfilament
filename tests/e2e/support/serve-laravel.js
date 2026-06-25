import { spawn, spawnSync } from 'node:child_process';
import { laravelE2eEnv } from './laravel-env.js';

const port = process.argv[2] ?? '8001';
const appUrl = process.argv[3] ?? `http://127.0.0.1:${port}`;

const server = spawn('php', [
    'artisan',
    'serve',
    '--env=testing',
    '--host=127.0.0.1',
    `--port=${port}`,
], {
    cwd: process.cwd(),
    env: {
        ...laravelE2eEnv,
        APP_URL: appUrl,
    },
    stdio: 'inherit',
});

let shuttingDown = false;

function shutdown() {
    if (shuttingDown) {
        return;
    }

    shuttingDown = true;

    if (process.platform === 'win32' && server.pid) {
        spawnSync('taskkill', ['/pid', String(server.pid), '/T', '/F'], { stdio: 'ignore' });

        return;
    }

    if (! server.killed) {
        server.kill('SIGTERM');
    }
}

for (const signal of ['SIGINT', 'SIGTERM']) {
    process.on(signal, () => {
        shutdown();
        process.exit(0);
    });
}

process.on('exit', shutdown);

server.on('exit', (code) => {
    if (! shuttingDown) {
        process.exit(code ?? 0);
    }
});
