import { defineConfig, devices } from '@playwright/test';
import path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));

/**
 * E2E tests expect the backend fixture users (admin@crm.local / admin123 etc.),
 * see e2e/README.md.
 *
 * Env:
 *   E2E_BASE_URL   frontend to test (default http://localhost:3001, the docker frontend)
 *   CHROMIUM_PATH  use a locally installed Chromium instead of `npx playwright install`
 */
const baseURL = process.env.E2E_BASE_URL ?? 'http://localhost:3001';

export default defineConfig({
  testDir: path.resolve(__dirname, './e2e'),
  testMatch: /.+\.spec\.(js|ts|jsx|tsx)$/,
  outputDir: path.resolve(__dirname, './test-results'),

  timeout: 30000,
  expect: { timeout: 5000 },

  fullyParallel: true,
  forbidOnly: !!process.env.CI,
  retries: process.env.CI ? 2 : 0,
  workers: process.env.CI ? 1 : undefined,
  reporter: [['list'], ['html', { open: 'never' }]],

  use: {
    baseURL,
    actionTimeout: 5000,
    trace: 'retain-on-failure',
    screenshot: 'only-on-failure',
  },

  projects: [
    {
      name: 'chromium',
      use: {
        ...devices['Desktop Chrome'],
        launchOptions: { executablePath: process.env.CHROMIUM_PATH || undefined },
      },
    },
  ],

  // Starts Vite if nothing is listening on baseURL yet (e.g. docker frontend not running)
  webServer: {
    command: `npm run dev -- --port ${new URL(baseURL).port || 3001} --strictPort`,
    url: baseURL,
    reuseExistingServer: !process.env.CI,
    timeout: 120000,
  },
});
