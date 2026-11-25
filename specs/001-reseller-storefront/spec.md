# Feature Specification: Public Reseller Storefront with Admin Panel

**Feature Branch**: `001-reseller-storefront`

**Created**: 2026-11-25

**Status**: Draft

**Input**: User description: "I'm going to build an affliate store for https://docs.mart.ps/ they have mcp tool https://docs.mart.ps/mcp https://docs.mart.ps/developers/connect-your-ai-tool#claude-code, let's build a public intutive smart modern ui/ux store with admin panel, let's use laravel framework"

## Context

Mart's documentation (read 2026-11-25) describes no affiliate links, referral codes or visitor attribution. It describes a partner reseller programme: the partner sells Mart catalogue products at the partner's own price, submits the order to Mart, Mart delivers and collects cash, and the partner earns selling price minus cost price (less any delivery fee the partner covers). The documentation server Mart offers for AI tools helps while building; it holds no store data. The store's data comes from the Mart Partner service.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Browse and find products (Priority: P1)

A visitor opens the store on a phone, explores categories, searches, filters, and views a product with its images, options, price and availability.

**Why this priority**: Nothing can be sold until people can find it.

**Independent Test**: With the catalogue imported, a visitor can reach any offered product through categories or search and see correct price and stock.

**Acceptance Scenarios**:

1. **Given** an imported catalogue, **When** a visitor opens the home page, **Then** they see categories, featured products and new arrivals.
2. **Given** a search term, **When** the visitor searches, **Then** matching visible products appear and can be narrowed by category, price and availability, and sorted.
3. **Given** a product with options, **When** the visitor picks a combination, **Then** images and stock reflect that combination, and out-of-stock combinations cannot be added to the cart.
4. **Given** a product page, **When** it loads, **Then** related in-stock products are suggested.

---

### User Story 2 - Order with cash on delivery (Priority: P1)

A visitor adds items to a cart, enters their name, phone, gender, district, town and address without creating an account, sees the delivery fee and total, and confirms. The store receives the order for review.

**Why this priority**: This is how the store earns. With Story 1 and the order review step of Story 3 it forms the minimum viable product.

**Independent Test**: In Mart's test environment, place an order from the store and confirm it is held for review with the totals shown to the customer and nothing is sent to Mart.

**Acceptance Scenarios**:

1. **Given** items in the cart, **When** the customer picks a town, **Then** the delivery fee, total and estimated delivery of 2 to 5 days are shown before confirmation.
2. **Given** a town that cannot be delivered to, **When** the customer opens the town list, **Then** it is not offered.
3. **Given** a confirmed order, **When** it is received, **Then** the customer sees an order reference and that the store will review the order before dispatch, and nothing is sent to Mart yet.
4. **Given** an approved order and Mart does not answer or fails, **When** the submission is retried, **Then** no duplicate order is created.
5. **Given** price, stock or fee changed since the cart was built, **When** the customer confirms, **Then** they see the new figures and must confirm again.
6. **Given** no district is chosen, **When** the customer reaches the town field, **Then** it is disabled with a prompt to choose a district first; changing the district clears the chosen town.

---

### User Story 3 - Manage the store (Priority: P2)

The owner signs in to an admin panel, sets selling prices through a markup rule with per-product overrides, shows, hides and features products, and follows orders.

**Why this priority**: Controls margin and merchandising. Order review (scenarios 5 to 7) is needed at launch; the rest can follow, running on one default markup.

**Independent Test**: Change the markup and visibility in the panel and confirm the public store reflects it.

**Acceptance Scenarios**:

1. **Given** a signed-in owner, **When** they change the default markup, **Then** store prices update and none falls below cost.
2. **Given** a price override below cost, **When** saved, **Then** it is rejected with an explanation.
3. **Given** a hidden product, **When** a visitor searches or opens its link, **Then** it is not available.
4. **Given** submitted orders, **When** the owner opens the orders list, **Then** each shows status, totals and expected earnings, and eligible orders can be cancelled.
5. **Given** someone not signed in, **When** they open any admin page, **Then** access is refused.
6. **Given** an order awaiting review, **When** the owner approves it, **Then** it is sent to Mart; **When** the owner rejects it, **Then** it is never sent.
7. **Given** an order awaiting review, **When** the owner approves it, **Then** the prices the customer agreed to are kept while they still cover cost; if a price no longer covers cost, free delivery is no longer covered, or the customer's total would change, the order is not sent until the owner sets valid figures and confirms the customer has accepted them.

---

### User Story 4 - Track an order and see performance (Priority: P3)

A customer checks an order's status with its reference and phone number. The owner sees sales, orders and earnings at a glance.

**Why this priority**: Reduces support calls and informs decisions, but selling works without it.

**Independent Test**: Look up a test order publicly and compare dashboard totals with the listed orders.

**Acceptance Scenarios**:

1. **Given** a matching reference and phone, **When** the customer looks up the order, **Then** current status and totals are shown.
2. **Given** a non-matching pair, **When** submitted, **Then** nothing about any order is revealed.
3. **Given** orders in a period, **When** the owner opens the dashboard, **Then** they see order count, sales value, expected and delivered earnings, and top products.

### User Story 5 - Configure the website (Priority: P2)

The owner changes how the website looks and behaves from a settings area in the admin panel, without a developer.

**Why this priority**: The store needs its own name and contact details at launch, and these change over time.

**Independent Test**: Change the store name, logo and phone number in settings and confirm the public store shows them.

**Acceptance Scenarios**:

1. **Given** a signed-in owner, **When** they save the store name, logo or contact details, **Then** the public store shows them.
2. **Given** invalid input, such as a bad email address or an oversized or non-image logo, **When** saved, **Then** it is rejected with an explanation and the previous value stays.
3. **Given** ordering is switched off, **When** a visitor browses, **Then** products remain visible, orders cannot be placed, and the reason is shown.
4. **Given** the admin panel, **When** an administrator switches between Arabic and English, **Then** all panel labels and messages change language and direction, and the choice is remembered.

### Edge Cases

- Mart is unreachable: browsing keeps working from the last known catalogue; new orders are accepted for review and marked unverified, and approved orders are retried safely.
- A cost price rises above the selling price: the selling price is raised to at least cost before the product can be ordered.
- A product leaves the offer or becomes inactive while in a cart: the customer is told and the line cannot be ordered.
- Mart's usage limits are reached: catalogue updates pause and resume without losing orders.
- Mart reduces or cancels an order after submission: the store shows the updated state, and cancelled orders do not count as sales.
- Mart's created total differs from the total the customer accepted: the order is flagged for the owner to contact the customer or cancel.
- An approved order cannot reach Mart: the owner can stop retrying after the store confirms Mart does not have it.
- Mart reports that the order reference is already used for different content: the owner is asked to check it in Mart; it is not resent.
- Repeated or automated fake orders: submissions are limited, because refused parcels can be charged to the store owner.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The store MUST operate as a reseller store with its own catalogue, cart and checkout: it sells at the owner's prices and sends orders to Mart for delivery.
- **FR-002**: The system MUST import Mart products, categories, options, images, stock and delivery destinations, and refresh them automatically; all prices MUST be fully re-read at least daily.
- **FR-003**: Visitors MUST be able to browse by category, search, filter and sort visible products, and view product details with options.
- **FR-004**: The store MUST be mobile-first, usable with keyboard and screen reader, and give clear loading, empty and error states.
- **FR-005**: The storefront MUST be in Arabic only, with a right-to-left layout, including all labels, messages and errors.
- **FR-006**: Visitors MUST be able to manage a cart and order without an account, paying cash on delivery, with amounts in shekels (ILS).
- **FR-007**: The system MUST show Mart's delivery fee for the chosen town (or free delivery when FR-024 applies) and verify the final totals with Mart before the customer confirms. If Mart cannot be reached, the order MUST be accepted with the last known figures and marked unverified for the administrator.
- **FR-008**: A confirmed order MUST be held as awaiting review and sent to Mart only after an administrator approves it; an administrator MUST be able to reject it instead, and rejected orders are never sent.
- **FR-009**: Retries MUST never create a duplicate order.
- **FR-010**: The system MUST validate customer details and limit repeated order submissions.
- **FR-011**: Administrators MUST sign in to reach the admin panel; there is no public admin registration.
- **FR-012**: Administrators MUST be able to set a default markup and per-product prices, never below cost.
- **FR-013**: Administrators MUST be able to show, hide and feature products and trigger a catalogue refresh, seeing when it last succeeded and any failure.
- **FR-014**: Administrators MUST be able to list, view, annotate, approve, reject and (where Mart permits) cancel orders, and see how many await review.
- **FR-015**: Order statuses MUST stay in step with Mart.
- **FR-016**: Customers MUST be able to look up an order using its reference and phone number.
- **FR-017**: Administrators MUST see sales and earnings summaries.
- **FR-018**: Mart access credentials MUST never be exposed to visitors, and customer personal data MUST be visible only to administrators and, for their own order, the customer.
- **FR-019**: The admin panel MUST be available in Arabic (right-to-left) and English (left-to-right), with each administrator's choice remembered.
- **FR-020**: Administrators MUST be able to edit website settings in these groups:
  - Identity: store name, logo, browser icon, main colour.
  - Contact: footer description, phone, WhatsApp number, email, address, social links. The footer also lists shop links and the current delivery fees per zone.
  - Home page: announcement text. The hero slider shows a random selection of in-stock products on each visit.
  - Pages: about, delivery and returns, privacy, terms.
  - Search engines: default page title, description and sharing image.
  - Ordering: ordering on or off with a message, minimum order amount, default markup.
  - Shipping: free-delivery threshold per zone, and the estimated delivery text.
- **FR-021**: Saved settings MUST be validated, take effect without developer help, and have sensible defaults before first use. Storefront text entered in settings is Arabic.
- **FR-022**: Settings MUST NOT display Mart access credentials; these are configured outside the admin panel.
- **FR-023**: The checkout address MUST collect: first name and last name (required, up to 100 characters each), phone (required, up to 40, left-to-right), email (optional, valid, up to 255), gender (required), district (required), town within that district (required), address (required, up to 255) and order notes (optional, up to 1,000).
- **FR-024**: Delivery fees MUST come from Mart per town and are not editable. Administrators set, per zone, an order subtotal from which delivery is free for the customer; the store pays the fee from its margin. Free delivery MUST be offered only when the margin covers the fee; otherwise the normal fee is shown.
- **FR-025**: Zones are Jerusalem district, the seven "48 areas" districts, and all other districts, with starting thresholds of ₪500, ₪1,000 and ₪250.
- **FR-026**: Districts and towns offered MUST be those Mart currently accepts, refreshed at least daily.
- **FR-027**: Each order MUST keep the address, district and town names, the town's identifier, prices and delivery fee as last accepted by the customer; later catalogue or rate changes do not alter it.
- **FR-028**: The storefront's visual direction (colours, type, spacing, components and page layouts for a phone first) MUST be defined before its pages are built and applied consistently.
- **FR-029**: Categories MUST be shown with Arabic names, with built-in defaults that an administrator can change.

### Key Entities

- **Product**: A Mart item with description, images, category, cost price, selling price, visibility and featured flag.
- **Product Option (combination)**: A variant of a product with its attributes, stock and images.
- **Category**: Grouping used for browsing.
- **Delivery Destination**: A town within a district, with Mart's availability and fee.
- **Shipping Zone**: A group of districts with a free-delivery threshold.
- **Order**: Customer details, lines, totals, delivery fee, expected earnings, unique reference and status.
- **Administrator**: A person allowed into the admin panel.
- **Website Settings**: Identity, contact, home page, pages, search engine and ordering values, including default markup, plus the estimated delivery text.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: A first-time visitor on a phone can go from home page to confirmed order in under 3 minutes.
- **SC-002**: Main pages show their content within 2.5 seconds on a typical mobile connection.
- **SC-003**: Zero duplicate orders and zero orders priced below cost.
- **SC-004**: The total the customer last accepted matches what the courier collects in 100% of orders unless Mart later changes the order.
- **SC-005**: Prices and stock shown are never more than 24 hours old; otherwise the owner is warned.
- **SC-006**: The owner can change pricing or visibility and see it live in under 1 minute.
- **SC-007**: At least 9 of 10 test users find a requested product on their first attempt.
- **SC-008**: The owner can change any website setting and see it live in under 1 minute, in either panel language.

## Assumptions

- The owner has, or will obtain, a Mart partner account with test and live access, and Mart permits selling through an own website. The documentation read does not confirm this.
- One store, one Mart account, one administrator role in the first version.
- "Smart" means good search, filtering, related and featured products; no AI assistant or personalised recommendations in the first version.
- Product text is shown as Mart provides it, in whatever language that is; the store does not translate it.
- The owner contacts customers by phone outside the system; the store sends no SMS or messaging in the first version.
- SC-001 measures the time to a received order, not to dispatch.
- Out of scope for the first version: online card payment, customer accounts, wishlists, reviews, coupons, marketing messages, and payouts or withdrawals (done in Mart's partner panel).
- Technical choice for planning: the user requires the Laravel framework.
- Observed in the Mart test environment (2026-11-25): about 19,000 products, about 15,300 active and in stock; most sampled product names are Arabic (352 of 500), category names are English, descriptions are formatted text; Mart's public price never exceeded cost in the sample, so margin comes from the store's markup; card payment is not enabled; price override and free delivery are allowed.
