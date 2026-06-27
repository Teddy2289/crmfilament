import { execFileSync } from 'node:child_process';
import { expect, test } from '@playwright/test';
import { loginToNsConseil } from './support/auth.js';
import { laravelE2eEnv } from './support/laravel-env.js';

function setupFieldVisibilityScenario() {
    const output = execFileSync('php', ['tests/e2e/support/setup-prospect-field-visibility.php'], {
        cwd: process.cwd(),
        env: laravelE2eEnv,
        encoding: 'utf8',
    });

    return JSON.parse(output);
}

test.describe('Droits par champ', () => {
    test('masque les champs prospects sans droit show pour un role selectif', async ({ page }) => {
        test.setTimeout(90_000);

        const scenario = setupFieldVisibilityScenario();

        expect(scenario.checks).toEqual({
            password_ok: true,
            panel_ok: true,
        });

        await loginToNsConseil(page, scenario.user);

        await page.goto('/ns-conseil/prospects');
        await expect(page.getByRole('heading', { name: /prospects/i })).toBeVisible();
        await expect(page.locator('body')).toContainText(scenario.prospect.name);
        await expect(page.locator('body')).toContainText(scenario.prospect.phone);
        await expect(page.locator('body')).not.toContainText(scenario.prospect.email);

        await page.goto(`/ns-conseil/prospects/${scenario.prospect.id}`, { waitUntil: 'commit' });
        await expect(page.getByRole('heading', { name: /prospect/i })).toBeVisible();
        await expect(page.locator('body')).toContainText(scenario.prospect.name);
        await expect(page.locator('body')).toContainText(scenario.prospect.phone);
        await expect(page.locator('body')).not.toContainText(scenario.prospect.email);
    });
});
