import { test, expect } from '@playwright/test';
import { login } from './helpers';

test.describe('Dashboard', () => {
  test.beforeEach(async ({ page }) => {
    await login(page);
  });

  test('Shows stats and pipeline', async ({ page }) => {
    await expect(page.getByRole('heading', { name: 'Dashboard' })).toBeVisible();
    await expect(page.getByRole('heading', { name: 'Pipeline' })).toBeVisible();
  });

  test('Admin sees admin navigation', async ({ page }) => {
    const nav = page.getByRole('navigation');
    await expect(nav.getByRole('link', { name: 'Users' })).toBeVisible();
    await expect(nav.getByRole('link', { name: 'Role Permissions' })).toBeVisible();
  });

  for (const [label, path] of [
    ['Contacts', '/contacts'],
    ['Accounts', '/accounts'],
    ['Deals', '/deals'],
    ['Activities', '/activities'],
  ] as const) {
    test(`Can navigate to ${label}`, async ({ page }) => {
      await page.getByRole('navigation').getByRole('link', { name: label }).click();
      await expect(page).toHaveURL(path);
      await expect(page.getByRole('heading', { level: 1, name: label })).toBeVisible();
    });
  }
});
