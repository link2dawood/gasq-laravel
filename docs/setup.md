# Setup

## Prerequisites

- PHP 8.2+
- Composer
- Node.js & npm (for front-end assets)
- SQLite (default) or MySQL/PostgreSQL

## Local setup (native)

1. Clone the repository and enter the Laravel project:

   ```bash
   cd gasq-laravel
   ```

2. Install PHP dependencies:

   ```bash
   composer install
   ```

3. Copy environment file and generate key:

   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. Configure `.env` (database, mail, optional Google OAuth). Default uses SQLite; ensure `database/database.sqlite` exists or run:

   ```bash
   touch database/database.sqlite
   ```

5. Run migrations:

   ```bash
   php artisan migrate
   ```

6. (Optional) Build front-end assets:

   ```bash
   npm install && npm run build
   ```

7. Start the dev server:

   ```bash
   php artisan serve
   ```

   Open `http://localhost:8000`.

## Docker (local only)

Docker is for **local development only**, not production.

### Quick start

1. From the project root (`gasq-laravel`):

   ```bash
   cp .env.docker.example .env
   php artisan key:generate
   ```

   Or copy your existing `.env` and set `DB_HOST=db`, `DB_DATABASE=gasq`, `DB_USERNAME=gasq`, `DB_PASSWORD=secret`, `APP_URL=http://localhost:8080`.

2. Build and start containers:

   ```bash
   docker compose up -d --build
   ```

3. On first run, the app container will run `composer install` automatically if `vendor` is missing. Generate key and run migrations:

   ```bash
   docker compose exec app php artisan key:generate
   docker compose exec app php artisan migrate
   ```

4. (Optional) Build assets:

   ```bash
   docker compose exec node npm install && docker compose exec node npm run build
   ```

   Or run `npm run build` on the host and ensure `public/build` is present.

5. Open the app at **http://localhost:8080**.

### Services

| Service | Purpose |
|---------|---------|
| `app` | PHP 8.2 + Laravel; serves the app (artisan serve or PHP-FPM) |
| `db` | MySQL 8 (or set DB_CONNECTION=sqlite and use a volume) |
| `node` | Optional; for running npm build inside the stack |

### Useful commands

```bash
# Run migrations
docker compose exec app php artisan migrate

# Run the queue worker for queued notifications and mail
docker compose exec app php artisan queue:work

# Run seeders
docker compose exec app php artisan db:seed

# Shell into app container
docker compose exec app bash

# View logs
docker compose logs -f app
```

## Environment variables

| Variable | Description |
|----------|-------------|
| `APP_URL` | Full URL of the app (e.g. `http://localhost:8080` in Docker) |
| `DB_CONNECTION` | `sqlite`, `mysql`, or `pgsql` |
| `DB_HOST` | For Docker: `db` |
| `GOOGLE_CLIENT_ID` / `GOOGLE_CLIENT_SECRET` | For Google OAuth (optional) |

See `.env.example` and `.env.docker.example` for full lists.

## Queue note

The app defaults to Laravel's `database` queue connection. A queue worker is required for queued bid notifications and credits-added emails to be delivered.
