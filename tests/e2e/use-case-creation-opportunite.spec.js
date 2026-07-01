import { test, expect } from '@playwright/test';
import { loginToNsConseil } from './support/auth.js';

test.describe('Use Case: Création Opportunité', () => {
  test.beforeEach(async ({ page }) => {
    // Login using the existing auth function
    await loginToNsConseil(page);
  });

  test('Navigation et accès aux Opportunités', async ({ page }) => {
    // Étape 1: Accéder à la page Prospects
    await page.goto('/ns-conseil/prospects');
    await expect(page.locator('h1')).toContainText('Prospects');

    // Étape 2: Accéder aux Opportunités
    await page.goto('/ns-conseil/opportunites');
    await expect(page.locator('h1')).toContainText('Opportunités');

    // Étape 3: Vérifier que la page est chargée
    await expect(page.locator('body')).toBeVisible();

    // Étape 4: Retourner aux Prospects
    await page.goto('/ns-conseil/prospects');
    await expect(page.locator('h1')).toContainText('Prospects');
  });
});
