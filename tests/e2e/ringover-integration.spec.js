import { expect, test } from '@playwright/test';
import { loginToNsConseil } from './support/auth.js';

test.describe('Intégration Ringover', () => {
    test.beforeEach(async ({ page }) => {
        await loginToNsConseil(page);
    });

    test('accède à la page Variables .env dans Super Admin', async ({ page }) => {
        await page.goto('/super-admin/env-settings');
        
        await expect(page.getByText('Variables .env')).toBeVisible();
        await expect(page.getByText('Services tiers')).toBeVisible();
    });

    test('affiche les variables Ringover dans le groupe Services tiers', async ({ page }) => {
        await page.goto('/super-admin/env-settings');
        
        // Attendre que le tableau se charge
        await page.waitForSelector('table');
        
        // Vérifier que les variables Ringover sont présentes
        await expect(page.getByText('Token API Ringover')).toBeVisible();
        await expect(page.getByText('Région Ringover')).toBeVisible();
        await expect(page.getByText('Monitoring Ringover activé')).toBeVisible();
        await expect(page.getByText('Secret Webhook Ringover')).toBeVisible();
        await expect(page.getByText('Timeout API Ringover')).toBeVisible();
    });

    test('modifie une variable Ringover et vérifie la synchronisation', async ({ page }) => {
        await page.goto('/super-admin/env-settings');
        
        // Cliquer sur le filtre Services tiers
        await page.getByRole('button', { name: 'Filtres' }).click();
        await page.getByRole('option', { name: 'Services tiers' }).click();
        
        // Attendre le filtrage
        await page.waitForTimeout(500);
        
        // Cliquer sur la ligne RINGOVER_REGION
        await page.getByText('Région Ringover').click();
        
        // Modifier la valeur
        const regionInput = page.getByLabel('Valeur');
        await regionInput.clear();
        await regionInput.fill('us');
        
        // Sauvegarder
        await page.getByRole('button', { name: 'Enregistrer' }).click();
        
        // Vérifier le message de succès
        await expect(page.getByText('enregistré avec succès', { exact: false })).toBeVisible();
        
        // Retourner à la liste
        await page.goto('/super-admin/env-settings');
        
        // Vérifier que la valeur a été modifiée
        await page.getByText('Région Ringover').click();
        await expect(page.getByLabel('Valeur')).toHaveValue('us');
        
        // Restaurer la valeur par défaut
        await regionInput.clear();
        await regionInput.fill('europe');
        await page.getByRole('button', { name: 'Enregistrer' }).click();
    });

    test('affiche les variables Reverb pour WebSocket', async ({ page }) => {
        await page.goto('/super-admin/env-settings');
        
        // Cliquer sur le filtre Services tiers
        await page.getByRole('button', { name: 'Filtres' }).click();
        await page.getByRole('option', { name: 'Services tiers' }).click();
        
        // Attendre le filtrage
        await page.waitForTimeout(500);
        
        // Vérifier que les variables Reverb sont présentes
        await expect(page.getByText('ID Application Reverb')).toBeVisible();
        await expect(page.getByText('Clé Application Reverb')).toBeVisible();
        await expect(page.getByText('Secret Application Reverb')).toBeVisible();
        await expect(page.getByText('Hôte Reverb')).toBeVisible();
        await expect(page.getByText('Port Reverb')).toBeVisible();
        await expect(page.getByText('Schéma Reverb')).toBeVisible();
    });

    test('masque les valeurs sensibles des variables Ringover', async ({ page }) => {
        await page.goto('/super-admin/env-settings');
        
        // Cliquer sur le filtre Services tiers
        await page.getByRole('button', { name: 'Filtres' }).click();
        await page.getByRole('option', { name: 'Services tiers' }).click();
        
        // Attendre le filtrage
        await page.waitForTimeout(500);
        
        // Cliquer sur Token API Ringover (sensible)
        await page.getByText('Token API Ringover').click();
        
        // Vérifier que le champ est de type password
        const tokenInput = page.getByLabel('Valeur');
        const inputType = await tokenInput.evaluate(el => el.type);
        expect(inputType).toBe('password');
    });

    test('filtre les variables par groupe', async ({ page }) => {
        await page.goto('/super-admin/env-settings');
        
        // Cliquer sur le filtre Groupe
        await page.getByRole('button', { name: 'Filtres' }).click();
        await page.getByRole('option', { name: 'Groupe' }).click();
        
        // Sélectionner Services tiers
        await page.getByRole('option', { name: 'Services tiers' }).click();
        
        // Attendre le filtrage
        await page.waitForTimeout(500);
        
        // Vérifier que seules les variables Services tiers sont affichées
        const rows = page.locator('table tbody tr');
        const count = await rows.count();
        
        expect(count).toBeGreaterThan(0);
        
        // Vérifier que toutes les lignes affichées sont dans Services tiers
        for (let i = 0; i < count; i++) {
            const row = rows.nth(i);
            await expect(row).toContainText('Services tiers');
        }
    });

    test('recherche une variable Ringover spécifique', async ({ page }) => {
        await page.goto('/super-admin/env-settings');
        
        // Utiliser la recherche
        await page.getByPlaceholder('Rechercher').fill('RINGOVER');
        
        // Attendre les résultats
        await page.waitForTimeout(500);
        
        // Vérifier que les résultats contiennent RINGOVER
        const rows = page.locator('table tbody tr');
        const count = await rows.count();
        
        expect(count).toBeGreaterThan(0);
        
        // Vérifier que toutes les lignes contiennent RINGOVER
        for (let i = 0; i < count; i++) {
            const row = rows.nth(i);
            await expect(row.getByText(/RINGOVER/)).toBeVisible();
        }
    });

    test('vérifie le badge du groupe Services tiers', async ({ page }) => {
        await page.goto('/super-admin/env-settings');
        
        // Cliquer sur le filtre Services tiers
        await page.getByRole('button', { name: 'Filtres' }).click();
        await page.getByRole('option', { name: 'Services tiers' }).click();
        
        // Attendre le filtrage
        await page.waitForTimeout(500);
        
        // Vérifier que les badges sont affichés
        const badges = page.locator('table tbody tr td:first-child span');
        const count = await badges.count();
        
        expect(count).toBeGreaterThan(0);
        
        // Vérifier que le badge contient "Services tiers"
        for (let i = 0; i < count; i++) {
            const badge = badges.nth(i);
            await expect(badge).toContainText('Services tiers');
        }
    });
});
