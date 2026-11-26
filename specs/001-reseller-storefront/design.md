# Visual direction (FR-028)

Calm, product-first, phone-first, right-to-left. Photos carry the colour; the interface stays neutral.

## Tokens (`resources/css/app.css`)
- One accent: the admin's `main_color` (default `#3f2878`, the logo purple) for the primary button, links, focus and the one brand band; `brand-deep` for the footer. The logo's orange appears only in the logo.
- Neutrals: `ink`, `muted`, `line`, `soft`; the page is white.
- Type: IBM Plex Sans Arabic. One scale: `title-1`, `title-2`, `title-3`, body 16px, nothing under 14px.
- Edges: every block sits in `wrap` (1280px content at 1440; 24 / 48 / 80px gutters).
- Buttons: one pair, `.btn-primary` and `.btn-ghost`, 44px, `rounded-xl`. `.link` is the one text link; `.chip` for filters and options.

## Layouts
- **Header**: logo, shop links (desktop), search, cart. Sticky, one hairline.
- **Home**: headline and fact, slider of random products, category tiles with photos, featured, new arrivals, "how an order works" band.
- **Product**: large gallery beside name, price, options (only what differs on the buttons), buy row, facts; then description and related.
- **Cart and checkout**: list or form with a summary column; one column on phones.

## States
Loading: dimmed content with `wire:loading`. Empty: one sentence and a link onward. Error: plain Arabic message beside the field or at the top, announced to screen readers.

## Motion and interaction
Short and quiet; all movement is off under "reduce motion". It answers the visitor or happens once on load, never because the page scrolled: page cross-fade, hero entrance, card lift and image zoom (pointer devices), button press, cart count bump, "added" toast, quantity steppers, sticky add-to-cart bar on phones. CSS and the Alpine bundled with Livewire; no animation library.

