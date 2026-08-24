# Laravel mini-shop

Standalone example that installs [`mainmoney/mm-aggr-php-sdk`](https://github.com/MainMoney-Inc/mm_aggr_php_sdk)
as a Composer package. Same shop API as the Python and Node examples.

Default port: **8003**.

## Setup

```bash
cp .env.example .env
# set MM_CLIENT_ID, MM_API_SECRET, and MM_WEBHOOK_SECRET
composer install
./scripts/reset-db
```

Until Packagist lists the SDK, Composer loads `dev-main` from GitHub
(see `composer.json` repositories).

```bash
./scripts/seed
```

Update `data/initial.sqlite3` only when the schema or catalog changes:

```bash
php artisan migrate
./scripts/seed
./scripts/export-initial-db
```

Do not commit `db.sqlite3`.

## Run

```bash
php artisan serve --host=127.0.0.1 --port=8003
```

Then start a JS frontend example:

```
VITE_MERCHANT_BACKEND_URL=http://127.0.0.1:8003
```

Aggregator webhooks cannot reach `localhost`. Use a tunnel for `POST /webhooks`.
Status polling works without a public URL.
