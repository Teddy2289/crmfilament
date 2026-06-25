import { test, expect } from '@playwright/test';
import { loginToNsConseil } from './support/auth.js';

test.describe('Rendez-vous', () => {
  test.beforeEach(async ({ page }) => {
    // Login using the existing auth function
    await loginToNsConseil(page);
  });

  test('Accès à la page Rendez-vous', async ({ page }) => {
    await page.goto('/ns-conseil/rendez-vous');
    
    // Vérifier que la page est chargée
    await expect(page.locator('h1')).toContainText('Rendez');
  });
});
