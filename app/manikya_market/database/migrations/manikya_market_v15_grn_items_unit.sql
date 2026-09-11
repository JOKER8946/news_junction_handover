-- Track the unit each line item was received in. Sellers can override the
-- product's default selling unit on the Gate Entry form when a supplier
-- delivers in a different unit (e.g. product sells in pieces but supplier
-- delivers in dozens).

ALTER TABLE grn_items
  ADD COLUMN IF NOT EXISTS unit VARCHAR(20) NOT NULL DEFAULT 'kg' AFTER product_id;
