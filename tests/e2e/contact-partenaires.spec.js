import { test, expect } from '@playwright/test';
import { loginToNsConseil } from './support/auth.js';

test.describe('Contacts Partenaires', () => {
  test.beforeEach(async ({ page }) => {
    // Login using the existing auth function
    await loginToNsConseil(page);
  });

  test('Accès à la page Contacts Partenaires', async ({ page }) => {
    // Étape 1: Accéder à la page Contacts Partenaires
    await page.goto('/ns-conseil/contact-partenaires');
    await expect(page.locator('h1')).toContainText('Contacts');
  });

  test('Bouton Créer visible', async ({ page }) => {
    // Étape 1: Accéder à la page Contacts Partenaires
    await page.goto('/ns-conseil/contact-partenaires');
    await expect(page.locator('h1')).toContainText('Contacts');

    // Étape 2: Vérifier que le bouton Créer existe
    const createButton = page.locator('button').filter({ hasText: 'Créer' });
    await expect(createButton).toBeVisible();
  });

  test('Navigation depuis Partenaires', async ({ page }) => {
    // Étape 1: Accéder à la page Partenaires
    await page.goto('/ns-conseil/partenaires');
    await expect(page.locator('h1')).toContainText('Partenaires');

    // Étape 2: Accéder aux Contacts Partenaires
    await page.goto('/ns-conseil/contact-partenaires');
    await expect(page.locator('h1')).toContainText('Contacts');

    // Étape 3: Retourner aux Partenaires
    await page.goto('/ns-conseil/partenaires');
    await expect(page.locator('h1')).toContainText('Partenaires');
  });
});
