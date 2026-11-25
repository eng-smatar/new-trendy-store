# Visual direction (FR-028)

Calm, product-first, phone-first, right-to-left. Photos carry the colour; the interface stays neutral.

## Tokens (`resources/css/app.css`)
- Brand: admin-set `main_color` (default `#3f2878`), used for primary buttons, links, active states and focus. White text sits on it, so it must be dark (settings reject light colours).
- Neutrals: Tailwind `stone`; page `stone-50`, cards white with a `stone-200` border.
- Type: IBM Plex Sans Arabic 400/500/700. Body 16px (no smaller on inputs), titles 20-24px, price bold.
- Shape: cards `rounded-2xl`, controls `rounded-xl`, touch targets at least 44px.
- Spacing: 16px page gutter, 12px grid gap on phones, 16px from `sm`; content max width 1152px.

## Components
`.btn-primary`, `.btn-ghost`, `.field`, `.label`, `.error`, `.card`, `.rich`.
Product card: square image, two-line name, price, "out of stock" badge.

## Layouts (phone first)
- **Header**: store name or logo, search, cart with count.
- **Home**: announcement, banner, category chips, featured grid, new arrivals grid.
- **Listing**: title, filters in a disclosure, 2 columns (3 `sm`, 4 `lg`), pager.
- **Product**: gallery, name, price, options, add button, description, related.
- **Cart**: line list, subtotal, continue button.
- **Checkout**: one column: details, address, summary, confirm.

## States
Loading: dimmed content with `wire:loading`. Empty: one sentence and a link onward. Error: plain Arabic message beside the field or at the top, announced to screen readers.

## Motion and interaction
Short and quiet; all movement is off under "reduce motion". Page cross-fade, card lift and image zoom (pointer devices), products rising in on scroll, button press, cart count bump, "added" toast, quantity steppers, sticky add-to-cart bar on phones, back-to-top. CSS and the Alpine bundled with Livewire; no animation library.

