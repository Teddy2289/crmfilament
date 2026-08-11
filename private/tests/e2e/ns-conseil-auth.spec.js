import { expect, test } from '@playwright/test';
import { loginToNsConseil } from './support/auth.js';

test.describe('Authentification NS Conseil', () => {
    test('redirige un visiteur anonyme vers la page de connexion', async ({ page }) => {
        await page.goto('/ns-conseil');

        await expect(page).toHaveURL(/\/ns-conseil\/login$/);
        await expect(page.getByText('NS CONSEIL').first()).toBeVisible();
        await expect(page.getByLabel(/adresse e-mail|email/i)).toBeVisible();
    });

    test('refuse des identifiants invalides', async ({ page }) => {
        await page.goto('/ns-conseil/login');
        await page.getByLabel(/adresse e-mail|email/i).fill('inconnu@example.test');
        await page.getByLabel(/mot de passe|password/i).fill('mauvais-mot-de-passe');
        await page.getByRole('button', { name: /se connecter|connexion/i }).click();

        await expect(page).toHaveURL(/\/ns-conseil\/login$/);
        await expect(page.getByText(/identifiants ne correspondent pas|credentials do not match/i)).toBeVisible();
    });

    test('connecte un utilisateur seede au tableau de bord', async ({ page }) => {
        await loginToNsConseil(page);

        await expect(page.locator('body')).toContainText(/tableau de bord|prospects|partenaires|workflow phoning/i);
    });
});
