import { test, expect } from '@playwright/test';
import { loginToNsConseil } from './support/auth.js';

test.describe('Use Case: Migration Contacts Conversion', () => {
  test.beforeEach(async ({ page }) => {
    // Login using the existing auth function
    await loginToNsConseil(page);
  });

  test('Conversion Prospect vers Partenaire avec migration contacts', async ({ page }) => {
    // Étape 1: Créer un prospect avec des informations de contact
    await page.goto('/ns-conseil/prospects/create');
    
    // Remplir les informations de base du prospect
    await page.fill('input[name="nom"]', 'CSE Test Migration');
    await page.selectOption('select[name="type_pressenti"]', 'CSE');
    await page.fill('input[name="ville"]', 'Paris');
    await page.fill('input[name="code_postal"]', '75001');
    await page.fill('input[name="departement"]', '75');
    await page.fill('input[name="nb_salaries"]', '150');
    
    // Remplir les informations de contact qui seront migrées
    await page.fill('input[name="contact_principal_nom"]', 'Dupont');
    await page.fill('input[name="contact_principal_prenom"]', 'Jean');
    await page.fill('input[name="contact_principal_email"]', 'jean.dupont@test.com');
    await page.fill('input[name="contact_principal_telephone"]', '0123456789');
    await page.fill('input[name="contact_principal_fonction"]', 'Secrétaire CSE');
    
    // Sauvegarder le prospect
    await page.click('button[type="submit"]');
    await expect(page.locator('.filament-notifications')).toContainText('Créé avec succès');
    
    // Étape 2: Passer le prospect en statut QF (Qualifié)
    await page.click('button:has-text("Marquer QF")');
    await expect(page.locator('.filament-notifications')).toContainText('Statut mis à jour');
    
    // Étape 3: Convertir le prospect en partenaire
    await page.click('button:has-text("Convertir en Partenaire")');
    await expect(page.locator('.filament-notifications')).toContainText('Conversion réussie');
    
    // Étape 4: Vérifier que le partenaire a été créé
    await page.goto('/ns-conseil/partenaires');
    await expect(page.locator('h1')).toContainText('Partenaires');
    await expect(page.locator('table tbody tr')).toContainText('CSE Test Migration');
    
    // Étape 5: Accéder aux contacts du partenaire
    await page.click('table tbody tr:has-text("CSE Test Migration") button:has-text("Voir")');
    await page.click('button:has-text("Contacts")');
    
    // Étape 6: Vérifier que les contacts ont été migrés
    await expect(page.locator('table tbody tr')).toContainText('Dupont');
    await expect(page.locator('table tbody tr')).toContainText('Jean');
    await expect(page.locator('table tbody tr')).toContainText('jean.dupont@test.com');
    await expect(page.locator('table tbody tr')).toContainText('Secrétaire CSE');
    
    // Étape 7: Vérifier les détails du contact migré
    await page.click('table tbody tr:has-text("Dupont") button:has-text("Voir")');
    await expect(page.locator('text=Dupont')).toBeVisible();
    await expect(page.locator('text=Jean')).toBeVisible();
    await expect(page.locator('text=jean.dupont@test.com')).toBeVisible();
    await expect(page.locator('text=0123456789')).toBeVisible();
    await expect(page.locator('text=Secrétaire CSE')).toBeVisible();
  });

  test('Navigation Partenaires vers Contacts Partenaires', async ({ page }) => {
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
