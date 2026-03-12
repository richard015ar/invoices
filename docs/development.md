# Development Guide

## Local environment

The project is intended to run with Laravel Sail.

```bash
./vendor/bin/sail up -d
./vendor/bin/sail artisan migrate
```

Default URLs:

- App: `http://localhost:8080`
- Mailpit: `http://localhost:8025`

## Quality commands

Run the full automated checks:

```bash
./vendor/bin/sail artisan test
composer lint
```

Format the codebase:

```bash
composer format
```

## Storage

Invoice attachments are stored on the `local` disk under `storage/app/private/invoice-attachments`.

They are intentionally served through authenticated application routes instead of a public disk.

## Legacy import command

The repository includes a console command for importing historical PDF invoices:

```bash
./vendor/bin/sail artisan invoices:import-legacy-pdfs
./vendor/bin/sail artisan invoices:import-legacy-pdfs storage/imports --user=1
```

That command exists for data migration/maintenance and is not part of the primary application flow.

## Coding expectations

- Use Conventional Commits
- Keep comments minimal and in English only
- Prefer explicit ownership checks for user-scoped resources
- Prefer tests for behavioral changes before relying on manual verification
