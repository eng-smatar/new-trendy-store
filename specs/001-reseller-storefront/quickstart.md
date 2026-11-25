# Quickstart validation

## Setup
- PHP 8.3, Composer, Node 24, MySQL running with an empty database.
- `.env`: database settings, `MART_BASE_URL=https://sandbox.mart.ps`, `MART_API_KEY` (already present; never commit).

```bash
composer install && npm install && npm run build
php artisan migrate && php artisan storage:link
php artisan make:filament-user
php artisan mart:sync-towns && php artisan mart:sync-catalog   # the scheduler adds a quick pass every 15 min
php artisan queue:work      # separate terminal
php artisan schedule:work   # separate terminal
php artisan test
```

## Scenarios (sandbox only; routes in contracts/web-routes.md)

| # | Do | Expect |
|---|---|---|
| 1 | Search an Arabic word with and without hamza; filter and sort | same matches; only visible products |
| 2 | Checkout: choose district then town (أريحا) | town disabled until district; fee ₪20 |
| 3 | Confirm the order | "awaiting review"; nothing in Mart (`GET /orders`) |
| 4 | Approve in `/admin` | exactly one Mart order with matching `cod_total` |
| 5 | Run the submit job again for it | still one order |
| 6 | Subtotal above the zone threshold, with enough margin; then with 0% markup | free delivery; then normal fee |
| 7 | Override a price below cost | rejected |
| 8 | Switch panel to English and back | direction and labels change, choice persists |
| 9 | Change store name and logo; switch ordering off | visible at once; checkout closed |
| 10 | Track with right and wrong phone | status; generic refusal |
| 11 | Change markup after an order is placed, then approve | agreed price kept |
| 12 | Block Mart, place an order | accepted, shown as unverified |
| 13 | Cancel the test order in `/admin` | cancelled in Mart, excluded from dashboard sales |
