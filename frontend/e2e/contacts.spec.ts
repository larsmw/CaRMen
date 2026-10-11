import { test, expect } from '@playwright/test';
import { login } from './helpers';

test.describe('Contacts', () => {
  test.beforeEach(async ({ page }) => {
    await login(page);
    await page.goto('/contacts');
  });

  test('Lists fixture contacts', async ({ page }) => {
    await expect(page.getByRole('heading', { level: 1, name: 'Contacts' })).toBeVisible();
    await expect(page.getByRole('link', { name: 'Alice Smith' })).toBeVisible();
    await expect(page.getByRole('link', { name: 'Bob Jones' })).toBeVisible();
  });

  test('Opens contact detail page', async ({ page }) => {
    await page.getByRole('link', { name: 'Alice Smith' }).click();
    await expect(page).toHaveURL(/\/contacts\/[\w-]+$/);
    await expect(page.getByRole('heading', { level: 1, name: 'Alice Smith' })).toBeVisible();
  });

  test('Search filters the list', async ({ page }) => {
    await page.getByPlaceholder('Search...').fill('Alice');
    await expect(page.getByRole('link', { name: 'Alice Smith' })).toBeVisible();
    await expect(page.getByRole('link', { name: 'Bob Jones' })).toHaveCount(0);
  });
});
