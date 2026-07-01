import { test, expect } from '@playwright/test';
import { loginToNsConseil } from './support/auth.js';

test.describe('Use Case: Flux de travail téléphonique', () => {
  test.beforeEach(async ({ page }) => {
    await loginToNsConseil(page);
  });

  test('affiche le flux téléphonique et les actions de file', async ({ page }) => {
    await page.goto('/ns-conseil/phoning-workflow');

    await expect(page.locator('h1')).toContainText('Flux de travail téléphonique');
    await expect(page.getByRole('button', { name: 'Choisir une campagne' })).toBeVisible();
    await expect(page.getByRole('button', { name: 'Rafraîchir' }).first()).toBeVisible();
    await expect(page.getByRole('heading', { name: 'File vide', exact: true })).toBeVisible();
    await expect(page.getByText("Tous les contacts ont été traités ou aucun prospect n'est assigné")).toBeVisible();
    await expect(page.getByRole('link', { name: /Gérer la file/ })).toHaveAttribute(
      'href',
      /\/ns-conseil\/phoning-back-office$/,
    );
  });

  test('navigue entre prospects et flux téléphonique', async ({ page }) => {
    await page.goto('/ns-conseil/phoning-workflow');
    await expect(page.locator('h1')).toContainText('Flux de travail téléphonique');

    await page.goto('/ns-conseil/prospects');
    await expect(page.locator('h1')).toContainText('Prospects');

    await page.goto('/ns-conseil/phoning-workflow');
    await expect(page.locator('h1')).toContainText('Flux de travail téléphonique');
  });

  test('ouvre le back-office de gestion de file depuis l’état vide', async ({ page }) => {
    await page.goto('/ns-conseil/phoning-workflow');

    await page.getByRole('link', { name: /Gérer la file/ }).click();

    await expect(page).toHaveURL(/\/ns-conseil\/phoning-back-office$/);
    await expect(page.locator('h1')).toContainText(/File d'appels.*Back-office/);
  });
});
