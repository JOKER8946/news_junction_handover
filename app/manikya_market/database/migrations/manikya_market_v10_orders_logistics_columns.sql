-- Adds logistics columns referenced by app/pages/logistics/* and buyer/orders.php
-- that were never present in the base schema dump.

ALTER TABLE orders
  ADD COLUMN IF NOT EXISTS expected_delivery_date DATE NULL AFTER shipping_tracking_no,
  ADD COLUMN IF NOT EXISTS awb_number VARCHAR(80) NULL AFTER expected_delivery_date,
  ADD COLUMN IF NOT EXISTS picked_at DATETIME NULL AFTER awb_number;
