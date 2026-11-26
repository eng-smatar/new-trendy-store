# Implementation Plan: Public Reseller Storefront with Admin Panel

**Branch**: `001-reseller-storefront` | **Date**: 2026-11-25 | **Spec**: [spec.md](spec.md)

## Summary

One Laravel application: an Arabic RTL storefront, a bilingual Filament admin panel, and a scheduled sync with the Mart Partner API. Orders are held locally until an administrator approves them, then sent to Mart idempotently. Decisions: [research.md](research.md).

## Technical Context

**Language/Version**: PHP 8.3 (installed 8.3.37)

**Primary Dependencies**: Laravel 13, Filament 5 (admin), Livewire 4 (storefront interactivity, ships with Filament), Tailwind CSS via Vite

**Storage**: MySQL; sessions and cache on the database driver; queues on Redis with Horizon; uploads on the public disk; product images linked from Mart

**Testing**: PHPUnit (Laravel default) with `Http::fake`; manual sandbox run per [quickstart.md](quickstart.md)

**Target Platform**: Any PHP host with cron and one long-running queue worker; local development on Herd

**Project Type**: Server-rendered web application

**Performance Goals**: SC-002 (content within 2.5 s on mobile)

**Constraints**: Mart limits 120 requests/min and 20,000/day; API key server-side only; no duplicate or below-cost orders

**Scale/Scope**: ~19,000 products, 18 districts, 577 towns, one administrator role, low order volume

## Constitution Check

`.specify/memory/constitution.md` is the unfilled template, so there are no gates. Re-checked after design: unchanged.

## Project Structure

### Documentation

```text
specs/001-reseller-storefront/
├── spec.md, plan.md, research.md, data-model.md, quickstart.md, shipping.md
├── contracts/   # mart-api.md, web-routes.md
└── tasks.md     # from /speckit-tasks
```

### Source Code (repository root, standard Laravel layout)

```text
app/
├── Models/            # Product, Category, Town, Order, OrderItem, Setting, User
├── Services/Mart/     # MartClient (the only code that calls Mart)
├── Services/          # Pricing, Cart, Checkout, ArabicText
├── Jobs/              # SubmitOrderToMart
├── Console/Commands/  # mart:sync-catalog, mart:sync-towns, mart:sync-orders
├── Livewire/          # catalogue, product, cart, checkout, order tracking
└── Filament/          # Resources (products, orders), Settings page, widgets
config/services.php    # mart.base_url, mart.key from .env
database/              # migrations, factories
lang/ar, lang/en
resources/views/       # storefront layout and pages (dir="rtl")
tests/Feature, tests/Unit
```

**Structure Decision**: A single application with no separate API or front-end project. No repository layer or extra packages for settings, cart or translation.

**Setup note**: The root is not empty (`.specify/`, `specs/`, `.env`), so create the Laravel skeleton in a temporary directory and copy it in, merging `.env` and `.gitignore` without overwriting the existing Mart values.

## Delivery order

1. Skeleton, Mart client, catalogue and town sync, pricing (US1 data)
2. Storefront browse and search (US1)
3. Cart, checkout, order review and submission (US2 + US3 scenarios 5-7). **First launchable point**
4. Rest of admin, settings, admin languages (US3, US5)
5. Order tracking and dashboard (US4)

## Complexity Tracking

No violations.
