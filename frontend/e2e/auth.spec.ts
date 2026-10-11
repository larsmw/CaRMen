import { test, expect } from '@playwright/test';
import { ADMIN, SALES, login } from './helpers';

test.describe('Authentication', () => {
  test('Login page loads', async ({ page }) => {
    await page.goto('/login');
    await expect(page.getByRole('heading', { name: 'Sign in to CRM' })).toBeVisible();
    await expect(page.locator('input[type="email"]')).toBeVisible();
    await expect(page.locator('input[type="password"]')).toBeVisible();
  });

  test('Admin login redirects to dashboard', async ({ page }) => {
    await login(page, ADMIN);
    await expect(page.getByRole('heading', { name: 'Dashboard' })).toBeVisible();
  });

  test('Sales login redirects to dashboard', async ({ page }) => {
    await login(page, SALES);
    await expect(page.getByRole('heading', { name: 'Dashboard' })).toBeVisible();
  });

  test('Invalid credentials show an error', async ({ page }) => {
    await page.goto('/login');
    await page.locator('input[type="email"]').fill(ADMIN.email);
    await page.locator('input[type="password"]').fill('wrongpassword');
    await page.getByRole('button', { name: 'Sign in' }).click();

    await expect(page.getByText('Invalid credentials')).toBeVisible();
    await expect(page).toHaveURL('/login');
  });

  test('Unauthenticated user is redirected to login', async ({ page }) => {
    await page.goto('/');
    await expect(page).toHaveURL('/login');
  });

  test('Expired token sends user back to login', async ({ page }) => {
    await page.goto('/login');
    await page.evaluate(() => {
      localStorage.setItem('token', 'invalid');
      localStorage.setItem('crm-auth', JSON.stringify({ state: { token: 'invalid' }, version: 0 }));
    });
    await page.goto('/');
    await expect(page).toHaveURL('/login');
  });

  test('Sign out clears session', async ({ page }) => {
    await login(page);
    await page.getByRole('button', { name: 'Sign out' }).click();
    await expect(page).toHaveURL('/login');

    await page.goto('/');
    await expect(page).toHaveURL('/login');
  });
});
