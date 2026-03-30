#!/bin/sh
set -e
cd /var/www/html

# ── 1. Bootstrap .env ──────────────────────────────────────────────────────
if [ ! -f .env ]; then
  if [ -f .env.example ]; then
    echo "[entrypoint] .env not found — copying from .env.example"
    cp .env.example .env
  else
    echo "[entrypoint] WARNING: no .env or .env.example found"
  fi
fi

# ── 2. Composer ─────────────────────────────────────────────────────────────
if [ ! -f vendor/autoload.php ]; then
  echo "[entrypoint] Installing Composer dependencies..."
  composer install --no-interaction --prefer-dist --no-progress
fi

# ── 3. App key ──────────────────────────────────────────────────────────────
if grep -q '^APP_KEY=$' .env 2>/dev/null; then
  echo "[entrypoint] Generating APP_KEY..."
  php artisan key:generate --no-interaction
fi

# ── 4. Clear config (so container env vars override any cached config) ──────
php artisan config:clear 2>/dev/null || true

# ── 5. Storage link ─────────────────────────────────────────────────────────
if [ ! -L public/storage ]; then
  echo "[entrypoint] Creating storage symlink..."
  php artisan storage:link 2>/dev/null || true
fi

# ── 6. Migrate (auto on every start; safe because migrate is idempotent) ────
echo "[entrypoint] Running migrations..."
php artisan migrate --force --no-interaction 2>&1 || echo "[entrypoint] WARNING: migrate failed — check DB connection"

exec "$@"
