import { test, expect } from '@playwright/test';
import { loginToNsConseil } from './support/auth.js';

test.describe('Use Case: Conversion Prospect en Client', () => {
  test.beforeEach(async ({ page }) => {
    // Login using the existing auth function
    await loginToNsConseil(page);
  });

  test('Navigation entre Prospects et Clients', async ({ page }) => {
    // Étape 1: Accéder à la page Prospects
    await page.goto('/ns-conseil/prospects');
    await expect(page.locator('h1')).toContainText('Prospects');

    // Étape 2: Accéder à la page Clients
    await page.goto('/ns-conseil/clients');
    await expect(page.locator('h1')).toContainText('Clients');

    // Étape 3: Retourner aux Prospects
    await page.goto('/ns-conseil/prospects');
    await expect(page.locator('h1')).toContainText('Prospects');

    // Étape 4: Vérifier que les pages sont accessibles
    await expect(page.locator('body')).toBeVisible();
  });
});
