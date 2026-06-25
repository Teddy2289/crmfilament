import { test, expect } from '@playwright/test';
import { loginToNsConseil } from './support/auth.js';

test.describe('Campagne Phoning', () => {
  test.beforeEach(async ({ page }) => {
    // Login using the existing auth function
    await loginToNsConseil(page);
  });

  test('Accès à la page Campagne Phoning', async ({ page }) => {
    await page.goto('/ns-conseil/campagne-phonings');
    
    // Vérifier que la page est chargée
    await expect(page.locator('h1')).toContainText('Campagne');
  });

  test('Bouton Créer visible', async ({ page }) => {
    await page.goto('/ns-conseil/campagne-phonings');
    
    // Vérifier que le bouton Créer existe
    const createButton = page.locator('button').filter({ hasText: 'Créer' });
    await expect(createButton).toBeVisible();
  });
});
