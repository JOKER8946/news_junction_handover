-- Home hero banner supports multiple background images (carousel).
-- Stored as a JSON array of relative paths e.g. ["/manikya market/manikya_market/uploads/company/hero_1.jpg", ...]
-- The existing single home_bg_image column is preserved for legacy fallback;
-- when both are present, home_banner_images takes priority.

ALTER TABLE merchant_profile
  ADD COLUMN IF NOT EXISTS home_banner_images TEXT NULL AFTER home_bg_image;
