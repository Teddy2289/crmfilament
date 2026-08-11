import { test, expect } from '@playwright/test';
import { loginToNsConseil } from './support/auth.js';

test.describe('Use Case: Parcours prospection CSE', () => {
  test.beforeEach(async ({ page }) => {
    // Login using the existing auth function
    await loginToNsConseil(page);
  });

  test('Navigation dans le workflow de prospection', async ({ page }) => {
    // Étape 1: Accéder à la page Prospects
    await page.goto('/ns-conseil/prospects');
    await expect(page.locator('h1')).toContainText('Prospects');

    // Étape 2: Accéder au flux de travail téléphonique
    await page.goto('/ns-conseil/phoning-workflow');
    await expect(page.locator('h1')).toContainText('Flux de travail');

    // Étape 3: Accéder aux Opportunités
    await page.goto('/ns-conseil/opportunites');
    await expect(page.locator('h1')).toContainText('Opportunités');

    // Étape 4: Accéder aux Partenaires
    await page.goto('/ns-conseil/partenaires');
    await expect(page.locator('h1')).toContainText('Partenaires');
  });
});
