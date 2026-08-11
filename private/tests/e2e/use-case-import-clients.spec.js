import { test, expect } from '@playwright/test';
import { loginToNsConseil } from './support/auth.js';

test.describe('Use Case: Import Clients Dolibarr', () => {
  test.beforeEach(async ({ page }) => {
    // Login using the existing auth function
    await loginToNsConseil(page);
  });

  test('Navigation pour import Clients Dolibarr', async ({ page }) => {
    // Étape 1: Accéder à la page Clients
    await page.goto('/ns-conseil/clients');
    await expect(page.locator('h1')).toContainText('Clients');

    // Étape 2: Vérifier que le bouton Import existe
    const importButton = page.locator('button').filter({ hasText: 'Importer' });
    await expect(importButton).toBeVisible();

    // Étape 3: Vérifier que le bouton Export existe
    const exportButton = page.locator('button').filter({ hasText: 'Exporter' });
    await expect(exportButton).toBeVisible();

    // Étape 4: Retourner à la page Clients
    await page.goto('/ns-conseil/clients');
    await expect(page.locator('h1')).toContainText('Clients');
  });
});
