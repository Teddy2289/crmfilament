import { test, expect } from '@playwright/test';
import { loginToNsConseil } from './support/auth.js';

test.describe('Base de Connaissances', () => {
  test.setTimeout(120_000);

  test.beforeEach(async ({ page }) => {
    await loginToNsConseil(page, undefined, '/ns-conseil/document-knowledges');
  });

  test('Accès page base de connaissances', async ({ page }) => {
    await expect(page.locator('h1')).toContainText(/Base\s+de\s+connaissances/i);
  });
});
