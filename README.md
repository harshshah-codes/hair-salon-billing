# Nirav Hair Storm — Salon & Spa Billing System

A lightweight, PHP 8 + MySQL billing and management system for salons and spas. No framework — plain PHP MVC with a small custom core (routing, database, views, auth, RBAC, CSV/PDF export).

## Features

- **Dashboard** — revenue summary, chart of sales, recent bills, top services, staff performance, low package balances, inactive customers, activity feed
- **Billing** — create invoices, apply services & packages, employee allocation, record/cancel payments, print & PDF invoices, full history with filters
- **Customers** — profiles with photo, phone/mobile, notes, package balances and per-customer invoice history
- **Packages** — prepaid plans with per-transaction deduction ledger and low-balance tracking
- **Services** — categorized service catalog with pricing
- **Employees** — staff directory with per-employee service/earnings breakdown
- **Reports** — revenue, package sales, earnings, service totals, outstanding balances, customer statements; print & CSV export
- **Settings** — business info, invoice numbering, preferences, users & role-based permissions (RBAC), theme toggle, DB backup, activity log
- **Auth** — login, password change, session handling, CSRF protection

## Requirements

- PHP 8.1+ with `pdo_mysql`
- MySQL 5.7+ / MariaDB 10.3+
- A web server (Apache/PHP built-in server)

## Installation

1. Clone the repo and point your web server document root at `public/`.
2. Create a database (default name: `nirav_hairstorm`). Credentials can be set via environment variables:

   ```
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=nirav_hairstorm
   DB_USERNAME=root
   DB_PASSWORD=
   ```

3. With the PHP built-in server:

   ```
   php -S 127.0.0.1:8000 -t public
   ```

4. Visit `http://127.0.0.1:8000/install.php` to create the schema, seed reference data and set your admin account.
5. **Delete `public/install.php` after installation** and sign in.

## Configuration

Environment variables:

| Variable      | Default                   | Description               |
|---------------|---------------------------|---------------------------|
| `APP_ENV`     | `production`              | Application environment   |
| `APP_DEBUG`   | `false`                   | Enable debug error output |
| `APP_URL`     | `http://localhost/salon`  | Base URL                  |
| `DB_HOST`     | `127.0.0.1`               | Database host             |
| `DB_PORT`     | `3306`                    | Database port             |
| `DB_DATABASE` | `nirav_hairstorm`         | Database name             |
| `DB_USERNAME` | `root`                    | Database user             |
| `DB_PASSWORD` | *(empty)*                 | Database password         |

## Project Structure

```
app/
  Controllers/   Request handlers
  Core/          Custom framework (Router, Database, Auth, CSRF, View, ...)
  Helpers/       Global helper functions
  Middleware/    Auth/guest/permission guards
  Models/        Data models
  Repositories/  Query logic
  Services/      Business logic
  Views/         PHP templates (layouts, partials, pages)
config/          Application & database configuration
database/        schema.sql, seed.sql
public/          Document root (index.php, install.php, css/js/uploads)
routes/          Route definitions
```
