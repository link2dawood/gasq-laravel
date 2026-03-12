# Stripe environment and keys

The app uses **Stripe mode** (test vs live) to choose which API keys and webhook secret to use.

## How it works

1. **`APP_ENV`** – Laravel’s environment: `local`, `staging`, `production`, etc.
2. **`STRIPE_MODE`** (optional) – Override for Stripe only: `test` or `live`.
   - If **set**: that value is used.
   - If **not set**: `production` → `live`, anything else → `test`.

So you can either:

- Rely on **APP_ENV**: set `APP_ENV=production` on the live server and `APP_ENV=local` (or similar) in dev; Stripe will use live keys in production and test keys otherwise.
- Or set **STRIPE_MODE** explicitly so Stripe doesn’t follow APP_ENV (e.g. `APP_ENV=production` but `STRIPE_MODE=test` for testing payments on prod).

## .env variables

### Dev / test mode (`STRIPE_MODE=test` or `APP_ENV` ≠ production)

- `STRIPE_SECRET_TEST` – Stripe **secret** key (starts with `sk_test_...`).
- `STRIPE_PUBLISHABLE_KEY_TEST` – Stripe **publishable** key (starts with `pk_test_...`).
- `STRIPE_WEBHOOK_SECRET_TEST` or `STRIPE_WEBHOOK_SECRET` – Webhook signing secret for your **test** webhook endpoint.

### Production / live mode (`STRIPE_MODE=live` or `APP_ENV=production`)

- `STRIPE_SECRET` – Stripe **secret** key (starts with `sk_live_...`).
- `STRIPE_PUBLISHABLE_KEY_LIVE` – Stripe **publishable** key (starts with `pk_live_...`).
- `STRIPE_WEBHOOK_SECRET_LIVE` or `STRIPE_WEBHOOK_SECRET` – Webhook signing secret for your **live** webhook endpoint.

### Optional (any mode)

- `STRIPE_SUCCESS_URL` – Where to send the user after successful checkout (default: app URL + `/credits`).
- `STRIPE_CANCEL_URL` – Where to send the user if they cancel checkout.
- `STRIPE_WEBHOOK_URL` – Your Stripe webhook URL (for reference; Stripe dashboard uses this).

## Example .env snippets

**Local dev (test keys):**

```env
APP_ENV=local
STRIPE_MODE=test
STRIPE_SECRET_TEST=sk_test_xxxxx
STRIPE_PUBLISHABLE_KEY_TEST=pk_test_xxxxx
STRIPE_WEBHOOK_SECRET_TEST=whsec_xxxxx
# optional:
STRIPE_SUCCESS_URL=http://localhost:8082/credits
STRIPE_CANCEL_URL=http://localhost:8082/credits
```

**Production (live keys):**

```env
APP_ENV=production
STRIPE_MODE=live
STRIPE_SECRET=sk_live_xxxxx
STRIPE_PUBLISHABLE_KEY_LIVE=pk_live_xxxxx
STRIPE_WEBHOOK_SECRET_LIVE=whsec_xxxxx
STRIPE_SUCCESS_URL=https://yoursite.com/credits/success
STRIPE_CANCEL_URL=https://yoursite.com/credits
```

If you omit `STRIPE_MODE`, production servers should have `APP_ENV=production` and the correct live Stripe vars; local can use `APP_ENV=local` and test vars.

## Using the keys in code

- **Secret / API**: `config('services.stripe.secret')` (used for Checkout and webhooks).
- **Publishable**: `config('services.stripe.publishable')` (for frontend Stripe.js if you add it).
- **Webhook secret**: `config('services.stripe.webhook_secret')`.
- **Mode**: `config('services.stripe.mode')` → `'test'` or `'live'`.

All Stripe usage in the app should go through `config('services.stripe.*')`; avoid reading Stripe keys from `env()` directly.
