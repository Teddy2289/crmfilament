import { test, expect } from '@playwright/test';
import { loginToNsConseil } from './support/auth.js';

test.describe('Use Case: Gestion Rendez-vous', () => {
  test.beforeEach(async ({ page }) => {
    // Login using the existing auth function
    await loginToNsConseil(page);
  });

  test('Navigation et accès aux Rendez-vous', async ({ page }) => {
    // Étape 1: Accéder à la page Prospects
    await page.goto('/ns-conseil/prospects');
    await expect(page.locator('h1')).toContainText('Prospects');

    // Étape 2: Accéder à la page Rendez-vous
    await page.goto('/ns-conseil/rendez-vous');
    await expect(page.locator('h1')).toContainText('Rendez');

    // Étape 3: Retourner aux Prospects
    await page.goto('/ns-conseil/prospects');
    await expect(page.locator('h1')).toContainText('Prospects');

    // Étape 4: Revenir aux Rendez-vous
    await page.goto('/ns-conseil/rendez-vous');
    await expect(page.locator('h1')).toContainText('Rendez');
  });
});
