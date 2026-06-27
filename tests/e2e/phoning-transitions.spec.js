import { test, expect } from '@playwright/test';
import { loginToNsConseil } from './support/auth.js';

test.describe('Transitions Enum Phoning', () => {
  test.beforeEach(async ({ page }) => {
    await loginToNsConseil(page);
  });

  test('Transition statut prospect après appel avec resultat contacte', async ({ page }) => {
    // Étape 1: Créer un prospect
    await page.goto('/ns-conseil/prospects/create');
    await page.fill('input[name="nom"]', 'CSE Test Transition Contacte');
    await page.selectOption('select[name="type_pressenti"]', 'CSE');
    await page.fill('input[name="ville"]', 'Paris');
    await page.fill('input[name="code_postal"]', '75001');
    await page.fill('input[name="departement"]', '75');
    await page.fill('input[name="nb_salaries"]', '150');
    await page.click('button[type="submit"]');
    await expect(page.locator('.filament-notifications')).toContainText('Créé avec succès');

    // Étape 2: Accéder au workflow phoning
    await page.goto('/ns-conseil/phoning-workflow');
    await expect(page.locator('h1')).toContainText('Workflow');

    // Étape 3: Charger la file d'appels
    await page.click('button:has-text("Charger la file")');
    await expect(page.locator('.filament-notifications')).toContainText('File chargée');

    // Étape 4: Sélectionner le prospect créé
    await page.click('table tbody tr:has-text("CSE Test Transition Contacte")');

    // Étape 5: Enregistrer un appel avec résultat "contacte"
    await page.click('button:has-text("Enregistrer appel")');
    await page.selectOption('select[name="phoning_result"]', 'contacte');
    await page.fill('textarea[name="phoning_notes"]', 'Appel réussi, contact établi');
    await page.click('button:has-text("Sauvegarder")');
    await expect(page.locator('.filament-notifications')).toContainText('Appel enregistré');

    // Étape 6: Vérifier que le statut du prospect a changé vers "Contacté"
    await page.goto('/ns-conseil/prospects');
    const updatedRow = page.locator('table tbody tr:has-text("CSE Test Transition Contacte")');
    await expect(updatedRow).toContainText('Contacté');
  });

  test('Transition statut prospect après appel avec resultat std_nr', async ({ page }) => {
    // Étape 1: Créer un prospect
    await page.goto('/ns-conseil/prospects/create');
    await page.fill('input[name="nom"]', 'CSE Test Transition STD_NR');
    await page.selectOption('select[name="type_pressenti"]', 'CSE');
    await page.fill('input[name="ville"]', 'Lyon');
    await page.fill('input[name="code_postal"]', '69001');
    await page.fill('input[name="departement"]', '69');
    await page.fill('input[name="nb_salaries"]', '200');
    await page.click('button[type="submit"]');
    await expect(page.locator('.filament-notifications')).toContainText('Créé avec succès');

    // Étape 2: Accéder au workflow phoning
    await page.goto('/ns-conseil/phoning-workflow');
    await expect(page.locator('h1')).toContainText('Workflow');

    // Étape 3: Charger la file d'appels
    await page.click('button:has-text("Charger la file")');
    await expect(page.locator('.filament-notifications')).toContainText('File chargée');

    // Étape 4: Sélectionner le prospect créé
    await page.click('table tbody tr:has-text("CSE Test Transition STD_NR")');

    // Étape 5: Enregistrer un appel avec résultat "std_nr"
    await page.click('button:has-text("Enregistrer appel")');
    await page.selectOption('select[name="phoning_result"]', 'std_nr');
    await page.fill('textarea[name="phoning_notes"]', 'À rappeler ultérieurement');
    await page.click('button:has-text("Sauvegarder")');
    await expect(page.locator('.filament-notifications')).toContainText('Appel enregistré');

    // Étape 6: Vérifier que le statut du prospect a changé vers "STD NR"
    await page.goto('/ns-conseil/prospects');
    const updatedRow = page.locator('table tbody tr:has-text("CSE Test Transition STD_NR")');
    await expect(updatedRow).toContainText('STD NR');
  });

  test('Transition statut prospect après appel avec resultat ko', async ({ page }) => {
    // Étape 1: Créer un prospect
    await page.goto('/ns-conseil/prospects/create');
    await page.fill('input[name="nom"]', 'CSE Test Transition KO');
    await page.selectOption('select[name="type_pressenti"]', 'CSE');
    await page.fill('input[name="ville"]', 'Marseille');
    await page.fill('input[name="code_postal"]', '13001');
    await page.fill('input[name="departement"]', '13');
    await page.fill('input[name="nb_salaries"]', '100');
    await page.click('button[type="submit"]');
    await expect(page.locator('.filament-notifications')).toContainText('Créé avec succès');

    // Étape 2: Accéder au workflow phoning
    await page.goto('/ns-conseil/phoning-workflow');
    await expect(page.locator('h1')).toContainText('Workflow');

    // Étape 3: Charger la file d'appels
    await page.click('button:has-text("Charger la file")');
    await expect(page.locator('.filament-notifications')).toContainText('File chargée');

    // Étape 4: Sélectionner le prospect créé
    await page.click('table tbody tr:has-text("CSE Test Transition KO")');

    // Étape 5: Enregistrer un appel avec résultat "ko"
    await page.click('button:has-text("Enregistrer appel")');
    await page.selectOption('select[name="phoning_result"]', 'ko');
    await page.fill('textarea[name="phoning_notes"]', 'Pas d\'intérêt');
    await page.fill('input[name="motif_ko"]', 'Budget insuffisant');
    await page.click('button:has-text("Sauvegarder")');
    await expect(page.locator('.filament-notifications')).toContainText('Appel enregistré');

    // Étape 6: Vérifier que le statut du prospect a changé vers "KO"
    await page.goto('/ns-conseil/prospects');
    const updatedRow = page.locator('table tbody tr:has-text("CSE Test Transition KO")');
    await expect(updatedRow).toContainText('KO');
  });

  test('Modification appel met a jour statut prospect', async ({ page }) => {
    // Étape 1: Créer un prospect
    await page.goto('/ns-conseil/prospects/create');
    await page.fill('input[name="nom"]', 'CSE Test Modification Appel');
    await page.selectOption('select[name="type_pressenti"]', 'CSE');
    await page.fill('input[name="ville"]', 'Toulouse');
    await page.fill('input[name="code_postal"]', '31000');
    await page.fill('input[name="departement"]', '31');
    await page.fill('input[name="nb_salaries"]', '180');
    await page.click('button[type="submit"]');
    await expect(page.locator('.filament-notifications')).toContainText('Créé avec succès');

    // Étape 2: Créer un appel avec résultat "contacte"
    await page.goto('/ns-conseil/prospects');
    await page.click('table tbody tr:has-text("CSE Test Modification Appel") button:has-text("Voir")');
    await page.click('button:has-text("Appels")');
    await page.click('button:has-text("Créer")');
    await page.selectOption('select[name="phoning_result"]', 'contacte');
    await page.fill('textarea[name="phoning_notes"]', 'Premier appel');
    await page.click('button[type="submit"]');
    await expect(page.locator('.filament-notifications')).toContainText('Créé avec succès');

    // Étape 3: Vérifier que le statut est "Contacté"
    await page.goto('/ns-conseil/prospects');
    const row = page.locator('table tbody tr:has-text("CSE Test Modification Appel")');
    await expect(row).toContainText('Contacté');

    // Étape 4: Modifier l'appel pour changer le résultat
    await page.click('table tbody tr:has-text("CSE Test Modification Appel") button:has-text("Voir")');
    await page.click('button:has-text("Appels")');
    await page.click('table tbody tr button:has-text("Modifier")');
    await page.selectOption('select[name="phoning_result"]', 'ko');
    await page.fill('input[name="motif_ko"]', 'Nouveau motif');
    await page.click('button[type="submit"]');
    await expect(page.locator('.filament-notifications')).toContainText('Modifié avec succès');

    // Étape 5: Vérifier que le statut a changé vers "KO"
    await page.goto('/ns-conseil/prospects');
    const updatedRow = page.locator('table tbody tr:has-text("CSE Test Modification Appel")');
    await expect(updatedRow).toContainText('KO');
  });
});
