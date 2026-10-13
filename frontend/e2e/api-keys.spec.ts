import { test, expect } from '@playwright/test';
import { login } from './helpers';

test.describe('API keys', () => {
  test.beforeEach(async ({ page }) => {
    await login(page);
  });

  test('Page loads when opened directly', async ({ page }) => {
    // /api-keys must not be mistaken for an /api/ request by the proxies
    await page.goto('/api-keys');
    await expect(page.getByRole('heading', { level: 1, name: 'API Keys' })).toBeVisible();
  });

  test('Admin creates a key and sees it once', async ({ page }) => {
    await page.getByRole('navigation').getByRole('link', { name: 'API Keys' }).click();
    await page.getByRole('button', { name: '+ New API key' }).click();
    const name = `E2E key ${Date.now()}`;
    await page.getByLabel('Name').fill(name);
    await page.getByRole('button', { name: 'Create key' }).click();

    await expect(page.getByTestId('new-api-key')).toHaveText(/^crm_[0-9a-f]{8}_[A-Za-z0-9_-]{32}$/);
    await page.getByRole('button', { name: 'Done' }).click();

    const row = page.getByRole('row', { name: new RegExp(name) });
    await expect(row).toContainText('Active');
    await expect(page.getByTestId('new-api-key')).toHaveCount(0);
  });
});
