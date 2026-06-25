import { test, expect } from '@playwright/test';
import { loginToNsConseil } from './support/auth.js';

test.describe('Use Case: Export de Données', () => {
  test.beforeEach(async ({ page }) => {
    // Login using the existing auth function
    await loginToNsConseil(page);
  });

  test('Accès aux pages avec fonctionnalités d\'export', async ({ page }) => {
    // Étape 1: Accéder à la page Clients
    await page.goto('/ns-conseil/clients');
    await expect(page.locator('h1')).toContainText('Clients');

    // Étape 2: Vérifier que le bouton Export existe
    const exportButton = page.locator('button').filter({ hasText: 'Exporter' });
    await expect(exportButton).toBeVisible();

    // Étape 3: Accéder à la page Prospects
    await page.goto('/ns-conseil/prospects');
    await expect(page.locator('h1')).toContainText('Prospects');
  });
});
