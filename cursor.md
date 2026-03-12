# GASQ Laravel — Cursor / AI context

This file gives AI assistants and developers a quick overview of the project for editing and code generation.

## What this project is

- **GASQ Laravel**: Laravel 12 application that replicates the GASQ security services marketplace and calculators (originally React + Supabase). Auth and data are fully on Laravel; no Supabase.
- **Stack**: Laravel 12, PHP 8.2+, Blade + Bootstrap 5, session auth + Google OAuth. DB: SQLite default; MySQL/PostgreSQL supported. Docker is for **local development only**.

## Important paths

| Path | Purpose |
|------|---------|
| `docs/` | All project documentation (architecture, setup, modules, development). |
| `app/Models/User.php` | User model; to be extended with `user_type` (buyer/vendor/admin). |
| `app/Http/Controllers/Auth/` | Login, Register, profile. |
| `resources/views/layouts/app.blade.php` | Main app layout (Bootstrap navbar). |
| `routes/web.php` | Web routes; auth and profile under middleware. |

## Implementation plan (order of work)

- [x] 1. **Docker (local)** — docker-compose, Dockerfile, .env.docker.example.
- [x] 2. **Extend auth** — `user_type` on users, registration flow, admin middleware.
- [x] 3. **Data model** — Migrations for wallets, jobs, bids, vendor_profiles, analytics, FAQs, pricing, settings.
- [x] 4. **Landing & content** — Landing page, marketplace landing, pricing, FAQs, PayScale, policy, post coverage schedule.
- [x] 5. **Credits & balance** — Wallet service, account balance page (payment integration later).
- [x] 6. **Marketplace** — Job posting, job board, bids, vendor profile.
- [x] 7. **Calculators** — Main menu, instant estimator, contract analysis, security billing, mobile patrol (calc + comparison), post coverage.
- [x] 8. **Discovery & analytics** — Discovery call booking, analytics event logging.
- [x] 9. **Admin** — Settings, admin tokens dashboard.
- [x] 10. **Reports** — PDF/receipt generation and email.

## Conventions

- **Controllers**: Thin; domain logic in `app/Services`.
- **Validation**: Form Requests.
- **Front-end**: Blade + Blade components + Bootstrap 5; match GASQ UI/UX from the plan.
- **Docs**: Keep `docs/*.md` and this file updated when adding modules or changing setup.

## Docs index

- [docs/README.md](docs/README.md) — Docs overview and index.
- [docs/architecture.md](docs/architecture.md) — Architecture and stack.
- [docs/setup.md](docs/setup.md) — Local and Docker setup.
- [docs/modules.md](docs/modules.md) — Feature modules and status.
- [docs/development.md](docs/development.md) — Workflow and commands.
