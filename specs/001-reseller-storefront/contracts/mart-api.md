# Contract: Mart Partner API (consumed)

Base `{MART_BASE_URL}/api/v1`, `Authorization: Bearer {MART_API_KEY}`. Envelope: `status, status_code, message, data, meta, error`. Branch on `error.code`. Full schema: https://docs.mart.ps/openapi.json

| Call | Used for |
|---|---|
| `GET /account` | permissions (orders, price override, free shipping) shown in admin |
| `GET /products?page&limit=1000&include=description` | catalogue sync until `meta.pagination.has_next` is false |
| `GET /categories` | category sync |
| `GET /states` | cities (= districts) → states (= towns) with fee brackets |
| `POST /orders/validate` | quote at checkout and at approval |
| `POST /orders` | create after approval |
| `GET /orders?updated_since&order=asc&limit=100` | find changes |
| `GET /orders/{ref}` | status and `money` |
| `POST /orders/cancel/{ref}` | cancel |
| `POST /orders/notes/{ref}` | admin note to Mart (≤1000 chars, ≤20 per order) |

## Order body

```json
{
  "payment_type": "cod",
  "partner_reference": "NT-…",
  "shipping_address": {"customer_name": "", "phone": "", "state_id": 0, "address": "", "notes": ""},
  "products": [{"product_id": 0, "combination_id": 0, "quantity": 1, "price": 0.00}],
  "free_shipping": false
}
```

Omit `combination_id` for products without combinations. Always send `price` (omitting it earns nothing).

## Responses to handle

- Quote: `data.validation.quote` → `products_total, shipping_fee, free_shipping, cod_total, reward{gross, shipping_deduction, net}`. Docs say `quote` is null when the reference already names the same order.
- Create: `data.order`; `meta.idempotency_replay: true` is success; `409 idempotency_conflict` is a refusal needing a person.
- `429`: wait `Retry-After`. Timeout or 5xx: resend the identical body.
- Use a 60 s timeout on create.
