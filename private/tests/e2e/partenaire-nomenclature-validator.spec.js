import { test, expect } from '@playwright/test';
import { loginToNsConseil } from './support/auth.js';

test.describe('Validator Nomenclature Partenaire', () => {
  test.beforeEach(async ({ page }) => {
    await loginToNsConseil(page);
  });

  test('Accès formulaire création partenaire', async ({ page }) => {
    // Étape 1: Accéder à la page Partenaires
    await page.goto('/ns-conseil/partenaires/create');
    await expect(page.locator('h1')).toContainText('Créer Partenaire');
  });
});
