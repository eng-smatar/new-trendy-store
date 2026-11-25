# Research (2026-11-25)

Sources: docs.mart.ps and its `openapi.json`, read-only and dry-run calls to the sandbox, Packagist, Filament docs.

## Stack
- **Decision**: Laravel 13.44, Filament 5.12, Livewire 4.4, on PHP 8.3.
- **Rationale**: Current releases, all support PHP 8.3. Filament gives tables, forms, login and Arabic/English translations; its `ar` locale sets RTL.
- **Rejected**: Hand-built admin (more code); Inertia/Vue or Next.js storefront (second front-end stack); Pest 5 (needs PHP 8.4).

## Database
- **Decision**: MySQL 8. The local server is 8.0.27 on port 3307 (the 5.7 seen earlier was only the command-line client).
- **Rejected**: SQLite (web, queue and sync write concurrently).

## Catalogue sync
- **Decision**: Full sync every 6 hours: `GET /products?limit=1000&include=description`, then mark products not seen as removed. Remove only after every page succeeded.
- **Rationale**: ~19,000 products is about 20 requests. A full pass is the only way Mart exposes price changes, so incremental sync adds nothing.
- **Rejected**: `updated_after` sync; per-product detail calls; events feed.

## Order status
- **Decision**: Every 5 minutes `GET /orders?updated_since=…&order=asc`, then `GET /orders/{ref}` for each changed one (status and earnings). Overlap the window and de-duplicate by reference.
- **Rejected**: Events feed (cursor handling for the same result). No webhooks exist.

## Search
- **Decision**: A normalised `search_text` column (strip Arabic diacritics; unify أ إ آ to ا, ة to ه, ى to ي; lowercase) matched with one `LIKE` per word.
- **Rationale**: Adequate for ~19,000 rows, no search service. If searches get slow, move to a full-text engine.

## Pricing
- **Decision**: `selling = override ?? round(cost × (1 + markup%), 2)`, never below cost; stored on the product and recalculated on sync or markup change. Initial markup 20% (owner to set).
- **Rationale**: `public_price` never exceeded `group_price` in 500 sampled products, so it cannot serve as a retail price.

## Checkout and submission
- **Decision**: Quote with `POST /orders/validate` before confirmation; store the order as `pending_review`. On approval keep the agreed unit prices while each is ≥ current cost and validate again; require new figures and customer acceptance only if that fails or the customer's total differs. Store the exact request body, then a queued job creates it, resending the same body and `partner_reference` on timeout or 5xx and honouring `Retry-After` on 429. Compare the created total with the accepted one.
- **Mart unreachable at checkout**: accept with the locally computed total, flagged unverified; approval validates it anyway.
- **Free delivery**: `free_shipping: true` when subtotal ≥ zone threshold, margin ≥ fee, and the account allows it. A dry run confirmed the reward arithmetic.

## Untrusted content
- **Decision**: Product descriptions are HTML from Mart: sanitise before output. Same for admin rich text. Image uploads: raster only, size-limited, no SVG.

## Settings, cart, admin language
- **Decision**: `settings` key/value table with one Filament page; cart in the session; `users.locale` applied by panel middleware.
- **Rejected**: Settings, cart and language-switcher packages.

## Open (not blocking)
- Category names are English paths such as `clothing & accessories > clothing`. Plan: build the tree by splitting on `>`, show as given. An optional Arabic name per category would be a small addition.
- Production data and limits not checked; Mart's permission to sell from an own website not confirmed.
- Sandbox run during implementation: order reads return Arabic status names (for example `انتظار التأكيد هاتفيا`, `ملغي`), so they are shown as received. The create response has only the English word `processing`, which is not stored.
