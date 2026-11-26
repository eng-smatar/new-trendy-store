# Data Model

Money: `decimal(10,2)` ILS. Mart ids are kept as given.

## categories
`id` (Mart category_id), `name` (full path), `parent_path` (nullable), `gender`, `name_ar` (nullable, admin's own; never overwritten by sync). Shown name: `name_ar`, else `lang/ar/categories.php` by last path segment, else Mart's English.

## products
`id` (Mart product_id), `reference`, `name`, `description`, `description_short`, `category_id?`, `brand_name?`, `cost_price` (group_price), `price_override?`, `selling_price`, `images` (json), `cover_image`, `combinations` (json: id, available_quantity, attributes, images), `total_quantity`, `search_text`, `mart_active`, `hidden` (default false), `featured` (default false), `removed_at?`, `synced_at`.

- Listed to shoppers (FR-030): sellable, plus `selling_price - cost_price >= listing_min_profit` and, when `listing_categories` is not empty, a category in those branches.
- Sellable: `mart_active` and not `hidden` and `removed_at` null and `selling_price > 0`. Orderable: visible and the chosen quantity is in stock.
- Index: visibility columns, `category_id`, `selling_price`, `featured`.
- Sync never overwrites `price_override`, `hidden`, `featured`.
- `price_override >= cost_price` on save; if cost later rises above it, `selling_price = cost_price`.
- Products with combinations require a `combination_id` on order lines.

## towns
`id` (Mart state_id), `district_id`, `district_name`, `name`, `fees` (json brackets), `available`.

- Fee: bracket where `from_total <= subtotal < to_total` (null = no upper end).
- Zone from `district_id`: 248 `Jerusalem`; 256-262 `Isreal`; otherwise `default`.

## orders
`id`, `reference` (unique, random, ≤64, sent as `partner_reference`), `first_name`, `last_name`, `phone`, `email?`, `gender`, `town_id`, `district_name`, `town_name`, `address_line1`, `notes?`, `admin_note?`, `status`, `products_total`, `shipping_fee` (Mart fee), `free_shipping`, `customer_total`, `expected_net`, `quote_verified`, `attention_reason?`, `send_attempts`, `next_attempt_at?`, `request_body?` (json, frozen at approval), `last_error?`, `mart_status_id?`, `mart_status_name?`, `earning_state?`, `net_earning?`, `approved_by?`, `approved_at?`, `submitted_at?`.

Validation: FR-023. `customer_total = products_total + (free_shipping ? 0 : shipping_fee)`.

| From | To | When |
|---|---|---|
| pending_review | rejected | admin rejects |
| pending_review | approved | admin approves after a matching or accepted quote |
| approved | pending_review | admin stops retrying and `GET /orders/{ref}` answers `order_not_found` |
| send_failed (conflict) | submitted | admin checks Mart and links the existing order |
| pending_review | submitted | sync finds the reference in Mart |
| approved | submitted | Mart returns the order (including idempotent replay) |
| approved | send_failed | Mart refuses (4xx other than 429) |
| send_failed | approved | admin approves again (not after `idempotency_conflict`) |
| send_failed | rejected | admin rejects |
| submitted | cancelled | Mart confirms cancellation, or sync reports `cancellation.cancelled_at` |

Timeouts, 5xx and 429 leave the order `approved` and retry. `request_body` changes only after a definite refusal or a withdrawal confirmed absent from Mart. `attention_reason` is set for a total mismatch, an idempotency conflict, or an order found in Mart that was not marked submitted; the owner can clear the first and last (`mart_total`, Mart's last reported total, keeps a cleared mismatch quiet until it changes). A Mart cancellation clears any warning.

## order_items
`order_id`, `product_id`, `combination_id?`, `name`, `options_label?`, `quantity`, `unit_price`, `unit_cost`.

## settings
`key` (unique), `value` (json). Keys cover FR-020: identity, contact, banners, pages, SEO, ordering (enabled, closed message, minimum amount, markup percent), shipping (free_from per zone: 250 / 500 / 1000, estimate text "2-5 days").

## users
Laravel default plus `locale` (`ar` default, or `en`). Every user is an administrator; created by command only, no registration.
