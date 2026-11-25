# Tasks: Public Reseller Storefront with Admin Panel

**Input**: `specs/001-reseller-storefront/` (plan.md, spec.md, research.md, data-model.md, contracts/, shipping.md, quickstart.md)

**Tests**: Not requested as TDD. Small PHPUnit tests are included only for money, sync, order submission and access rules. All use `Http::fake`; no test calls Mart.

**Rules for every task**: storefront text is Arabic via `lang/ar`, layout RTL; money is `decimal(10,2)` ILS; never print or commit `MART_API_KEY`; `SHIPPING_ADDRESS.md` supplies form fields and labels, but towns and fees come from Mart; every admin label uses a translation key in `lang/ar/admin.php` (with `lang/en/admin.php`) from the first admin task.

## Phase 1: Setup

- [ ] T001 Create a Laravel 13 skeleton in a temporary directory and copy it into the repository root without overwriting `.env` values `MART_BASE_URL`/`MART_API_KEY`, `.gitignore` entries, `.specify/`, `.claude/`, `specs/` or `SHIPPING_ADDRESS.md`; add the two keys with empty values to `.env.example`
- [ ] T002 Configure MySQL, database drivers for queue, session and cache, `APP_LOCALE=ar`, `APP_TIMEZONE=Asia/Hebron` in `.env`, `.env.example` and `config/app.php`; run `php artisan storage:link`
- [ ] T003 Require `filament/filament:^5` in `composer.json` and install the panel in `app/Providers/Filament/AdminPanelProvider.php` (path `/admin`, login only, no registration)
- [ ] T004 [P] Add `mart.base_url` and `mart.key` to `config/services.php`
- [ ] T005 [P] Set up Tailwind with a main-colour CSS variable and a self-hosted Arabic font in `resources/css/app.css` and `vite.config.js`

## Phase 2: Foundational (blocks all stories)

- [ ] T006 Create `app/Services/Mart/MartClient.php` and `MartException.php` per `contracts/mart-api.md`: bearer auth, envelope unwrap, `error.code` and `details` on the exception, 60 s timeout, distinguish refusal (4xx), rate limit (429 with `Retry-After`) and unknown outcome (timeout, 5xx); never log the key
- [ ] T007 [P] Create `settings` migration (`key` unique, `value` json) and `app/Models/Setting.php` with `get($key)` falling back to defaults in `config/store.php`: store name, ordering enabled true, closed message, minimum order amount 0, markup percent 20, free_from `default` 250 / `Jerusalem` 500 / `Isreal` 1000, delivery estimate "2-5 days" in Arabic
- [ ] T008 [P] Add `locale` (`ar` default, or `en`) to the users migration; make `app/Models/User.php` implement `FilamentUser` (every user is an administrator, created by command only)
- [ ] T009 Define the visual direction (FR-028) in `specs/001-reseller-storefront/design.md`: colour tokens built on the admin-set main colour, Arabic type scale, spacing, buttons, fields, product card, and phone-first layouts for home, listing, product, cart and checkout with loading, empty and error states; apply tokens in `resources/css/app.css`
- [ ] T010 Create the RTL storefront layout in `resources/views/components/layouts/store.blade.php` with header, search box, cart count, footer, skip link, visible focus, and store name and colour from `Setting`
- [ ] T011 [P] Test in `tests/Unit/MartClientTest.php`: refusal, 429 and 5xx are told apart; envelope is unwrapped

**Checkpoint**: app boots, `/admin` login works, Mart client tested.

## Phase 3: US1 Browse and find products (P1)

**Independent test**: after syncing, any visible product is reachable by category or search with correct price and stock.

- [ ] T012 [P] [US1] Migration and `app/Models/Category.php`: `id` = Mart category_id, `name` (full path), `parent_path` nullable (split on `>`), `gender`
- [ ] T013 [P] [US1] Migration and `app/Models/Product.php` with every field in `data-model.md` "products" (`id` = Mart product_id, `price_override` nullable, `hidden` default false, `featured` default false, `removed_at` nullable, `images`/`combinations` json), indexes, and a `visible` scope: `mart_active` and not `hidden` and `removed_at` null
- [ ] T014 [P] [US1] `app/Services/Pricing.php`: `selling = price_override ?? round(cost × (1 + markup%), 2)`, never below cost
- [ ] T015 [P] [US1] `app/Services/ArabicText.php`: strip diacritics, unify أ إ آ to ا, ة to ه, ى to ي, lowercase
- [ ] T016 [US1] `app/Console/Commands/SyncCatalog.php` (`mart:sync-catalog`): categories, then `GET /products?limit=1000&include=description` until `has_next` is false; upsert by id without touching `price_override`, `hidden`, `featured`; set selling price and `search_text`; set `removed_at` only after every page succeeded, clear it on return; wait on 429; record last success and last error in settings
- [ ] T017 [US1] Schedule `mart:sync-catalog` every 6 hours without overlapping in `routes/console.php`
- [ ] T018 [US1] Home page in `app/Livewire/Home.php`: categories, featured, new arrivals (highest Mart product id first); route `/`
- [ ] T019 [US1] Listing and search in `app/Livewire/Catalog.php`: routes `/categories/{id}` (includes subcategories) and `/search?q=`; one `LIKE` per normalised word; filters category, price, availability; sort; filters kept in the query string; pagination; loading and empty states
- [ ] T020 [US1] Product page in `app/Livewire/ProductPage.php`, route `/products/{id}`: gallery, combination picker that updates images and stock, out-of-stock not addable, related in-stock products from the same category, 404 if not visible, `description` and `description_short` sanitised before output
- [ ] T021 [P] [US1] Product card, lazy images with alt text and Arabic error pages in `resources/views/components/` and `resources/views/errors/`
- [ ] T022 [P] [US1] Tests in `tests/Feature/CatalogTest.php`: never below cost; sync keeps admin fields; failed page removes nothing; search matches with and without hamza; hidden product 404; script in description is removed

**Checkpoint**: the catalogue is browsable.

## Phase 4: US2 Order with cash on delivery (P1)

**Independent test**: a customer places an order; it is stored as awaiting review with the shown totals and nothing is created in Mart.

- [ ] T023 [P] [US2] Migration and `app/Models/Town.php`: `id` = Mart state_id, `district_id`, `district_name`, `name`, `fees` json, `available`; `fee($subtotal)` uses `from_total <= subtotal < to_total` (null = no upper end); `zone()`: district 248 `Jerusalem`, 256-262 `Isreal`, otherwise `default`
- [ ] T024 [US2] `app/Console/Commands/SyncTowns.php` (`mart:sync-towns`) from `GET /states` (Mart city = district, state = town), missing towns become unavailable; schedule daily in `routes/console.php`
- [ ] T025 [P] [US2] Migrations, `app/Models/Order.php`, `app/Models/OrderItem.php` and `app/Enums/OrderStatus.php` (pending_review, rejected, approved, submitted, send_failed, cancelled) with every field in `data-model.md`; `reference` unique, random, unguessable, ≤64 characters of letters, digits, dot, underscore, hyphen
- [ ] T026 [US2] `app/Services/Cart.php` (session) and `app/Livewire/CartPage.php`, route `/cart`: add, change quantity, remove, flag unavailable lines; add-to-cart wired from `ProductPage`
- [ ] T027 [US2] `app/Services/Checkout.php`: build the Mart order body (always send `price`; `combination_id` only for products with combinations; `customer_name` = first + last; `state_id` = town); free shipping when subtotal ≥ zone free_from AND margin ≥ fee AND `GET /account` allows it; quote with `POST /orders/validate`; if Mart is unreachable use local figures with `quote_verified=false` (FR-007); `customer_total = products_total + (free_shipping ? 0 : shipping_fee)`
- [ ] T028 [US2] `app/Livewire/CheckoutPage.php`, route `/checkout`, validating: `first_name` required max 100; `last_name` required max 100; `phone` required max 40 (LTR); `email` optional, valid, max 255; `gender` required `male` or `female`; district required; town required, available and inside the district; `address_line1` required max 255; `notes` optional max 1000. Town disabled until a district is chosen and cleared when it changes; fee, total and estimate shown before confirm; changed figures require a second confirm; minimum amount and ordering-off enforced; max 3 orders/hour per phone and 10 per IP; double click creates one order
- [ ] T029 [US2] Confirmation page `/orders/{reference}/done`, only for the placing session, saying the store will review before dispatch, in `routes/web.php` and `resources/views/order-done.blade.php`
- [ ] T030 [P] [US2] Tests in `tests/Feature/CheckoutTest.php`: bracket boundary; free only when margin covers; town outside district rejected; order stored with snapshot and no `POST /orders` sent; rate limit

**Checkpoint**: orders are received. Not launchable until Phase 5A.

## Phase 5: US3 Manage the store (P2)

**Independent test**: approving an order creates exactly one Mart order; markup and visibility changes show on the public store.

### 5A: Order review (needed for launch)

- [ ] T031 [US3] `app/Jobs/SubmitOrderToMart.php`: unique per order; send stored `request_body` unchanged to `POST /orders`; success or `meta.idempotency_replay` → submitted, and if the created `cod_total` differs from `customer_total` set `attention_reason`; `409 idempotency_conflict` → send_failed with `attention_reason`; other 4xx except 429 → send_failed with `last_error`; timeout, 5xx, 429 → stay approved and retry with backoff honouring `Retry-After`; schedule a re-dispatch of stale approved orders in `routes/console.php`
- [ ] T032 [US3] `app/Services/OrderApproval.php`: keep agreed unit prices (a later markup change does not alter them); reprice is required only if a unit price < current cost, free delivery is no longer covered, Mart refuses, or the quoted `cod_total` differs from `customer_total`; admin may then set unit prices ≥ cost or drop free delivery, and must confirm the customer accepts before saving. Freeze `request_body`, `approved_by`, `approved_at`; allow only transitions in `data-model.md`; reject never sends. Stop retrying: take the job's lock, `GET /orders/{ref}`; found → submitted, `order_not_found` → pending_review. After a conflict, block re-approval and offer "check in Mart"
- [ ] T033 [US3] `app/Filament/Resources/Orders/OrderResource.php`: list with pending-count badge, view with expected earnings and `last_error`, badges for unverified (`quote_verified=false`) and `attention_reason`, actions approve, reject, stop retrying, check in Mart, cancel (`POST /orders/cancel/{ref}`), admin note (optionally `POST /orders/notes/{ref}`, ≤1000 characters)
- [ ] T034 [US3] `app/Console/Commands/SyncOrders.php` (`mart:sync-orders`): `GET /orders?updated_since&order=asc` with overlap, then `GET /orders/{ref}` for each; store status id and name, totals, `earning_state`, net; set cancelled when `cancellation.cancelled_at` is present; an order found in Mart but not marked submitted becomes submitted with `attention_reason`; every 5 minutes in `routes/console.php`
- [ ] T035 [P] [US3] Tests in `tests/Feature/OrderApprovalTest.php`: retry sends identical body once-only result; replay is success; refusal → send_failed; changed totals or price below cost block sending; markup change keeps agreed price; created total mismatch is flagged; Mart cancellation sets cancelled; rejected never sent

### 5B: Catalogue and pricing

- [ ] T036 [US3] `app/Filament/Resources/Products/ProductResource.php`: search, show/hide, feature, price override rejected below cost with an explanation
- [ ] T037 [US3] `app/Filament/Pages/ManageSettings.php` with Ordering (ordering on/off, closed message, minimum amount, markup %) and Shipping (free_from per zone, min 0; estimate text) tabs, validated; changing markup recalculates selling prices
- [ ] T038 [P] [US3] "Refresh now" action (queued) and a widget with last success, last failure and a warning when data is older than 24 hours in `app/Filament/Widgets/SyncStatus.php`
- [ ] T039 [P] [US3] Test in `tests/Feature/AdminAccessTest.php`: guests cannot reach any admin page; below-cost override rejected

**Checkpoint (MVP)**: browse, order, review, send.

## Phase 6: US5 Configure the website (P2)

**Independent test**: changing name, logo and phone appears on the public store; the panel works in Arabic and English.

- [ ] T040 [US5] Add to `app/Filament/Pages/ManageSettings.php` the tabs Identity (name, logo, icon, colour), Contact (phone, WhatsApp, email, address, social links), Home (banners with links, announcement), Pages (about, delivery and returns, privacy, terms) and SEO (title, description, sharing image). Validate all; links http/https only; images raster, max 2 MB, no SVG
- [ ] T041 [US5] Apply settings in `resources/views/components/layouts/store.blade.php` and `app/Livewire/Home.php`: logo, icon, colour, contact, banners, announcement, meta tags
- [ ] T042 [P] [US5] Content pages `/pages/{about|delivery|privacy|terms}` with sanitised HTML in `routes/web.php` and `resources/views/page.blade.php`
- [ ] T043 [US5] Admin languages: `app/Http/Middleware/SetAdminLocale.php` applying `users.locale`, a user-menu switch in `AdminPanelProvider.php`, and check no admin string is missing from `lang/ar/admin.php` or `lang/en/admin.php`
- [ ] T044 [P] [US5] Tests in `tests/Feature/SettingsTest.php`: invalid upload keeps old value; ordering off blocks checkout; locale persists and sets direction

## Phase 7: US4 Track an order and see performance (P3)

**Independent test**: look up an order publicly; dashboard totals match the order list.

- [ ] T045 [P] [US4] `app/Livewire/TrackOrder.php`, route `/track`: reference plus phone, one generic message for any mismatch, 10 tries/min per IP, status in Arabic
- [ ] T046 [P] [US4] Dashboard widgets in `app/Filament/Widgets/`: orders, sales value, expected and delivered earnings, top products, for a chosen period; rejected and cancelled (including cancelled by Mart) excluded
- [ ] T047 [P] [US4] Tests in `tests/Feature/TrackingTest.php`: wrong phone reveals nothing; totals match

## Phase 8: Polish

- [ ] T048 [P] Check every page at 360 px, by keyboard, and for contrast; fix in `resources/views/`
- [ ] T049 [P] Check main-page load against 2.5 s with the full catalogue; add missing indexes in `database/migrations/`
- [ ] T050 [P] Write setup and deployment (cron, queue worker, MySQL 8, live key) in `README.md`
- [ ] T051 Run all scenarios in `quickstart.md` against the sandbox, then cancel test orders; record Mart status names and map them to Arabic labels in `lang/ar/orders.php`

## Dependencies

Setup → Foundational → US1 → US2 → US3 → US5 and US4 (either order) → Polish.

- US2 needs products (US1). US3 order review needs orders (US2). US3 5B needs only US1.
- US5 and US4 need the panel; US4 also needs orders.

## Parallel examples

- US1: T012, T013, T014, T015
- US2: T023, T025
- US3: T038, T039
- US5: T042, T044
- US4: T045, T046, T047

## Implementation strategy

MVP is **Phases 1-5** (US1, US2, US3). US1 alone is a demo, not a store. Stop at each checkpoint and verify before continuing, then add US5, US4 and polish.
