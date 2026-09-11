-- =============================================================================
-- Manikya Market v3 — multi-merchant orders
-- =============================================================================
-- Each order belongs to exactly one merchant. When a buyer's cart spans
-- multiple merchants, checkout creates N orders (one per merchant) linked
-- by parent_order_no so the buyer sees them as one purchase.
-- =============================================================================

ALTER TABLE orders
  ADD COLUMN merchant_id INT NULL AFTER buyer_id,
  ADD COLUMN parent_order_no VARCHAR(40) NULL AFTER order_no,
  ADD INDEX idx_orders_merchant (merchant_id),
  ADD INDEX idx_orders_parent (parent_order_no),
  ADD CONSTRAINT fk_orders_merchant FOREIGN KEY (merchant_id) REFERENCES users(id) ON DELETE SET NULL;

-- Backfill any existing rows: derive merchant_id from the first order_item's product.
-- (Manikya Market is fresh; this is defensive in case any rows pre-exist.)
UPDATE orders o
SET merchant_id = (
    SELECT p.merchant_id
    FROM order_items oi
    JOIN products p ON p.id = oi.product_id
    WHERE oi.order_id = o.id
    ORDER BY oi.id ASC
    LIMIT 1
)
WHERE merchant_id IS NULL;

-- payments.provider was an ENUM('razorpay') only — extend so COD orders store
-- the right value (the existing code path inserts 'cod' for Cash on Delivery).
ALTER TABLE payments
  MODIFY COLUMN provider ENUM('razorpay','cod') NOT NULL DEFAULT 'razorpay';
