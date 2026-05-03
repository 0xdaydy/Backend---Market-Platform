# Farmers Market Platform - Backend API

A Laravel REST API backend for a farmers market POS system with offline support, credit management, and commodity-based repayments.

## Features

- **Authentication**: Sanctum-based token authentication with role-based access control (Admin, Supervisor, Operator)
- **User Management**: Hierarchical user structure with self-referencing supervisor relationships
- **Farmer Management**: CRUD operations with credit limits, balance tracking, and search by card ID or phone
- **Product Catalog**: Self-referencing categories with nested tree support and product management
- **Cash Transactions**: Snapshot pricing with immutable transaction records and unique reference numbers
- **Credit Transactions**: Automatic interest calculation, debt creation, and credit limit enforcement
- **Repayments**: FIFO allocation across debts, partial/full repayment support, commodity-based repayments
- **Offline Support**: Server-side pre-validation endpoint for offline transactions
- **Settings**: Admin-configurable global settings (interest rate, default credit limit)
- **API Documentation**: Auto-generated OpenAPI/Swagger specs via Scramble

## Tech Stack

- **Framework**: Laravel 11
- **Authentication**: Laravel Sanctum
- **API Docs**: Dedoc Scramble (OpenAPI 3.1)
- **Database**: MySQL 8.0
- **Containerization**: Docker Compose (PHP-FPM + MySQL + Nginx)
- **Testing**: PHPUnit with SQLite in-memory

## Quick Start

### Prerequisites

- Docker & Docker Compose
- Git

### Installation

1. Clone the repository:
```bash
git clone <repository-url>
cd Backend---Market-Platform
```

2. Start the Docker containers:
```bash
docker compose up -d
```

3. Install PHP dependencies:
```bash
docker compose run --rm app composer install
```

4. Copy environment file:
```bash
cp .env.example .env
docker compose run --rm app php artisan key:generate
```

5. Run migrations and seed demo data:
```bash
docker compose run --rm app php artisan migrate:fresh --seed --seeder=DemoSeeder
```

6. The API will be available at `http://localhost:8080`

7. Open the interactive API docs at `http://localhost:8080/docs/api`

### Demo Credentials

After running the demo seeder:
- **Admin**: `admin@market.local` / `password`
- **Supervisor**: `supervisor@market.local` / `password`
- **Operator**: `operator@market.local` / `password`

### Operator Test Seeder

A standalone seeder for testing operator login flows across web and mobile platforms:

```bash
docker compose run --rm app php artisan db:seed --class=OperatorTestSeeder
```

| Role | Email | Password |
|------|-------|----------|
| Supervisor | `test-supervisor@market.local` | `password` |
| Operator | `test-operator@market.local` | `password` |

The seeder creates an operator under a supervisor and pre-generates Sanctum tokens for `web` and `mobile` device names. Tokens are printed to the console for use in manual testing. See [tests/Feature/OperatorTestSeederTest.php](tests/Feature/OperatorTestSeederTest.php) for automated coverage.

## API Documentation

### Interactive Docs (Scramble — Stoplight Elements)
Visit [http://localhost:8080/docs/api](http://localhost:8080/docs/api) for interactive Swagger-style API documentation. You can test every endpoint directly from the browser — click **Try it out** on any endpoint, fill in the parameters, and see real responses.

> If the page appears blank, check the browser console. The UI loads from `unpkg.com` CDN — if that's blocked, use the raw spec instead (see below).

### Raw OpenAPI Spec
```bash
# View the raw OpenAPI 3.1 JSON in your browser:
# http://localhost:8080/docs/api.json

# Export to a file:
docker compose run --rm app php artisan openapi:export

# Export to custom path:
docker compose run --rm app php artisan openapi:export --path=specs/api.json
```

Paste the exported JSON into [Swagger Editor](https://editor.swagger.io/) for an alternative interactive view.

### Quick Test from Terminal
```bash
# Login as operator
curl -X POST http://localhost:8080/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"test-operator@market.local","password":"password","device_name":"web"}'

# Copy the token from the response, then:
curl http://localhost:8080/api/v1/categories \
  -H "Authorization: Bearer <token>"
```

### Postman Collection
Import `docs/postman_collection.json` into Postman. Set the `token` collection variable after logging in.

## API Endpoints

### Authentication
| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/v1/auth/login` | Login with email/password |
| POST | `/api/v1/auth/logout` | Revoke current token |

### Users (Admin/Supervisor)
| Method | Endpoint | Access |
|--------|----------|--------|
| GET | `/api/v1/users` | Admin: all, Supervisor: own operators |
| POST | `/api/v1/users` | Admin: any role, Supervisor: operators only |
| GET | `/api/v1/users/{user}` | Admin: any, Supervisor: own operators |
| PUT | `/api/v1/users/{user}` | Admin: any, Supervisor: own operators |
| DELETE | `/api/v1/users/{user}` | Admin: any, Supervisor: own operators |

### Farmers
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/farmers` | List farmers (search by `card_id` or `phone`) |
| POST | `/api/v1/farmers` | Create farmer |
| GET | `/api/v1/farmers/{farmer}` | Show farmer |
| PUT | `/api/v1/farmers/{farmer}` | Update farmer |
| DELETE | `/api/v1/farmers/{farmer}` | Delete farmer |
| GET | `/api/v1/farmers/{farmer}/debts` | Farmer's debt summary |
| GET | `/api/v1/farmers/{farmer}/transactions` | Farmer's transactions |

### Catalog
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/categories` | List categories (nested tree) |
| POST | `/api/v1/categories` | Create category (admin only) |
| GET | `/api/v1/categories/{category}` | Show category |
| PUT | `/api/v1/categories/{category}` | Update category (admin only) |
| DELETE | `/api/v1/categories/{category}` | Delete category (admin only) |
| GET | `/api/v1/categories/{category}/products` | Products by category |
| GET | `/api/v1/products` | List products (filter by `category_id`) |
| POST | `/api/v1/products` | Create product (admin only) |
| GET | `/api/v1/products/{product}` | Show product |
| PUT | `/api/v1/products/{product}` | Update product (admin only) |
| DELETE | `/api/v1/products/{product}` | Delete product (admin only) |

### Transactions
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/transactions` | List transactions (filter by `payment_method`) |
| POST | `/api/v1/transactions` | Create transaction |
| GET | `/api/v1/transactions/{transaction}` | Show transaction |
| POST | `/api/v1/transactions/validate` | Offline pre-validation |

### Repayments
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/repayments` | List repayments |
| POST | `/api/v1/repayments` | Create repayment (cash or commodity) |
| GET | `/api/v1/repayments/{repayment}` | Show repayment |

### Settings (Admin Only)
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/settings` | Get all settings |
| PATCH | `/api/v1/settings` | Update settings |

## Request Examples

### Login
```bash
curl -X POST http://localhost:8080/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@market.local",
    "password": "password",
    "device_name": "cli"
  }'
```

### Create Credit Transaction
```bash
curl -X POST http://localhost:8080/api/v1/transactions \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -d '{
    "farmer_id": 1,
    "payment_method": "credit",
    "items": [
      {"product_id": 1, "quantity": 2}
    ]
  }'
```

### Create Commodity Repayment
```bash
curl -X POST http://localhost:8080/api/v1/repayments \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -d '{
    "farmer_id": 1,
    "payment_method": "commodity",
    "commodity_id": 1,
    "quantity": 20
  }'
```

### Validate Offline Transaction
```bash
curl -X POST http://localhost:8080/api/v1/transactions/validate \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -d '{
    "farmer_id": 1,
    "payment_method": "credit",
    "items": [
      {"product_id": 1, "quantity": 2, "unit_price": 1000}
    ]
  }'
```

## Architecture

### Module Depth

| Module | Depth | Description |
|--------|-------|-------------|
| **CreditAccount** | Deep | Encapsulates all credit balance/limit/surplus logic. Callers use `$farmer->creditAccount()->charge($amount)` |
| **TransactionEngine** | Medium | Creates debt records for credit transactions; delegates balance updates to CreditAccount |
| **RepaymentAllocator** | Medium | FIFO debt allocation with partial repayment support; delegates balance updates to CreditAccount |
| **TransactionPricing** | Medium | Item pricing calculation and price-change detection shared by online and offline paths |
| **OfflinePreValidator** | Medium | Server-side re-validation for offline transactions; uses TransactionPricing + CreditAccount |
| **CatalogController** | Shallow | Pure CRUD passthrough; acceptable for resource controllers |
| **FarmerController** | Shallow | Pure CRUD passthrough; acceptable for resource controllers |

### Key Models

- **User**: Authenticatable with role enum and supervisor self-reference
- **Farmer**: Credit tracking with `credit_limit` and `credit_balance_fcfa` (mutate through `CreditAccount` only)
- **Transaction**: Immutable records with snapshot pricing via `TransactionItem`
- **Debt**: 1:1 with credit transactions, tracks principal, interest, balance, status
- **Repayment**: Allocated across debts via FIFO through `DebtRepayment` pivot
- **Commodity/CommodityRate**: Dynamic kg-to-FCFA conversion rates

### Business Rules

- Interest rate: 5% default (configurable via settings)
- Default credit limit: 50,000 FCFA
- Credit surplus (negative balance) auto-applies to future credit transactions
- Excess repayments stored as credit surplus
- Debt statuses: `open` → `partially_paid` → `closed`

## Testing

```bash
# Run all tests
docker compose run --rm app php artisan test

# Run specific test suite
docker compose run --rm app php artisan test --filter=AuthTest

# Run with coverage (requires Xdebug)
docker compose run --rm app php artisan test --coverage
```

### Test Coverage

- **AuthTest**: Login/logout, token management, RBAC gates
- **UserManagementTest**: CRUD, role filtering, policy enforcement
- **FarmerManagementTest**: CRUD, search, credit fields, debts summary
- **CatalogTest**: Categories, products, nested tree, admin mutations
- **TransactionTest**: Cash transactions, snapshot pricing, references
- **CreditTransactionTest**: Interest calculation, limit enforcement
- **RepaymentTest**: FIFO allocation, partial/full repayment
- **CommodityRepaymentTest**: Commodity conversion, surplus handling
- **OfflineValidatorTest**: Server-side re-validation
- **SettingsManagementTest**: Admin-only settings CRUD
- **EndToEndFlowTest**: Complete market flow integration
- **CreditAccountTest** (Unit): CreditAccount value object invariants (9 read-only tests)
- **CreditAccountMutationTest** (Feature): CreditAccount charge/repay persistence (3 tests)
- **OperatorTestSeederTest**: Operator login, web/mobile token creation, supervisor assignment (5 tests)

## Docker Services

| Service | Container | Port | Description |
|---------|-----------|------|-------------|
| App | `market-app` | - | PHP 8.3 FPM |
| MySQL | `market-mysql` | 3306 | Database |
| Nginx | `market-nginx` | 8080 | Reverse proxy |

## Environment Variables

Key variables in `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=market_platform
DB_USERNAME=market
DB_PASSWORD=secret

DEFAULT_CREDIT_LIMIT=50000
INTEREST_RATE=0.05
```

## Development

### Useful Commands

```bash
# Run migrations
docker compose run --rm app php artisan migrate

# Fresh migrate with demo data
docker compose run --rm app php artisan migrate:fresh --seed --seeder=DemoSeeder

# Run specific seeder
docker compose run --rm app php artisan db:seed --class=DemoSeeder

# Seed operator test data (standalone, requires no prior data)
docker compose run --rm app php artisan db:seed --class=OperatorTestSeeder

# Tinker
docker compose run --rm app php artisan tinker

# Clear cache
docker compose run --rm app php artisan cache:clear

# Export OpenAPI
docker compose run --rm app php artisan openapi:export
```

### Code Style

The project follows Laravel conventions:
- **Resource controllers** for pure CRUD features (Catalog, Farmers, Users, Settings) — multi-method controllers under `app/Features/{Feature}/`
- **Single-action classes** (`__invoke`) for complex operations (StoreTransaction, StoreRepayment, ValidateTransaction, Auth)
- Feature-based folder structure under `app/Features/`
- Form Request validation classes
- Domain modules (e.g. `CreditAccount`) encapsulate business rules with a small public interface
- Service classes for shared business logic (TransactionPricing, TransactionEngine, RepaymentAllocator)
- Native Gates and Policies for authorization

## License

MIT
