import { test, expect } from '@playwright/test';
import { loginToNsConseil } from './support/auth.js';

test.describe('Import Prospects', () => {
  test.beforeEach(async ({ page }) => {
    // Login using the existing auth function
    await loginToNsConseil(page);
  });

  test('Accès à la page Prospects', async ({ page }) => {
    await page.goto('/ns-conseil/prospects');
    
    // Vérifier que la page est chargée
    await expect(page.locator('h1')).toContainText('Prospects');
  });

  test('Accès au Workflow Phoning', async ({ page }) => {
    await page.goto('/ns-conseil/phoning-workflow');
    
    // Vérifier que la page est chargée
    await expect(page.locator('h1')).toContainText('Workflow');
  });
});
