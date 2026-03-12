# Development

## Workflow

1. Create a feature branch from `main` (or your default branch).
2. Implement following the plan phases: auth extension → data model → landing/content → marketplace → calculators → admin → reports.
3. Run tests and lint before pushing.
4. Document new modules or env vars in `docs/` and update `docs/modules.md` status.

## Commands

```bash
# Run migrations
php artisan migrate

# Rollback last migration
php artisan migrate:rollback

# Run seeders
php artisan db:seed

# Clear caches
php artisan config:clear && php artisan cache:clear && php artisan view:clear

# Run queue worker (if using queue)
php artisan queue:work

# Run tests
php artisan test
# or
./vendor/bin/phpunit
```

## Code style

- Use Laravel Pint for PHP formatting: `./vendor/bin/pint`.
- Controllers: keep thin; put business logic in `app/Services`.
- Use Form Requests for validation.
- Use Eloquent relationships and scopes; avoid raw SQL unless needed.

## Blade and Bootstrap

- Shared UI: use Blade components in `resources/views/components/`.
- Prefer Bootstrap 5 utility classes and components for layout and forms.
- Match existing GASQ UI/UX from the reference React app where specified in the plan.

## Adding a new module

1. Add migrations for any new tables.
2. Create Eloquent models and relationships.
3. Add service class(es) in `app/Services` if needed.
4. Add controller(s) and routes.
5. Add Blade views; use shared layout and components.
6. Update `docs/modules.md` and any relevant docs.
