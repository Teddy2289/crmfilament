import { test, expect } from '@playwright/test';
import { loginToNsConseil } from './support/auth.js';

test.describe('Use Case: Navigation CRM', () => {
  test.beforeEach(async ({ page }) => {
    // Login using the existing auth function
    await loginToNsConseil(page);
  });

  test('Navigation complète dans le CRM', async ({ page }) => {
    // Étape 1: Accéder au tableau de bord
    await page.goto('/ns-conseil');
    await expect(page.locator('body')).toContainText(/tableau de bord|prospects|partenaires/i);

    // Étape 2: Accéder à la page Prospects
    await page.goto('/ns-conseil/prospects');
    await expect(page.locator('h1')).toContainText('Prospects');

    // Étape 3: Accéder à la page Clients
    await page.goto('/ns-conseil/clients');
    await expect(page.locator('h1')).toContainText('Clients');

    // Étape 4: Accéder à la page Partenaires
    await page.goto('/ns-conseil/partenaires');
    await expect(page.locator('h1')).toContainText('Partenaires');

    // Étape 5: Accéder au Workflow Phoning
    await page.goto('/ns-conseil/phoning-workflow');
    await expect(page.locator('h1')).toContainText('Workflow');

    // Étape 6: Accéder aux Opportunités
    await page.goto('/ns-conseil/opportunites');
    await expect(page.locator('h1')).toContainText('Opportunites');

    // Étape 7: Accéder aux Rendez-vous
    await page.goto('/ns-conseil/rendez-vous');
    await expect(page.locator('h1')).toContainText('Rendez');
  });
});
