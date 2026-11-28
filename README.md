# POS Management System API

Laravel 10 REST API for POS authentication, catalog management, checkout, payments, inventory, receipts, sales history, and reports.

## Requirements

- PHP 8.1 or newer with the extensions required by Laravel
- Composer
- MySQL (or another Laravel-supported database)

## Local setup

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Set `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD` in `.env`, then run:

```bash
php artisan migrate --seed
php artisan serve
```

The seeder creates demo accounts (`admin@pos.test`, `manager@pos.test`, `cashier@pos.test`) with password `password`. Use these only for local development; replace or remove them before deploying.

## Authentication

Login with `POST /api/login` using JSON `{ "email": "...", "password": "..." }`. Use the returned Sanctum bearer token on protected endpoints:

```http
Authorization: Bearer <token>
Accept: application/json
```

Logout with `POST /api/logout`; the current token is revoked.

## API routes

All routes are under `/api` (the current application does not use a `/v1` prefix).

| Area | Routes | Access |
|---|---|---|
| Authentication | `POST /login`, `POST /logout`, `GET /me` | Login public; others authenticated |
| Dashboard | `GET /dashboard` | Authenticated |
| Categories | `GET /categories`, `GET /categories/{id}`, `POST /categories`, `PUT /categories/{id}`, `DELETE /categories/{id}` | Read: authenticated; writes: admin/manager |
| Products | `GET /products`, `GET /products/{id}`, `POST /products`, `PUT /products/{id}`, `DELETE /products/{id}` | Read: authenticated; writes: admin/manager |
| Sales | `GET /sales`, `GET /sales/{id}`, `POST /sales`, `POST /sales/{id}/void` | Authenticated; void: admin/manager |
| Receipts/payments | `GET /payments`, `GET /payments/receipt/{saleId}` | Authenticated |
| Inventory | `GET /inventory`, `GET /inventory/movements`, `POST /inventory/adjust` | Admin/manager |
| Reports | `GET /reports/daily-sales`, `/reports/revenue`, `/reports/top-products`, `/reports/cashier-sales` | Admin/manager |
| Users/settings | `/users...`, `GET /settings`, `PUT /settings` | Admin |

Product listing supports `search`, `category_id`, `active_only`, `low_stock`, `sort_by`, `sort_dir`, and `per_page` query parameters. Sales and reports support date filters as implemented by their endpoints.

## Checkout example

`POST /api/sales`:

```json
{
  "items": [{ "product_id": 1, "quantity": 2 }],
  "payment_method": "cash",
  "tendered": 20,
  "discount": 1
}
```

Payment methods are `cash`, `card`, and `mobile`. Cash checkout requires `tendered`. The tax rate is configured by an admin under `tax_rate` in `PUT /api/settings` and applied by the backend as a percentage to the discounted subtotal. Checkout locks product rows and runs sale, payment, and stock changes in a database transaction.

## Void sale example

`POST /api/sales/{id}/void` requires an admin or manager and a reason:

```json
{ "reason": "Duplicate checkout" }
```

Voiding records the actor, timestamp, reason, and stock restoration movement.

## Development notes

- API routes are in `routes/api.php`.
- Migrations, seeders, and factories are in `database/`.
- The database ERD source is `database/erd.dbml`. Paste it into [dbdiagram.io](https://dbdiagram.io/d) to render or export the diagram. Update it when migrations change.
- Feature tests are in `tests/Feature/`.
- Run the test suite with `php artisan test`.
- Configure `tax_rate` as a percentage from 0 to 100. Keep `.env` and production credentials private.
