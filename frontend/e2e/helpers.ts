import { expect, type Page } from '@playwright/test';

// Users from backend/src/DataFixtures/AppFixtures.php
export const ADMIN = { email: 'admin@crm.local', password: 'admin123' };
export const SALES = { email: 'sales@crm.local', password: 'sales123' };

export async function login(page: Page, user: { email: string; password: string } = ADMIN) {
  await page.goto('/login');
  await page.locator('input[type="email"]').fill(user.email);
  await page.locator('input[type="password"]').fill(user.password);
  await page.getByRole('button', { name: 'Sign in' }).click();
  await expect(page).toHaveURL('/');
}
