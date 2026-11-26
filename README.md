# Trendy Windy store

Arabic storefront that resells the Mart catalogue with cash on delivery, with an Arabic/English admin panel at `/admin`. Design documents: `specs/001-reseller-storefront/`.

## Setup

Needs PHP 8.3 (with `redis` and `pcntl`), Composer, Node 24, MySQL 8 and Redis.

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
php artisan horizon                                                      # supervised
```

Locally, `php artisan schedule:work` replaces the cron line.

Queued jobs run on Redis through [Horizon](https://laravel.com/docs/horizon); its dashboard is at `/horizon` (open locally, admin sign-in required elsewhere).

## Going live

- `APP_ENV=production`, `APP_DEBUG=false`, HTTPS with `FORCE_HTTPS=true` (links and assets use https; let the web server redirect http requests), `SESSION_SECURE_COOKIE=true`.
- Set the `MAIL_*` values to a real mail server and `APP_URL` to the public address: every admin is emailed about each new order, at the address in their profile.
- "Forgot password?" on `/admin/login` needs the mail server and Horizon. If mail is broken, `php artisan make:filament-user` creates another admin.
- The app only answers on `APP_URL`'s host (and its subdomains).
- Keep Redis private and persistent (AOF), so queued jobs survive a restart.
- On a server that used the database queue, let it empty before switching.
- `MART_BASE_URL=https://mart.ps` with the `mk_live_` key. Keys never go in git.
- Behind a proxy or CDN, configure trusted proxies, or the per-IP order limit sees one address.
- Confirm with Mart that you may sell through your own website.
- In `/admin` settings: set your markup (starts at 20%), store name, contacts and policy pages.
- Back up the database and `storage/app/public`.

## Updating

```bash
git pull && composer install --no-dev --optimize-autoloader && npm ci && npm run build
php artisan migrate --force && php artisan optimize
php artisan horizon:terminate   # the process manager restarts it on the new code
```

## How an order moves

Customer confirms → **awaiting review** → you approve → sent to Mart (retried safely until Mart answers). Badges on an order mean: *Unverified*, Mart could not be reached at checkout; *Mart total differs*, call the customer or cancel; *Check in Mart*, Mart already has that reference.
