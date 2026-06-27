import { test, expect } from '@playwright/test';
import { loginToNsConseil } from './support/auth.js';

test.describe('Widget Performance Tests', () => {
  test.beforeEach(async ({ page }) => {
    await loginToNsConseil(page);
  });

  test('Dashboard charge rapidement', async ({ page }) => {
    const startTime = Date.now();
    await page.goto('/ns-conseil');
    const loadTime = Date.now() - startTime;
    
    // Le dashboard doit charger en moins de 3 secondes
    expect(loadTime).toBeLessThan(3000);
    await expect(page.locator('h1')).toContainText('Tableau de bord');
  });

  test('Widgets sont visibles après chargement', async ({ page }) => {
    await page.goto('/ns-conseil');
    
    // Attendre que les widgets soient chargés
    await page.waitForLoadState('networkidle');
    
    // Vérifier que le dashboard est visible
    await expect(page.locator('h1')).toBeVisible();
  });

  test('Polling ne cause pas de ralentissement visible', async ({ page }) => {
    await page.goto('/ns-conseil');
    
    // Mesurer le temps de réponse initial
    const startTime1 = Date.now();
    await page.waitForLoadState('networkidle');
    const initialLoadTime = Date.now() - startTime1;
    
    // Attendre un cycle de polling (60s configuré dans les widgets)
    // On attend seulement 2 secondes pour vérifier que l'interface reste réactive
    await page.waitForTimeout(2000);
    
    // Vérifier que l'interface est toujours réactive
    await expect(page.locator('h1')).toBeVisible();
    
    // Cliquer sur un élément pour vérifier la réactivité
    await page.getByRole('button', { name: /menu/i }).click();
    await expect(page.getByRole('navigation').first()).toBeVisible();
  });

  test('Navigation reste fluide avec widgets actifs', async ({ page }) => {
    await page.goto('/ns-conseil');
    await page.waitForLoadState('networkidle');
    
    // Naviguer vers une autre page
    const startTime = Date.now();
    await page.goto('/ns-conseil/prospects');
    const navigationTime = Date.now() - startTime;
    
    // La navigation doit être raisonnable (< 15 secondes pour charger les données)
    expect(navigationTime).toBeLessThan(15000);
    
    // Retourner au dashboard
    await page.goto('/ns-conseil');
    await expect(page.locator('h1')).toContainText('Tableau de bord');
  });
});
