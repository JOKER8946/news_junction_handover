-- Adds a `unit` column on products so sellers can pick the unit of sale
-- (kg, gm, piece, dozen, litre, bunch) for each product. Existing rows
-- default to 'kg' to match the historical assumption.

ALTER TABLE products
  ADD COLUMN IF NOT EXISTS unit VARCHAR(20) NOT NULL DEFAULT 'kg' AFTER stock_kg;
