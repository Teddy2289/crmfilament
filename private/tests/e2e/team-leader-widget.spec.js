import { test, expect } from '@playwright/test';
import { loginToNsConseil } from './support/auth.js';

test.describe('Team Leader Stats Widget', () => {
  test.beforeEach(async ({ page }) => {
    await loginToNsConseil(page);
  });

  test('Accès dashboard', async ({ page }) => {
    // Étape 1: Accéder au dashboard
    await page.goto('/ns-conseil');
    await expect(page.locator('h1')).toContainText('Tableau de bord');
  });
});
