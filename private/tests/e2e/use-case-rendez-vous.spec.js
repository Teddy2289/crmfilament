import { test, expect } from '@playwright/test';
import { loginToNsConseil } from './support/auth.js';

test.describe('Use Case: Rendez-vous', () => {
  test.beforeEach(async ({ page }) => {
    // Login using the existing auth function
    await loginToNsConseil(page);
  });

  test('Accès et navigation dans les Rendez-vous', async ({ page }) => {
    // Étape 1: Accéder à la page Rendez-vous
    await page.goto('/ns-conseil/rendez-vous');
    await expect(page.locator('h1')).toContainText('Rendez');

    // Étape 2: Vérifier que la page est chargée
    await expect(page.locator('body')).toBeVisible();

    // Étape 3: Retourner à la page Prospects
    await page.goto('/ns-conseil/prospects');
    await expect(page.locator('h1')).toContainText('Prospects');

    // Étape 4: Revenir aux Rendez-vous
    await page.goto('/ns-conseil/rendez-vous');
    await expect(page.locator('h1')).toContainText('Rendez');
  });
});
