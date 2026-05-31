# GASQ — Get A Security Quote

**GASQ** is a web platform for the **security-services market**. It connects **organizations that need security** (buyers) with **security providers** (vendors), and gives both sides **calculators and pricing tools** to understand and compare costs — hourly rates, staffing, patrol models, and contract economics — so procurement decisions are grounded in numbers, not guesswork.

Buyers can post jobs; vendors can submit bids and counter-offers; and both can run cost calculators to prepare transparent, competitive proposals. The app is a conventional, server-rendered **Laravel 12** site backed by a relational database, with a **credit-based** access model for advanced tools and **Stripe** for payments.

> 🌐 Production (beta): `https://getasecurityquotenow.com/beta`

---

## What you can do

| Audience | Capabilities |
|----------|--------------|
| **Buyers** | Register, verify phone, post jobs, review vendor bids, hire vendors, buy credits, and run the full set of cost calculators. |
| **Vendors** | Register, browse opportunities/leads, submit bids and counter-offers, complete questionnaires, and use calculators to price work. |
| **Administrators** | Manage settings, users, credit costs, and account/credit oversight. |

### Key features
- **Marketing & info pages** — landing, pricing, FAQ, payscale, payment model, terms, privacy.
- **Accounts & phone verification** — SMS code verification gates sensitive, paid features.
- **Job posting workflow** — start → preview → review → publish → hire/close, with workflow-status tracking.
- **Bidding** — bids, offer responses, counter-offers, vendor opportunities and invitations.
- **Calculators** — GASQ TCO calculator, government-contract calculator, coverage-schedule, instant estimator, and master inputs.
- **Credits & wallet** — prepaid credits meter advanced calculator usage; wallet, transactions, and coupon redemption.
- **Payments** — Stripe checkout with idempotent webhook handling.

For a non-technical walkthrough see [docs/OVERVIEW_FOR_STAKEHOLDERS.md](docs/OVERVIEW_FOR_STAKEHOLDERS.md). A deeper [case study](docs/CASE_STUDY.md) and full [technical docs](docs/README.md) are also available.

---

## Tech stack

- **Backend:** PHP 8.2, [Laravel 12](https://laravel.com)
- **Frontend:** Blade templates, Bootstrap 5, jQuery, [Laravel Mix](https://laravel-mix.com) (webpack), Sass
- **Database:** MySQL
- **Payments:** Stripe
- **Email:** SMTP (Resend)
- **PDF / Images:** dompdf, Intervention Image
- **Auth extras:** Laravel Socialite, SMS phone verification
- **Containerization:** Docker / docker-compose
- **CI/CD:** GitHub Actions → SSH deploy (see below)

---

## Getting started (local)

### Prerequisites
- PHP 8.2+, Composer
- Node.js 20+ and npm
- MySQL (or use the bundled Docker setup)

### Setup
```bash
# 1. Install dependencies
composer install
npm install

# 2. Environment
cp .env.example .env
php artisan key:generate

# 3. Configure .env (DB_*, MAIL_*, STRIPE_*, GOOGLE_MAPS_API_KEY, etc.)

# 4. Migrate the database
php artisan migrate

# 5. Build assets
npm run dev        # or: npm run watch  /  npm run production

# 6. Serve
php artisan serve
```

### With Docker
```bash
make up            # see the Makefile / docker-compose.yml for targets
```

> A **queue worker** is required for some billing/notification flows:
> `php artisan queue:work`. See [docs/setup.md](docs/setup.md).

---

## Testing

```bash
php artisan test          # or: ./vendor/bin/phpunit
```

Feature tests cover billing hardening, coupon redemption, and the wallet/credits flow.

---

## Project structure

```
app/            Controllers, models, services (WalletService, billing, jobs, bids)
config/         Laravel configuration
database/       Migrations, factories, seeders
public/         Web root + compiled assets (build/, css/, js/)
resources/      Blade views, Sass/JS sources
routes/         web.php (all HTTP routes), console.php
tests/          Feature & unit tests
docs/           Architecture, setup, stakeholder overview, case study
```

---

## Deployment

Pushing to **`main`** triggers an automated build-and-deploy via GitHub Actions:
it builds Composer + npm assets on the runner, then `rsync`s to the server and runs
post-deploy artisan commands over SSH.

- Workflow: [.github/workflows/deploy.yml](.github/workflows/deploy.yml)
- Setup & required secrets: [.github/DEPLOYMENT.md](.github/DEPLOYMENT.md)

---

## License

Proprietary — © GASQ. All rights reserved.
