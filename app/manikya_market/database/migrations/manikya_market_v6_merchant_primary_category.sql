-- =============================================================================
-- Manikya Market v6 — merchant primary category at signup
-- =============================================================================
-- When applying, a merchant declares which category they primarily sell in.
-- They can still list products in any category later; this is just their
-- "what kind of seller are you" answer for super-admin to use when approving.
-- =============================================================================

ALTER TABLE merchant_profile
  ADD COLUMN primary_category_id INT NULL AFTER status,
  ADD INDEX idx_merchant_profile_primary_category (primary_category_id),
  ADD CONSTRAINT fk_merchant_profile_primary_category
    FOREIGN KEY (primary_category_id) REFERENCES categories(id) ON DELETE SET NULL;
