# Invoices App

Laravel 12 + PHP 8.3 application for creating, managing, exporting, and emailing invoices with per-user data isolation.

## Core capabilities

- Authentication with separate data per user
- Invoice CRUD with reusable line items
- Client management with autofill into invoices
- Issuer profile management per user
- PDF export and email delivery
- Persistent invoice attachments
- PB allowances dashboard by year
- Laravel Sail development environment

## Stack

- Laravel 12
- PHP 8.3
- MySQL 8.4
- Redis
- Blade
- DomPDF
- Laravel Pint

## Quick start

```bash
git clone git@github.com:richard015ar/invoices.git
cd invoices
composer install
cp .env.example .env
./vendor/bin/sail up -d
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate
```

Open:

- App: [http://localhost:8080](http://localhost:8080)
- Mailpit: [http://localhost:8025](http://localhost:8025)

## Daily commands

```bash
./vendor/bin/sail up -d
./vendor/bin/sail down
./vendor/bin/sail artisan test
composer lint
composer format
```

## Email setup

Configure SMTP in `.env`. Gmail should use an app password instead of the account password.

Relevant variables:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_ENCRYPTION=tls
MAIL_USERNAME=your_email@gmail.com
MAIL_PASSWORD=your_app_password
MAIL_FROM_ADDRESS=your_email@gmail.com
MAIL_FROM_NAME="Your Name"
MAIL_INVOICE_COPY_TO=richard015ar@gmail.com
```

## Documentation

- [docs/usage.md](/Users/ricardoaragon/development/invoices-app/docs/usage.md)
- [docs/architecture.md](/Users/ricardoaragon/development/invoices-app/docs/architecture.md)
- [docs/development.md](/Users/ricardoaragon/development/invoices-app/docs/development.md)

## Commit style

This repository uses Conventional Commits.
