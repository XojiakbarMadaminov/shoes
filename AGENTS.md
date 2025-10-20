# Repository Guidelines

## Project Structure & Module Organization
- **app/** – Laravel application logic; Filament pages live under `app/Filament`, Eloquent models under `app/Models`.
- **app/Services/** – Domain services such as `PurchaseService` encapsulate business rules for stock updates and debt tracking.
- **resources/views/** – Blade templates for Filament UI, receipts, and public pages.
- **routes/web.php** – HTTP routes for PDFs and other web endpoints.
- **database/migrations/** – Schema definitions. New migrations must follow timestamped naming.
- **public/** & **storage/** – Static assets and generated media; avoid committing large binaries.

## Build, Test, and Development Commands
- `composer install` – Install PHP dependencies.
- `npm install` & `npm run build` – Manage and compile frontend assets via Vite.
- `php artisan serve` – Launch the development server.
- `php artisan migrate` – Apply database migrations locally (required after adding purchases/supplier-debt tables).
- `php artisan test` or `vendor/bin/phpunit` – Run the automated test suite.

## Coding Style & Naming Conventions
- Follow PSR-12 for PHP; 4-space indentation is standard.
- Use descriptive class names (e.g., `SalesHistoryPage`) and snake_case column names in migrations.
- Blade templates should remain compact; extract reusable UI into components under `resources/views/filament`.
- Run `./vendor/bin/pint` before committing to ensure consistent formatting.

## Testing Guidelines
- Tests reside in `tests/`; feature tests mirror Filament workflows and POS flows.
- Name tests using the `it_handles_pending_sales` style for clarity.
- Add coverage when adjusting checkout logic, migrations, or Filament actions.
- Execute `php artisan test --filter <name>` for targeted runs during development.

## Commit & Pull Request Guidelines
- Use imperative, present-tense commit messages (e.g., `Add cashier badge to sales history`).
- Each PR should describe the functional change, include reproduction or validation steps, and reference relevant issues.
- Attach screenshots or terminal snippets when updating Filament UI or CLI workflows.
- Ensure migrations run cleanly and tests pass before requesting review.


## Laravel 12 documentation
https://laravel.com/docs/12.x

## Filament 4 documentation
https://filamentphp.com/docs/4.x
