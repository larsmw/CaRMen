# End-to-End Tests

Playwright tests that drive the CRM frontend in Chromium.

## Requirements

The tests log in as the fixture users from `backend/src/DataFixtures/AppFixtures.php`
(`admin@crm.local` / `admin123`, `sales@crm.local` / `sales123`) and expect the fixture
contacts (Alice Smith, Bob Jones). Load them into the database the frontend talks to:

```bash
# WARNING: wipes the data. Use ./reset-db.sh first if you want a backup.
docker compose exec backend php bin/console doctrine:migrations:migrate -n
docker compose exec backend php bin/console doctrine:fixtures:load -n
```

The migrate step is a no-op on a database that's already up to date, but is needed
after `./reset-db.sh`, which drops all tables.

## Running

```bash
cd frontend
npm test                # headless
npm run test:headed     # watch the browser
npm run test:ui         # Playwright UI mode
npx playwright test auth.spec.ts
npx playwright show-report   # HTML report of the last run
```

By default the tests run against the docker frontend on http://localhost:3001; if
nothing is listening there, Playwright starts Vite on that port itself.

| Env var         | Purpose                                                         |
|-----------------|-----------------------------------------------------------------|
| `E2E_BASE_URL`  | Test another frontend, e.g. `http://localhost:3002`             |
| `CHROMIUM_PATH` | Use an installed Chromium instead of `npx playwright install chromium` |

Example with a local Chromium:

```bash
CHROMIUM_PATH=/opt/ungoogled-chromium/chrome npm test
```

On failure, a screenshot and a trace (`npx playwright show-trace <file>`) are saved in
`test-results/`.

## Writing tests

- Log in with `login(page)` / `login(page, SALES)` from `helpers.ts`.
- Prefer role-based locators (`getByRole('button', { name: 'Sign out' })`,
  `getByRole('heading', { level: 1, name: 'Contacts' })`) over CSS classes. The app uses
  CSS modules, so class names are hashed.
- Tests run in parallel; keep them independent and don't rely on data created by
  another test.
