const { chromium } = require('playwright');

(async () => {
  const browser = await chromium.launch();
  const page = await browser.newPage();
  const errors = [];
  page.on('console', msg => { if (msg.type() === 'error') errors.push(msg.text()); });
  page.on('pageerror', err => errors.push('PAGEERROR: ' + err.message));
  page.on('response', res => { if (res.status() >= 400) errors.push(`HTTP ${res.status()}: ${res.url()}`); });

  await page.goto('http://127.0.0.1:8000/ns-conseil/login', { waitUntil: 'domcontentloaded' });
  await page.waitForTimeout(800);
  await page.locator('input[type="email"]').fill('claude.phonetest@local.test');
  await page.locator('input[type="password"]').fill('TestPhone123!');
  await page.click('button[type="submit"]');
  await page.waitForFunction(() => !window.location.pathname.endsWith('/login'), { timeout: 15000 });
  await page.waitForTimeout(1000);

  await page.goto('http://127.0.0.1:8000/ns-conseil/prospects/create', { waitUntil: 'domcontentloaded' });
  await page.waitForSelector('.iti', { timeout: 15000 });
  await page.waitForTimeout(800);

  const count = await page.locator('.iti').count();
  console.log('Prospect create: .iti fields found (expect 2 - telephone + telephone_alt):', count);

  await page.screenshot({ path: 'C:/Works/Dev/crmfilament/_tmp_prospect.png', fullPage: false });

  console.log('ERRORS:', JSON.stringify(errors, null, 2));
  await browser.close();
})();
