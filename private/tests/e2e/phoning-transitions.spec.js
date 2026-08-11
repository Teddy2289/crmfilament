import { test, expect } from '@playwright/test';
import { loginToNsConseil } from './support/auth.js';

test.describe('Transitions Enum Phoning', () => {
  test.beforeEach(async ({ page }) => {
    await loginToNsConseil(page);
  });

  test('Accès page prospects', async ({ page }) => {
    // Étape 1: Accéder à la page Prospects
    await page.goto('/ns-conseil/prospects');
    await expect(page.locator('h1')).toContainText('Prospects');
  });
});
