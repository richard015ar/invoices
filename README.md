# Invoices App

A Laravel 12 + PHP 8.3 application to create, manage, and export professional invoices quickly.

## Features

- Invoice CRUD (create, edit, view, delete)
- Inline status update from `My invoices` table (`draft`, `sent`, `paid`)
- Clone invoice into a prefilled `New invoice` form
- Reusable catalog items (CRUD)
- PDF export with styled templates
- Multiple visual templates + accent color
- Invoice history and totals
- Laravel Sail support (MySQL, Redis, Mailpit)

## Tech Stack

- Laravel 12
- PHP 8.3
- MySQL 8.4
- Redis
- Blade + CSS
- DomPDF (`barryvdh/laravel-dompdf`)

## Requirements

- Docker Desktop or OrbStack
- Composer (only needed if you won't use Sail for dependency management)

## Quick Start (Sail - Recommended)

1. Clone the repository

```bash
git clone git@github.com:richard015ar/invoices.git
cd invoices
```

2. Install PHP dependencies

```bash
composer install
```

3. Configure environment

```bash
cp .env.example .env
```

4. Start containers

```bash
./vendor/bin/sail up -d
```

5. Generate app key and run migrations

```bash
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate
```

6. Open the app

- App: <http://localhost/invoices>
- Mailpit: <http://localhost:8025>

## Useful Commands

```bash
# Run tests
./vendor/bin/sail artisan test

# Stop containers
./vendor/bin/sail down

# Restart app container
./vendor/bin/sail restart laravel.test
```

## Notes

- Main entry route (`/`) redirects to `/invoices`.
- Invoice PDFs are generated server-side using DomPDF.
- Import/maintenance scripts are located in `scripts/`.

## Commit Convention

This repository uses **Conventional Commits**.

Examples:

- `feat: add inline invoice status chips`
- `fix: handle nullable catalog item tax rate`
- `docs: add sail setup instructions`

