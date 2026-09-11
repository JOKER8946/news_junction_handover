-- Track cost price per item received via Gate Entry / GRN. Sellers enter
-- the supplier's per-unit cost on the Gate Entry form so it is captured at
-- intake and remains queryable for cost-of-goods analytics later.

ALTER TABLE grn_items
  ADD COLUMN IF NOT EXISTS unit_price DECIMAL(10,2) NULL DEFAULT 0.00 AFTER expected_qty_kg;
