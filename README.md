# Enterprise CRM

Symfony 7 + API Platform backend, React 18 + TypeScript frontend.

## Quick Start

### Prerequisites
- PHP 8.2+, Composer, Symfony CLI
- Node 20+
- PostgreSQL 16 (or Docker)

### Backend

```bash
cd backend
composer install
# Generate JWT keys
php bin/console lexik:jwt:generate-keypair
# Create DB & run migrations
php bin/console doctrine:database:create
php bin/console doctrine:migrations:diff
php bin/console doctrine:migrations:migrate
# Load sample data
composer require --dev doctrine/doctrine-fixtures-bundle
php bin/console doctrine:fixtures:load
# Start server
symfony server:start --port=8000
```

### Frontend

```bash
cd frontend
npm install
npm run dev
```

### Or with Docker

```bash
docker compose up -d
```

## Credentials (fixtures)

| Role  | Email              | Password  |
|-------|--------------------|-----------|
| Admin | admin@crm.local    | admin123  |
| Sales | sales@crm.local    | sales123  |

## API

- Docs: http://localhost:8000/api/docs
- Login: `POST /api/login` → returns JWT

## Architecture

```
backend/
  src/
    Entity/       # User, Account, Contact, Deal, Activity
    Controller/   # AuthController, DashboardController
    Repository/   # Doctrine repositories
    DataFixtures/ # Sample data
  config/
    packages/     # security, jwt, api_platform, cors

frontend/
  src/
    api/          # Axios client
    store/        # Zustand auth store
    types/        # TypeScript interfaces
    pages/        # Dashboard, Contacts, Accounts, Deals, Activities
    components/   # Layout, Sidebar
```

## Role Hierarchy

```
ROLE_ADMIN > ROLE_MANAGER > ROLE_SALES > ROLE_USER
```
