# Architecture

## Overview

GASQ Laravel is a Laravel application that delivers the GASQ security services marketplace with calculators, credits (tokens), and admin tooling. All data and auth are handled in Laravel (no external BaaS for core app data).

## Stack

| Layer | Technology |
|-------|------------|
| Backend | Laravel 12, PHP 8.2+ |
| Frontend | Blade views, Blade components, Bootstrap 5 |
| Auth | Laravel session auth + Google OAuth (Socialite) |
| Database | SQLite (default), MySQL/PostgreSQL (configurable) |
| Queue / cache | Database driver (default); Redis optional |
| PDF / reports | To be added (e.g. DomPDF or Spatie PDF) |

## High-level flow

```
User → Web routes → HTTP Controllers → Services → Eloquent Models → Database
                         ↓
                  Blade views + Bootstrap components
                         ↓
                  Jobs/Queues (mail, notifications)
```

## Key directories

- `app/Http/Controllers` — HTTP controllers
- `app/Services` — Domain logic (calculators, wallet, matching)
- `app/Models` — Eloquent models
- `resources/views` — Blade templates and components
- `routes/web.php` — Web routes
- `database/migrations` — Schema

## Auth and roles

- **Existing**: Login, register, password reset, email verification, Google OAuth, profile (avatar, password).
- **Planned**: `user_type` on `users` (buyer, vendor, admin); middleware for admin-only routes; extended profile (company, phone) for buyers/vendors.

## Data model (planned)

- **Users**: Extended with `user_type`, optional `company`, `phone`
- **Marketplace**: `job_postings`, `bids`, `vendor_profiles`
- **Credits**: `wallets`, `transactions`, `feature_usage_rules`
- **Content**: `faqs`, `pricing_plans`, `settings`
- **Other**: `analytics_events`, `discovery_calls`, reports storage

See [Modules](modules.md) for the full feature list and status.
