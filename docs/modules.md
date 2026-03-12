# Feature modules

Status key: **Planned** | **In progress** | **Done**

## Phase 1 — Foundation & content

| Module | Status | Notes |
|--------|--------|-------|
| Docker (local only) | Done | docker-compose, Dockerfile, .env.docker.example, docker-entrypoint.sh |
| Extend auth (user_type, roles) | Done | buyer/vendor/admin, middleware, profile company/phone |
| Data model & migrations | Done | wallets, transactions, feature_usage_rules, job_postings, bids, vendor_profiles, analytics_events, discovery_calls, faqs, pricing_plans, settings |
| Landing page | Done | GASQ hero, #buyers, #sellers, #how-it-works |
| Marketplace landing | Done | |
| Pricing page | Done | Plans from DB |
| FAQs | Done | DB-driven accordion |
| PayScale page | Done | Placeholder |
| Policy / Payment | Done | Placeholder |
| Post coverage schedule | Done | Placeholder |
| Account balance (display) | Done | Wallet balance + transaction history (no payment yet) |

## Phase 2 — Marketplace & tools

| Module | Status | Notes |
|--------|--------|-------|
| Job posting | Done | Create, edit, delete job postings (buyers) |
| Job board | Done | Browse, filter, search; public and auth |
| Bid / Job post | Done | Submit bid (vendors), accept/reject (buyers) |
| Vendor profile | Done | View vendor by user ID |
| Main menu calculator | Done | Multi-tab: Security, Manpower, Economic, Bill Rate |
| Instant estimator | Done | Location, hours, guards → estimate |
| Contract analysis | Done | Categories with pay/bill rate → summary |
| Security billing | Done | Hourly rate × hours → weekly/monthly/annual |
| Mobile patrol (calc + comparison) | Done | Single scenario + compare two |
| Discovery call | Done | Authenticated users can request calls, stored in `discovery_calls` |
| Analytics (logging) | Done | Middleware logs page views to `analytics_events`; admin dashboard shows metrics |

## Phase 3 — Monetization & admin

| Module | Status | Notes |
|--------|--------|-------|
| Credits (purchase) | Planned | Payment integration, top-up (Stripe later) |
| Security billing | Done | Billing calculator (see Phase 2) |
| Mobile patrol calculator | Done | (see Phase 2) |
| Mobile patrol comparison | Done | (see Phase 2) |
| Admin settings | Done | App settings (grouped keys) |
| Admin tokens | Done | Wallets list, adjust balance, feature rules |
| Analytics dashboard | Done | Admin-only view of metrics and events |
| Receipt and report | Done | PDF (dompdf), receipts, calculator reports, download + email |

## Data model (planned)

- **users** — extended with `user_type`, `company`, `phone`
- **wallets** — user_id, balance
- **transactions** — user_id, tokens_change, type, reference, balance_after
- **feature_usage_rules** — feature_key, tokens_required, feature_name
- **job_postings** — title, description, budget, location, dates, buyer_id
- **bids** — job_id, vendor_id, amount, status, message
- **vendor_profiles** — user_id, company_name, capabilities, verification
- **analytics_events** — event_type, user_id, event_data (JSON)
- **discovery_calls** — user_id, requested_time, status
- **faqs** — question, answer, order
- **pricing_plans** — name, price, features
- **settings** — key/value for admin
