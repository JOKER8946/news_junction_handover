-- Add CIN (Company Identification Number) so sellers can display it on
-- the Amazon-style tax invoice next to PAN and GSTIN.

ALTER TABLE merchant_profile
  ADD COLUMN IF NOT EXISTS business_cin VARCHAR(30) NULL AFTER business_pan;
