import { test, expect } from '@playwright/test';
import { loginToNsConseil } from './support/auth.js';

test.describe('Use Case: Navigation CRM', () => {
  test.beforeEach(async ({ page }) => {
    // Login using the existing auth function
    await loginToNsConseil(page);
  });

  test('Navigation Prospects et Clients', async ({ page }) => {
    // Étape 1: Accéder à la page Prospects
    await page.goto('/ns-conseil/prospects');
    await expect(page.locator('h1')).toContainText('Prospects');

    // Étape 2: Accéder à la page Clients
    await page.goto('/ns-conseil/clients');
    await expect(page.locator('h1')).toContainText('Clients');
  });
});
