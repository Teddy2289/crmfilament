import { test, expect } from '@playwright/test';
import { loginToNsConseil } from './support/auth.js';

test.describe('Base de Connaissances', () => {
  test.beforeEach(async ({ page }) => {
    await loginToNsConseil(page);
  });

  test('Accès page base de connaissances', async ({ page }) => {
    // Étape 1: Accéder à la page Base de connaissances
    await page.goto('/ns-conseil/document-knowledges');
    await expect(page.locator('h1')).toContainText('Document Knowledges');
  });
});
