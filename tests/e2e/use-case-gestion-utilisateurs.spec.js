import { test, expect } from '@playwright/test';
import { loginToNsConseil } from './support/auth.js';

test.describe('Use Case: Gestion Utilisateurs', () => {
  test.beforeEach(async ({ page }) => {
    // Login using the existing auth function
    await loginToNsConseil(page);
  });

  test('Navigation et accès aux pages principales', async ({ page }) => {
    // Étape 1: Accéder au tableau de bord
    await page.goto('/ns-conseil');
    await expect(page.locator('body')).toBeVisible();

    // Étape 2: Accéder à la page Prospects
    await page.goto('/ns-conseil/prospects');
    await expect(page.locator('h1')).toContainText('Prospects');

    // Étape 3: Accéder à la page Clients
    await page.goto('/ns-conseil/clients');
    await expect(page.locator('h1')).toContainText('Clients');

    // Étape 4: Retourner au tableau de bord
    await page.goto('/ns-conseil');
    await expect(page.locator('body')).toBeVisible();
  });
});
