-- =============================================================================
-- Manikya Market v9 — marketplace extras
-- Wishlists, coupons, returns, review enhancements, GST settings
-- =============================================================================

-- ── 1. Wishlist ───────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS wishlists (
  id          INT NOT NULL AUTO_INCREMENT,
  buyer_id    INT NOT NULL,
  product_id  INT NOT NULL,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_wishlists_buyer_product (buyer_id, product_id),
  INDEX idx_wishlists_buyer (buyer_id),
  CONSTRAINT fk_wishlists_buyer   FOREIGN KEY (buyer_id)   REFERENCES users(id)    ON DELETE CASCADE,
  CONSTRAINT fk_wishlists_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ── 2. Coupons / promo codes ──────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS coupons (
  id              INT NOT NULL AUTO_INCREMENT,
  code            VARCHAR(40) NOT NULL,
  description     VARCHAR(255) NULL,
  discount_type   ENUM('percent','fixed') NOT NULL DEFAULT 'percent',
  discount_value  DECIMAL(10,2) NOT NULL,
  min_order_value DECIMAL(10,2) NOT NULL DEFAULT 0,
  max_discount    DECIMAL(10,2) NULL,
  usage_limit     INT NULL,
  used_count      INT NOT NULL DEFAULT 0,
  expires_at      DATETIME NULL,
  is_active       TINYINT(1) NOT NULL DEFAULT 1,
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_coupons_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE orders
  ADD COLUMN coupon_id     INT NULL AFTER discount_amount,
  ADD COLUMN coupon_code   VARCHAR(40) NULL AFTER coupon_id,
  ADD COLUMN coupon_amount DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER coupon_code,
  ADD COLUMN tax_amount    DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER coupon_amount,
  ADD INDEX idx_orders_coupon (coupon_id),
  ADD CONSTRAINT fk_orders_coupon FOREIGN KEY (coupon_id) REFERENCES coupons(id) ON DELETE SET NULL;

-- ── 3. Returns ────────────────────────────────────────────────────────────
ALTER TABLE orders
  MODIFY COLUMN status ENUM(
    'new','paid','packed','ready_to_pick','shipped','in_transit',
    'delivered','cancelled','return_requested','returned','refunded'
  ) NOT NULL DEFAULT 'new';

CREATE TABLE IF NOT EXISTS returns (
  id              INT NOT NULL AUTO_INCREMENT,
  order_id        INT NOT NULL,
  buyer_id        INT NOT NULL,
  merchant_id     INT NULL,
  reason          VARCHAR(255) NOT NULL,
  details         TEXT NULL,
  status          ENUM('requested','approved','rejected','received','refunded') NOT NULL DEFAULT 'requested',
  decision_notes  TEXT NULL,
  refund_amount   DECIMAL(10,2) NULL,
  requested_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  decided_at      DATETIME NULL,
  refunded_at     DATETIME NULL,
  PRIMARY KEY (id),
  INDEX idx_returns_order (order_id),
  INDEX idx_returns_status (status),
  CONSTRAINT fk_returns_order    FOREIGN KEY (order_id)    REFERENCES orders(id) ON DELETE CASCADE,
  CONSTRAINT fk_returns_buyer    FOREIGN KEY (buyer_id)    REFERENCES users(id)  ON DELETE CASCADE,
  CONSTRAINT fk_returns_merchant FOREIGN KEY (merchant_id) REFERENCES users(id)  ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ── 4. Review enhancements (photos + helpful votes) ───────────────────────
-- ratings table is created lazily in code; we add columns idempotently:
CREATE TABLE IF NOT EXISTS ratings (
  id          INT NOT NULL AUTO_INCREMENT,
  order_id    INT NOT NULL,
  buyer_id    INT NOT NULL,
  rating      INT NOT NULL,
  feedback    TEXT,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_ratings_order (order_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE ratings
  ADD COLUMN image_path    VARCHAR(255) NULL AFTER feedback,
  ADD COLUMN helpful_votes INT NOT NULL DEFAULT 0 AFTER image_path,
  ADD COLUMN product_id    INT NULL AFTER buyer_id,
  ADD INDEX idx_ratings_product (product_id);

CREATE TABLE IF NOT EXISTS rating_helpful (
  rating_id  INT NOT NULL,
  buyer_id   INT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (rating_id, buyer_id),
  CONSTRAINT fk_rating_helpful_rating FOREIGN KEY (rating_id) REFERENCES ratings(id) ON DELETE CASCADE,
  CONSTRAINT fk_rating_helpful_buyer  FOREIGN KEY (buyer_id)  REFERENCES users(id)   ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ── 5. GST setting on platform_settings ──────────────────────────────────
ALTER TABLE platform_settings
  ADD COLUMN gst_rate_pct DECIMAL(5,2) NOT NULL DEFAULT 18.00 AFTER commission_pct;
