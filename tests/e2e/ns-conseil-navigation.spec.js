import { expect, test } from '@playwright/test';
import { loginToNsConseil } from './support/auth.js';

test.describe('Navigation NS Conseil', () => {
    test('ouvre la liste des prospects apres connexion', async ({ page }) => {
        await loginToNsConseil(page);

        await page.goto('/ns-conseil/prospects');

        await expect(page).toHaveURL(/\/ns-conseil\/prospects$/);
        await expect(page.getByRole('heading', { name: /prospects/i })).toBeVisible();
    });
});
