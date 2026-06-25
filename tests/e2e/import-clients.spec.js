import { test, expect } from '@playwright/test';
import { loginToNsConseil } from './support/auth.js';

test.describe('Import Clients', () => {
  test.beforeEach(async ({ page }) => {
    // Login using the existing auth function
    await loginToNsConseil(page);
  });

  test('Accès à la page Clients', async ({ page }) => {
    await page.goto('/ns-conseil/clients');
    
    // Vérifier que la page est chargée
    await expect(page.locator('h1')).toContainText('Clients');
  });

  test('Bouton Import visible', async ({ page }) => {
    await page.goto('/ns-conseil/clients');
    
    // Vérifier que le bouton d'import existe
    const importButton = page.locator('button').filter({ hasText: 'Importer' });
    await expect(importButton).toBeVisible();
  });

  test('Bouton Export visible', async ({ page }) => {
    await page.goto('/ns-conseil/clients');
    
    // Vérifier que le bouton d'export existe
    const exportButton = page.locator('button').filter({ hasText: 'Exporter' });
    await expect(exportButton).toBeVisible();
  });
});
