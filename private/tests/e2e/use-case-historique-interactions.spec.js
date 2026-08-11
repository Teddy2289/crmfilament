import { test, expect } from '@playwright/test';
import { loginToNsConseil } from './support/auth.js';

test.describe('Use Case: Historique Interactions', () => {
  test.beforeEach(async ({ page }) => {
    // Login using the existing auth function
    await loginToNsConseil(page);
  });

  test('Consultation prospect enregistre interaction dans historique', async ({ page }) => {
    // Étape 1: Accéder à la page Prospects
    await page.goto('/ns-conseil/prospects');
    await expect(page.locator('h1')).toContainText('Prospects');

    // Étape 2: Cliquer sur le premier prospect pour consulter ses détails
    const firstRow = page.locator('table tbody tr').first();
    const prospectName = await firstRow.locator('td').first().textContent();
    await firstRow.click();

    // Étape 3: Vérifier que la page de détails s'affiche
    await expect(page.locator('h1')).toContainText(prospectName);

    // Étape 4: Naviguer vers l'onglet Historique Interactions
    await page.click('button:has-text("Historique Interactions")');

    // Étape 5: Vérifier que la section historique est visible
    await expect(page.locator('text=Historique Interactions')).toBeVisible();

    // Étape 6: Vérifier qu'une interaction de type "consultation" est enregistrée
    await expect(page.locator('table tbody tr')).toContainText('consultation');
    
    // Étape 7: Vérifier que l'utilisateur actuel est enregistré
    await expect(page.locator('table tbody tr')).toContainText('Consulté');
  });

  test('Consultation partenaire enregistre interaction dans historique', async ({ page }) => {
    // Étape 1: Accéder à la page Partenaires
    await page.goto('/ns-conseil/partenaires');
    await expect(page.locator('h1')).toContainText('Partenaires');

    // Étape 2: Cliquer sur le premier partenaire pour consulter ses détails
    const firstRow = page.locator('table tbody tr').first();
    const partnerName = await firstRow.locator('td').first().textContent();
    await firstRow.click();

    // Étape 3: Vérifier que la page de détails s'affiche
    await expect(page.locator('h1')).toContainText(partnerName);

    // Étape 4: Naviguer vers l'onglet Historique Interactions
    await page.click('button:has-text("Historique Interactions")');

    // Étape 5: Vérifier que la section historique est visible
    await expect(page.locator('text=Historique Interactions')).toBeVisible();

    // Étape 6: Vérifier qu'une interaction de type "consultation" est enregistrée
    await expect(page.locator('table tbody tr')).toContainText('consultation');
  });

  test('Navigation Prospects avec historique', async ({ page }) => {
    // Étape 1: Accéder à la page Prospects
    await page.goto('/ns-conseil/prospects');
    await expect(page.locator('h1')).toContainText('Prospects');

    // Étape 2: Accéder aux Partenaires
    await page.goto('/ns-conseil/partenaires');
    await expect(page.locator('h1')).toContainText('Partenaires');

    // Étape 3: Retourner aux Prospects
    await page.goto('/ns-conseil/prospects');
    await expect(page.locator('h1')).toContainText('Prospects');
  });

  test('Navigation Partenaires avec historique', async ({ page }) => {
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
