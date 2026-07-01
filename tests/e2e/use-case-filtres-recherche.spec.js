import { test, expect } from '@playwright/test';
import { loginToNsConseil } from './support/auth.js';

test.describe('Use Case: Filtres et Recherche', () => {
  test.beforeEach(async ({ page }) => {
    // Login using the existing auth function
    await loginToNsConseil(page);
  });

  test('Navigation entre les pages principales', async ({ page }) => {
    // Étape 1: Accéder à la page Prospects
    await page.goto('/ns-conseil/prospects');
    await expect(page.locator('h1')).toContainText('Prospects');

    // Étape 2: Accéder à la page Clients
    await page.goto('/ns-conseil/clients');
    await expect(page.locator('h1')).toContainText('Clients');

    // Étape 3: Accéder à la page Partenaires
    await page.goto('/ns-conseil/partenaires');
    await expect(page.locator('h1')).toContainText('Partenaires');

    // Étape 4: Accéder aux Opportunités
    await page.goto('/ns-conseil/opportunites');
    await expect(page.locator('h1')).toContainText('Opportunités');
  });
});
