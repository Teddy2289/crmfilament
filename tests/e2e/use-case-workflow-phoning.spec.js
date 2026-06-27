import { test, expect } from '@playwright/test';
import { loginToNsConseil } from './support/auth.js';

test.describe('Use Case: Workflow Phoning', () => {
  test.beforeEach(async ({ page }) => {
    // Login using the existing auth function
    await loginToNsConseil(page);
  });

  test('Workflow phoning complet avec transitions statuts', async ({ page }) => {
    // Étape 1: Accéder au Workflow Phoning
    await page.goto('/ns-conseil/phoning-workflow');
    await expect(page.locator('h1')).toContainText('Workflow');

    // Étape 2: Charger la file d'appels
    await page.click('button:has-text("Charger la file")');
    await expect(page.locator('.filament-notifications')).toContainText('File chargée');

    // Étape 3: Vérifier que des prospects sont affichés
    await expect(page.locator('table tbody tr')).toHaveCount(1);

    // Étape 4: Sélectionner le premier prospect
    const firstRow = page.locator('table tbody tr').first();
    await firstRow.click();

    // Étape 5: Enregistrer un appel avec résultat
    await page.click('button:has-text("Enregistrer appel")');
    
    // Sélectionner un résultat d'appel (ex: "Contacté")
    await page.selectOption('select[name="resultat"]', 'contacte');
    
    // Ajouter une note
    await page.fill('textarea[name="notes"]', 'Appel réussi, intérêt manifesté');
    
    // Sauvegarder
    await page.click('button:has-text("Sauvegarder")');
    await expect(page.locator('.filament-notifications')).toContainText('Appel enregistré');

    // Étape 6: Vérifier que le statut du prospect a changé
    await page.goto('/ns-conseil/prospects');
    const updatedRow = page.locator('table tbody tr').first();
    await expect(updatedRow).toContainText('Contacté');

    // Étape 7: Retourner au workflow et passer au prospect suivant
    await page.goto('/ns-conseil/phoning-workflow');
    await page.click('button:has-text("Prospect suivant")');

    // Étape 8: Enregistrer un autre appel avec résultat différent
    await page.click('button:has-text("Enregistrer appel")');
    await page.selectOption('select[name="resultat"]', 'std_nr');
    await page.fill('textarea[name="notes"]', 'À rappeler ultérieurement');
    await page.click('button:has-text("Sauvegarder")');
    await expect(page.locator('.filament-notifications')).toContainText('Appel enregistré');

    // Étape 9: Vérifier la priorisation de la file
    await page.click('button:has-text("Prioriser file")');
    await expect(page.locator('.filament-notifications')).toContainText('File priorisée');
  });

  test('Accès et navigation dans le Workflow Phoning', async ({ page }) => {
    // Étape 1: Accéder au Workflow Phoning
    await page.goto('/ns-conseil/phoning-workflow');
    await expect(page.locator('h1')).toContainText('Workflow');

    // Étape 2: Vérifier que la page est chargée
    await expect(page.locator('body')).toBeVisible();

    // Étape 3: Retourner à la page Prospects
    await page.goto('/ns-conseil/prospects');
    await expect(page.locator('h1')).toContainText('Prospects');

    // Étape 4: Revenir au Workflow Phoning
    await page.goto('/ns-conseil/phoning-workflow');
    await expect(page.locator('h1')).toContainText('Workflow');
  });

  test('Filtrage et tri dans le Workflow Phoning', async ({ page }) => {
    // Étape 1: Accéder au Workflow Phoning
    await page.goto('/ns-conseil/phoning-workflow');
    await expect(page.locator('h1')).toContainText('Workflow');

    // Étape 2: Charger la file d'appels
    await page.click('button:has-text("Charger la file")');
    await expect(page.locator('.filament-notifications')).toContainText('File chargée');

    // Étape 3: Appliquer un filtre par statut
    await page.click('button:has-text("Filtrer")');
    await page.selectOption('select[name="statut"]', 'AC');
    await page.click('button:has-text("Appliquer")');

    // Étape 4: Vérifier que les résultats sont filtrés
    await expect(page.locator('table tbody tr')).toHaveCount(1);

    // Étape 5: Réinitialiser les filtres
    await page.click('button:has-text("Réinitialiser")');
    await expect(page.locator('table tbody tr')).toHaveCount(1);
  });
});
