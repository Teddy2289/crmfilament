import { test, expect } from '@playwright/test';
import { loginToNsConseil } from './support/auth.js';

test.describe('Import Partenaires', () => {
  test.beforeEach(async ({ page }) => {
    // Login using the existing auth function
    await loginToNsConseil(page);
  });

  test('Accès à la page Partenaires', async ({ page }) => {
    await page.goto('/ns-conseil/partenaires');
    
    // Vérifier que la page est chargée
    await expect(page.locator('h1')).toContainText('Partenaires');
  });
});
