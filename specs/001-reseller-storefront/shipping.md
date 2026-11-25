# Shipping: source file vs Mart (sandbox, 2026-11-25)

Source: `../../SHIPPING_ADDRESS.md`. Mart: `GET /api/v1/states` and the order schema in `openapi.json`.

## Structure

Same two levels and, for shared rows, the same ids. Mart `city` = district; Mart `state` = town.

| | File | Mart |
|---|---|---|
| Districts | 18 | 18 (same ids and names) |
| Towns | 576 | 577 |

- Only in file (Mart lists none of these ids): 800 ابو غوش, 936 عسفيا, 937 ياصيد, 938 ام بطين, 939 الحويطات, 940 أبو تلول.
- Only in Mart (district 249): 562, 563, 570, 581, 593, 610, 626.
- Town 509: district 249 in file, 246 in Mart.
- Spelling differs for 628, 633, 653, 675, 688, 758, 767.

Use Mart's data.

## Fees

| Districts | File fee / free from | Mart fee |
|---|---|---|
| 256-262 (`Isreal`) | 65 / 1000 | 65 |
| 248 (`Jerusalem`) | 30 / 500 | 30 for 24 towns, 20 for 21 |
| others (`default`) | 20 / 250 | 20 |

The order request has no shipping fee field; only `free_shipping` (true/false). So fees are Mart's, and only the free threshold is store-managed. Mart refuses free shipping when the margin is below the fee (`free_shipping_not_covered`).

## Order field mapping

| Store field | Mart `shipping_address` |
|---|---|
| first_name + last_name | `customer_name` |
| phone | `phone` |
| town id | `state_id` |
| address_line1 | `address` |
| notes | `notes` (courier instructions) |
| email, gender, district name, town name | not sent; kept in the store |

Mart also accepts an optional `extra_phone`; not collected.
