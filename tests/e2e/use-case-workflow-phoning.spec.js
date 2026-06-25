import { test, expect } from '@playwright/test';
import { loginToNsConseil } from './support/auth.js';

test.describe('Use Case: Workflow Phoning', () => {
  test.beforeEach(async ({ page }) => {
    // Login using the existing auth function
    await loginToNsConseil(page);
  });

  test('Accès et navigation dans le Workflow Phoning', async ({ page }) => {
    // Étape 1: Accéder au Workflow Phoning
    await page.goto('/ns-conseil/phoning-workflow');
    await expect(page.locator('h1')).toContainText('Workflow');

    // Étape 2: Vérifier que la page est chargée
    await expect(page.locator('body')).toBeVisible();

    // Étape 3: Retourner à la page Prospects
    await page.goto('/ns-conseil/prospects');
    await expect(page.locator('h1')).toContainText('Prospects');

    // Étape 4: Revenir au Workflow Phoning
    await page.goto('/ns-conseil/phoning-workflow');
    await expect(page.locator('h1')).toContainText('Workflow');
  });
});
