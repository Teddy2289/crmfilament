import { test, expect } from '@playwright/test';
import { loginToNsConseil } from './support/auth.js';

test.describe('Use Case: Validation QF', () => {
  test.beforeEach(async ({ page }) => {
    // Login using the existing auth function
    await loginToNsConseil(page);
  });

  test('Navigation pour validation QF', async ({ page }) => {
    // Étape 1: Accéder à la page Prospects
    await page.goto('/ns-conseil/prospects');
    await expect(page.locator('h1')).toContainText('Prospects');

    // Étape 2: Accéder au flux de travail téléphonique
    await page.goto('/ns-conseil/phoning-workflow');
    await expect(page.locator('h1')).toContainText('Flux de travail');

    // Étape 3: Accéder aux Rendez-vous
    await page.goto('/ns-conseil/rendez-vous');
    await expect(page.locator('h1')).toContainText('Rendez');

    // Étape 4: Retourner aux Prospects
    await page.goto('/ns-conseil/prospects');
    await expect(page.locator('h1')).toContainText('Prospects');
  });
});
