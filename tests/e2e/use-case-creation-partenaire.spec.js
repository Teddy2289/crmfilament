import { test, expect } from '@playwright/test';
import { loginToNsConseil } from './support/auth.js';

test.describe('Use Case: Navigation Partenaires', () => {
  test.beforeEach(async ({ page }) => {
    // Login using the existing auth function
    await loginToNsConseil(page);
  });

  test('Navigation et accès aux fonctionnalités Partenaires', async ({ page }) => {
    // Étape 1: Accéder à la page Partenaires
    await page.goto('/ns-conseil/partenaires');
    await expect(page.locator('h1')).toContainText('Partenaires');

    // Étape 2: Vérifier que la page contient les éléments attendus
    await expect(page.locator('body')).toBeVisible();

    // Étape 3: Retourner au tableau de bord
    await page.goto('/ns-conseil');
    await expect(page.locator('body')).toContainText(/tableau de bord|prospects|partenaires/i);

    // Étape 4: Revenir aux Partenaires
    await page.goto('/ns-conseil/partenaires');
    await expect(page.locator('h1')).toContainText('Partenaires');
  });
});
