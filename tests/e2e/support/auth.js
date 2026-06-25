import { expect } from '@playwright/test';

export const e2eUser = {
    email: process.env.PLAYWRIGHT_E2E_EMAIL ?? 'a.florek@ns-conseil.com',
    password: process.env.PLAYWRIGHT_E2E_PASSWORD ?? 'changeme123',
};

export async function loginToNsConseil(page, user = e2eUser) {
    const loginTimeout = 30_000;

    await page.goto('/ns-conseil/login');
    await page.getByLabel(/adresse e-mail|email/i).fill(user.email);
    await page.getByLabel(/mot de passe|password/i).fill(user.password);
    await page.getByRole('button', { name: /se connecter|connexion/i }).click();

    const loginError = page.getByText(/identifiants ne correspondent pas|credentials do not match/i);

    try {
        await Promise.race([
            page.waitForURL((url) => url.pathname !== '/ns-conseil/login', {
                timeout: loginTimeout,
                waitUntil: 'commit',
            }),
            loginError.waitFor({ state: 'visible', timeout: loginTimeout }).then(() => {
                throw new Error(
                    `E2E login failed for ${user.email}. Seed the CRM users or set PLAYWRIGHT_E2E_EMAIL and PLAYWRIGHT_E2E_PASSWORD.`,
                );
            }),
        ]);
    } catch (error) {
        if (error instanceof Error && error.message.startsWith('E2E login failed')) {
            throw error;
        }

        throw new Error(
            `E2E login did not leave /ns-conseil/login for ${user.email}. Check the test user and CRM profile access.`,
            { cause: error },
        );
    }

    await expect(page).toHaveURL(/\/ns-conseil(?!\/login)(\/|$)/);
}
