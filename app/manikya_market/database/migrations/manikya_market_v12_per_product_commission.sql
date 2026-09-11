-- Per-product commission. Each product can override the platform-wide
-- commission_pct (platform_settings.commission_pct); NULL means "use the
-- platform default". order_items snapshot the rate at sale time so the
-- seller's payout is locked in even if the product's commission is later
-- changed.

ALTER TABLE products
  ADD COLUMN IF NOT EXISTS commission_pct DECIMAL(5,2) NULL AFTER price_per_kg;

ALTER TABLE order_items
  ADD COLUMN IF NOT EXISTS commission_pct DECIMAL(5,2) NOT NULL DEFAULT 0.00 AFTER line_total,
  ADD COLUMN IF NOT EXISTS commission_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER commission_pct,
  ADD COLUMN IF NOT EXISTS seller_payable DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER commission_amount;
