# Trendy Windy store

Arabic storefront that resells the Mart catalogue with cash on delivery, with an Arabic/English admin panel at `/admin`. Design documents: `specs/001-reseller-storefront/`.

## Setup

Needs PHP 8.3, Composer, Node 24 and MySQL 8.

```bash
composer install && npm install && npm run build
cp .env.example .env && php artisan key:generate   # then set DB_* and MART_API_KEY
php artisan migrate && php artisan storage:link
php artisan make:filament-user                     # every user is an administrator
php artisan mart:sync-towns && php artisan mart:sync-catalog
php artisan test                                   # never calls Mart
```

## Running

Two things must always run, or orders and prices stop updating:

```bash
* * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1   # crontab
php artisan queue:work --timeout=300                                     # supervised
```

Locally, `php artisan schedule:work` replaces the cron line.

## Going live

- `APP_ENV=production`, `APP_DEBUG=false`, HTTPS, `SESSION_SECURE_COOKIE=true`.
- `MART_BASE_URL=https://mart.ps` with the `mk_live_` key. Keys never go in git.
- Behind a proxy or CDN, configure trusted proxies, or the per-IP order limit sees one address.
- Confirm with Mart that you may sell through your own website.
- In `/admin` settings: set your markup (starts at 20%), store name, contacts and policy pages.
- Back up the database and `storage/app/public`.

## How an order moves

Customer confirms → **awaiting review** → you approve → sent to Mart (retried safely until Mart answers). Badges on an order mean: *Unverified*, Mart could not be reached at checkout; *Mart total differs*, call the customer or cancel; *Check in Mart*, Mart already has that reference.
