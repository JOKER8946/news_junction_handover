# Manikya Market — Changes Log

A summary of feature work, security fixes, and UI overhauls completed on the
Manikya Market marketplace platform.

---

## 1. Local Setup

- Imported `manikya_market.sql` into local XAMPP MySQL (`FOREIGN_KEY_CHECKS=0`
  during import to bypass intra-dump ordering issues).
- All app routes live under `/manikya%20market/manikya_market/`.
- Confirmed seeded super-admin credentials work locally.

---

## 2. Marketplace-Wide Terminology

- **Merchant → Seller** rename across all user-visible text (page titles,
  headings, table headers, button labels, flash messages, empty states).
- URL routes (`?p=merchant/...`), DB columns, variable names, and role-enum
  values intentionally left as `merchant` to avoid breaking data and code paths.

---

## 3. Bug Fixes

### Orders table — missing columns (Migration v10)
Added `expected_delivery_date`, `awb_number`, `picked_at` to `orders` —
referenced by buyer/orders.php, logistics/order-detail.php, and the Delhivery
integration but never migrated.

### Promotion Updates not appearing on homepage
`home.php` was reading `SELECT * FROM merchant_profile LIMIT 1` (first row in
PK order) instead of the super-admin's profile row. Fixed to explicitly query
`WHERE u.role='super_admin'` so the super-admin's Promotion Updates render.

### Promotion Updates' `$val` helper silently re-substituting defaults
The helper was returning a hard-coded default whenever the saved value was
empty, so clearing a field and saving made the page re-display the default and
look like the save was ignored. Rewrote to differentiate `NULL` (never saved)
from `""` (intentionally cleared).

### Checkout — `$selectedAddressId` undefined in closure
Added `$selectedAddressId` to the `use(...)` list of `$content = function()`
in `buyer/checkout.php` so the radio-button `checked` state at line 567 works.

### Footer logo URL broken
The footer used `<img src="/assets/img/footer-logo.jpeg">` which resolved to
the web root (404). Updated to prepend `app_base_path($config)`.

### Categories bar disappearing on scroll
Changed from `position: sticky` (broken by `overflow-x: hidden` on body) to
`position: fixed` with a spacer, so the bar is always visible just under the
navbar.

### Status & Actions panel hidden on first click
`merchant/order.php` has a `$viewOnly` flag that hides the action panel. The
Orders list and Payment Ledger were passing `&view=1` in their "View" links.
Removed the param so sellers always land on the actionable view.

---

## 4. Database Migrations Added

| Version | Purpose |
| --- | --- |
| v10 | Orders logistics columns (`expected_delivery_date`, `awb_number`, `picked_at`) |
| v11 | `products.unit` for the per-product unit selector |
| v12 | Per-product commission (`products.commission_pct`, `order_items.commission_pct/commission_amount/seller_payable`) |
| v13 | `merchant_profile.home_banner_images` (JSON array for hero carousel) |
| v14 | `grn_items.unit_price` for Gate Entry cost tracking |
| v15 | `grn_items.unit` so each received line preserves its supplier unit |
| v16 | `merchant_profile.business_cin` for tax invoices |

---

## 5. Product Unit System

- Added a **Unit dropdown** (Kg, Gram, Piece, Dozen, Litre, Bunch) to
  Add Product and Edit Product forms.
- `product_unit_label()` helper in `app/lib/helpers.php` for consistent
  display ("Kg" / "Gram" / etc.) anywhere a product is rendered.
- Updated product list/grid pages (home, products, merchant/products) to show
  each product's unit instead of the hard-coded "Kg".
- Cart, buyer orders, invoices, merchant order detail, logistics order detail
  all show each product's actual unit.
- Inventory page lists each product's unit + uses it on the Inward placeholder.
- GRN line items track unit per receipt so a product sold by piece can be
  inwarded by dozen.
- Pure-weight contexts (Delhivery shipping payloads, GRN totals, reports
  weight column) intentionally kept in kg since they track physical fulfillment.

---

## 6. Finance — Marketplace ↔ Operations ↔ Finance Wiring

### Per-product commission (Migration v12)
- `products.commission_pct` — per-product override of the platform default
  (`platform_settings.commission_pct`). `NULL` falls back to the platform rate.
- `order_items.commission_pct / commission_amount / seller_payable` —
  snapshotted at sale time so rate changes don't retroactively affect old
  orders.
- New helpers in `app/lib/referral.php`:
  `product_commission_pct()`, `compute_line_commission()`,
  `reverse_merchant_wallet_credit_for_order()`.
- Checkout (`buyer/checkout.php`) and `buyer/razorpay-create.php` now
  snapshot the commission at order-item insert time.

### S-Admin → Finance → Commissions (new page)
Path: `?p=super-admin/commissions`
- Seller-wise table of every product with an inline commission % editor.
- Per-row lifetime Revenue, Platform Earned, Seller Earned.
- Per-seller totals + three KPI cards across all sellers.
- Seller filter dropdown.
- **CSV download** with Month / Quarter / Year period selector.

### Wallet credit + reversal flow
- `merchant_wallet_credit_for_order()` sums per-line snapshots so the seller
  gets the exact net payable and the super-admin gets the exact commission.
- **Cancellation** (`buyer/orders.php`) and **return approval**
  (`merchant/returns.php`) now call `reverse_merchant_wallet_credit_for_order()`
  to undo both the seller credit and the platform commission credit. Idempotent.

### Payout validation
`super-admin/payouts.php` now rejects payouts that exceed the seller's
available wallet balance, with a clear error showing both numbers.

### Seller Earnings page enhanced
New columns on `super-admin/merchant-wallets.php`: **Total credited**,
**Reversed** (red `−`), **Paid out** (blue), **Balance (available)**.

### Seller Payment Ledger enhanced
`merchant/payments-ledger.php` now shows Gross / Commission (amber `−`) /
Net Payable (green bold) columns and includes them in the CSV export.

---

## 7. Cross-Seller Data Leak Audit & Fixes

Comprehensive audit of all 31 merchant pages. Three **critical** leaks
patched:

1. **`merchant/order-invoice.php`** — Any logged-in seller could pass
   `?id=<any order id>` and read a competitor's full invoice (buyer PII,
   address, line items, payment method). Added
   `AND o.merchant_id = :mid` ownership filter.
2. **`merchant/reports-data.php`** — The AJAX endpoint returned global sales
   aggregates across all sellers. Added `o.merchant_id = :mid` to the inner
   aggregate and `p.merchant_id = :mid` to the product join.
3. **`merchant/grn-create.php`** — Products dropdown listed every active
   product on the platform; POST handler accepted any `product_id`. Scoped
   the dropdown to the seller's own catalog AND added a defence-in-depth
   server-side check so forged form POSTs are rejected.

Other 28 pages verified clean.

---

## 8. New S-Admin Pages

### Seller Inventory (`?p=super-admin/inventory`)
- Sidebar: Operations → Inventory.
- Products grouped by seller, with per-row Item / Part Code / Unit / Price /
  Qty Available / Sold Qty / Stock Value / Revenue / Status.
- Per-seller footer row with totals; per-seller card header.
- Four KPI cards across the top.
- "Sold qty" counts paid orders not yet cancelled/returned/refunded.

### Seller Commissions (`?p=super-admin/commissions`)
See section 6.

### Invoice Issuer details on Platform Settings
New section on `super-admin/platform-settings.php` with fields for the
platform's tax-invoice identity (Company name, Address, State, Pincode,
PAN, GSTIN, CIN). Saves to the super-admin's `merchant_profile` row.

---

## 9. UI Overhaul — Amazon International Style

### Navbar (buyer + guest)
Three-section Amazon-style header in `app/views/partials/nav.php`:
- **Top row (`#131A22`)** — 90px tall: Logo, "Deliver to <FirstName> / <City Pincode>",
  Account stack (linked to addresses), Orders stack, Cart with orange count badge.
- **Bottom row (`#232F3E`)** — 40px tall: ☰ All hamburger, Wishlist, My Orders,
  Refer & Earn, Wallet, Addresses, Support, Logout (right-aligned).
- Merchant/super-admin/logistics keep the white navbar since they use a
  sidebar.

### Categories bar (`app/views/partials/categories-bar.php`)
- `position: fixed` just below the navbar (`top: 130px`), always visible.
- Underline-style active state with `#FF9900` accent.
- Mobile-responsive with horizontal scroll.

### Home (`app/pages/home.php`)
- Amazon-style hero banner with multi-image carousel (Bootstrap carousel,
  auto-rotate, arrows on banner edges, dot indicators).
- 4-up category tile cards with 2×2 product thumbnail grid.
- Restyled product cards in white surfaces with teal links.
- Functional "All / category" search dropdown wired to the products page.

### Products listing
- Pagination disabled state styled (gray) so users can distinguish from active
  buttons.
- Category bar always visible (sticky).

### My Orders (`app/pages/buyer/orders.php`)
Amazon "Your Orders" pattern:
- Page header with title + search bar with dark "Search Orders" button.
- Tabs: Orders | Buy Again | Not Yet Shipped (orange underline active).
- Time filter dropdown: past 3 months / 6 months / year / anytime.
- **4-step horizontal status progress timeline** with timestamps below each
  step (Order placed / Packed / Shipped / Delivered).
- Cancelled/returned states render a red pill instead of progress.

### Wishlist (`app/pages/buyer/wishlist.php`)
Amazon wishlist pattern:
- Top tabs: Your Lists / Your Friends.
- Two-column layout: left sidebar with "Shopping List" card, right main panel.
- Horizontal item rows: 156×156 image, teal product link, seller name, price,
  "Item added <date>", action pill row, yellow "Add to Cart" CTA.
- Filter bar: Search / Show (in/out of stock) / Sort by.

### Support Tickets (`app/pages/buyer/tickets.php`)
Restyled from orange-gradient theme to Amazon palette:
- Clean white page header.
- Stat cards with neutral icon chips.
- Underline-style filter tabs with active-state count badges in dark navy.
- Ticket cards with teal subject links, status side-stripes, gray pill metadata.
- White modal headers, yellow CTAs, neutral uploader.

### Footer (`app/views/partials/footer.php`)
- Two-tone navy: `#232F3E` main band + `#131A22` darker bottom strip.
- 3px gradient border at top (gold `#E8A23B` ↔ dark gold `#C77D2A`).
- Social icons as round 32×32 translucent chips with gold-on-hover.
- Link hovers: gold in main band, sky blue (`#7FB3FF`) in bottom strip.
- Footer logo URL fixed (was 404 due to absolute path).

### Hero banner — Promotion Updates wiring
- Background image + color picker exposed on Promotion Updates.
- **Multi-image upload** (Migration v13) — up to 10 banner images stored as
  JSON. Per-image remove checkboxes. Renders as Bootstrap carousel on home.
- Dark-navy gradient background with starry-dot pattern + warm gold title
  accent + light-blue sub-text + emerald-green CTA, matching the
  manikyamoneyservice.com reference.
- Eyebrow chip with red dot bullet via `::before`.
- Auto-flips text colors based on banner background luminance.

### Merchant sidebar
Now uses the **same indigo-purple gradient as the Super Admin sidebar**
(`#1E2A78 → #2D2F8F → #4338CA`), with matching section labels, nav links,
active states, and footer profile card. Only the active page is highlighted;
submenus auto-open only when they contain the current page.

---

## 10. Gate Entry & Inventory

### Gate Entry (`app/pages/merchant/gate-entry.php`)
Consolidated from two-step (Gate Entry → GRN Create) into a single screen:
- Supplier / Invoice header (existing fields).
- **Items received** table with dynamic rows: Product dropdown (seller's own
  catalog only), Unit dropdown (auto-defaults to product's unit but
  overridable), Qty, Unit price (₹), Line total, Remove.
- Live recalculation; "Add item" button appends rows; Grand total in footer.
- POST handler validates each `product_id` against the seller's catalog and
  creates `gate_entry` + `grn` + `grn_items` in one transaction.
- Redirects to `merchant/grn-detail` for QC and approval.

### Inventory (`app/pages/merchant/inventory.php`)
- Stock Management table headers no longer hard-code "(kg)".
- Added a **Unit column** showing each product's actual unit.
- Inward input placeholder reflects the product's unit.

---

## 11. Tax Invoice — Amazon Format

### Single shared partial
Created `app/views/partials/invoice-template.php` used by all three role
pages — buyer, merchant, logistics — so every view renders the same invoice.

### Layout matches the Amazon reference PDF
1. Header strip: company logo + name (left) / "Tax Invoice/Bill of Supply/
   Cash Memo" / "(Original for Recipient)" (right).
2. **Sold By** (left) | **Billing Address** (right) — with State/UT Code.
3. **PAN / GST / CIN** (left) | **Shipping Address** (right) — with Place of
   supply / Place of delivery.
4. Order Number / Order Date (left) | Invoice Number / Invoice Date (right).
5. **9-column items table**: Sl. No / Description / Unit Price / Qty /
   Net Amount / Tax Rate / Tax Type / Tax Amount / Total Amount.
   - Intra-state (same State/UT Code): **CGST + SGST** rows split via
     `rowspan="2"`.
   - Inter-state: single **IGST** row.
6. TOTAL row.
7. **Amount in Words** — Indian numbering (Crore / Lakh / Thousand).
8. Authorized Signatory block.
9. Reverse-charge note.
10. Payment Transaction table (Transaction ID / Date & Time / Invoice Value /
    Mode of Payment).
11. Centered footer disclaimer.

### "Sold By" = Platform, not seller
All three role pages load `$company` from the **super-admin's**
`merchant_profile` row (not the individual seller's), so the invoice header
shows the platform's identity (Manikya Money Service Private Limited).

### Cache cleared
All stale `uploads/invoices/*.html` and `*.pdf` deleted so the next view
regenerates with the new format.

---

## 12. Order Lifecycle

### Status timeline (buyer's My Orders page)
4-step horizontal progress bar with **per-step timestamps** below each label:
- **Order placed** — `orders.created_at`
- **Packed** — `order_tracking.packed` (or `ready_to_pick`)
- **Shipped** — `orders.shipped_at` → `order_tracking.shipped/in_transit`
- **Delivered** — `orders.delivered_at` → `order_tracking.delivered`

Cancelled / returned orders show a red pill with the cancel/return timestamp.

### Status & Actions panel
Visible on every click from the seller Orders list (not just after visiting
the invoice page).

---

## 13. PHP Backend Helpers

- `app/lib/helpers.php` — `product_unit_label($unit)` for consistent unit
  display.
- `app/lib/referral.php` — `product_commission_pct()`,
  `compute_line_commission()`, `reverse_merchant_wallet_credit_for_order()`.
- `app/lib/wishlist.php` — `wishlist_items()` now returns `added_at`, `unit`,
  `product_type`, `part_code` for the Amazon wishlist row.
- `app/lib/grn.php` — `create_grn_from_gate_entry()` accepts unit + unit_price
  per line.
- Invoice number-to-words helper (`numberToIndianWords()`) in the shared
  invoice partial.

---

## 14. Files Touched (high-level)

```
app/lib/
  helpers.php, referral.php, wishlist.php, grn.php, cart.php

app/pages/buyer/
  orders.php, order-invoice.php, wishlist.php, tickets.php, checkout.php,
  razorpay-create.php

app/pages/merchant/
  dashboard.php, products.php, product-add.php, product-edit.php,
  inventory.php, gate-entry.php, grn-create.php, order.php, order-invoice.php,
  orders.php, payments-ledger.php, promotions.php, returns.php, settings.php,
  reports-data.php

app/pages/super-admin/
  inventory.php (new), commissions.php (new), merchant-wallets.php,
  payouts.php, platform-settings.php, merchants.php

app/pages/logistics/
  order-invoice.php

app/pages/
  home.php, products.php

app/views/partials/
  nav.php, footer.php, categories-bar.php, super-admin-nav.php,
  invoice-template.php (new)

assets/css/app.css

database/migrations/
  v10 through v16
```

---

## 15. Quick Reference URLs

| Role | Login | Dashboard |
| --- | --- | --- |
| Super Admin | `?p=super-admin/login` | `?p=super-admin/dashboard` |
| Seller | `?p=merchant/login` | `?p=merchant/dashboard` |
| Buyer | `?p=buyer/login` | `?p=home` |
| Logistics | `?p=logistics/login` | `?p=logistics/dashboard` |

Seeded credentials are documented in `database/migrations/manikya_market_v2_init.sql`.
