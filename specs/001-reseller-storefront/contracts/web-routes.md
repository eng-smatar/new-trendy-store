# Contract: public routes (Arabic, RTL, no accounts)

| Route | Content | Rules |
|---|---|---|
| `/` | banners, categories, featured, new arrivals | |
| `/categories/{id}` | listing with filters and sort | query string holds filters, shareable |
| `/search?q=` | results | same filters |
| `/products/{id}` | gallery, options, add to cart, related | 404 if not visible |
| `/cart` | edit lines | unavailable lines flagged |
| `/checkout` | address form, fee, total, confirm | closed message when ordering is off; CSRF; max 3 orders/hour per phone and 30 per IP |
| `/orders/{reference}/done` | confirmation, "awaiting review" | only for the session that placed it |
| `/track` | reference + phone | same message for any mismatch; max 10 tries/min per IP |
| `/pages/{about\\|delivery\\|privacy\\|terms}` | settings content | |
| `/admin` | Filament panel | login required, no registration |
