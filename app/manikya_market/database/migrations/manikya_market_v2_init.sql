-- =============================================================================
-- Manikya Market v2 — multi-merchant marketplace bootstrap
-- =============================================================================
-- - Wipes leftover seed data (users, products, etc.)
-- - Adds super_admin role
-- - Creates categories table (seeded with 7 top-level categories)
-- - Adds merchant_id + category_id to products
-- - Adds approval workflow (merchant_profile.status)
-- - Adds Delhivery / SMTP / Google OAuth columns to merchant_profile (parity
--   with king_mango code so existing libs work without changes)
-- - Seeds one super-admin (YOUR_ADMIN_EMAIL)
-- =============================================================================

SET FOREIGN_KEY_CHECKS = 0;

TRUNCATE TABLE notification_logs;
TRUNCATE TABLE payments;
TRUNCATE TABLE order_tracking;
TRUNCATE TABLE order_items;
TRUNCATE TABLE orders;
TRUNCATE TABLE buyer_addresses;
TRUNCATE TABLE inventory_movements;
TRUNCATE TABLE inventory;
TRUNCATE TABLE grn_items;
TRUNCATE TABLE grn_inspection;
TRUNCATE TABLE grn;
TRUNCATE TABLE gate_entry;
TRUNCATE TABLE logistics_profile;
TRUNCATE TABLE merchant_profile;
TRUNCATE TABLE products;
TRUNCATE TABLE users;

SET FOREIGN_KEY_CHECKS = 1;

-- -----------------------------------------------------------------------------
-- 1. Add super_admin role
-- -----------------------------------------------------------------------------
ALTER TABLE users
  MODIFY COLUMN role ENUM('buyer','merchant','logistics','super_admin') NOT NULL;

-- -----------------------------------------------------------------------------
-- 2. Categories
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS categories (
  id          INT NOT NULL AUTO_INCREMENT,
  name        VARCHAR(150) NOT NULL,
  slug        VARCHAR(160) NOT NULL,
  description TEXT NULL,
  image_path  VARCHAR(255) NULL,
  sort_order  INT NOT NULL DEFAULT 0,
  is_active   TINYINT(1) NOT NULL DEFAULT 1,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_categories_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO categories (name, slug, sort_order) VALUES
  ('Fashions',                                       'fashions',                              10),
  ('Jewellery',                                      'jewellery',                             20),
  ('Refurbished Electronics and Appliances',         'refurbished-electronics-appliances',    30),
  ('Big Discount on Electronics and Appliances',     'big-discount-electronics-appliances',   40),
  ('Mega Discount on Factory Clearances',            'mega-discount-factory-clearances',      50),
  ('Automobile Two Wheeler and Four Wheeler',        'automobile-two-four-wheeler',           60),
  ('Furnitures',                                     'furnitures',                            70);

-- -----------------------------------------------------------------------------
-- 3. Products: bind to merchant + category
-- -----------------------------------------------------------------------------
ALTER TABLE products
  ADD COLUMN merchant_id INT NOT NULL AFTER id,
  ADD COLUMN category_id INT NOT NULL AFTER merchant_id,
  ADD INDEX idx_products_merchant (merchant_id),
  ADD INDEX idx_products_category (category_id),
  ADD CONSTRAINT fk_products_merchant
      FOREIGN KEY (merchant_id) REFERENCES users(id)      ON DELETE CASCADE,
  ADD CONSTRAINT fk_products_category
      FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT;

-- -----------------------------------------------------------------------------
-- 4. Merchant approval workflow + integration columns (parity with code)
-- -----------------------------------------------------------------------------
ALTER TABLE merchant_profile
  ADD COLUMN status ENUM('pending','approved','disabled') NOT NULL DEFAULT 'pending' AFTER merchant_user_id,
  ADD COLUMN delhivery_api_token       VARCHAR(255) NULL,
  ADD COLUMN delhivery_mode            VARCHAR(20)  NOT NULL DEFAULT 'sandbox',
  ADD COLUMN delhivery_warehouse_name    VARCHAR(150) NULL,
  ADD COLUMN delhivery_warehouse_address TEXT NULL,
  ADD COLUMN delhivery_warehouse_city    VARCHAR(80)  NULL,
  ADD COLUMN delhivery_warehouse_state   VARCHAR(80)  NULL,
  ADD COLUMN delhivery_warehouse_pincode VARCHAR(12)  NULL,
  ADD COLUMN delhivery_warehouse_phone   VARCHAR(30)  NULL,
  ADD COLUMN google_client_id     VARCHAR(255) NULL,
  ADD COLUMN google_client_secret VARCHAR(255) NULL,
  ADD COLUMN smtp_host       VARCHAR(150) NULL DEFAULT 'smtp.gmail.com',
  ADD COLUMN smtp_port       INT          NULL DEFAULT 587,
  ADD COLUMN smtp_username   VARCHAR(190) NULL,
  ADD COLUMN smtp_password   VARCHAR(190) NULL,
  ADD COLUMN smtp_from_email VARCHAR(190) NULL,
  ADD COLUMN smtp_from_name  VARCHAR(150) NULL,
  ADD COLUMN home_bg_color VARCHAR(20)  NULL DEFAULT '#fff8f0',
  ADD COLUMN home_bg_image VARCHAR(255) NULL,
  ADD INDEX idx_merchant_profile_status (status);

-- -----------------------------------------------------------------------------
-- 5. Seed super-admin
--    Email:    YOUR_ADMIN_EMAIL
--    Password: Manikya@2026!   (bcrypt; change on first login)
-- -----------------------------------------------------------------------------
INSERT INTO users (role, full_name, email, password_hash, status, created_at) VALUES
  ('super_admin',
   'Prashanth (Super Admin)',
   'YOUR_ADMIN_EMAIL',
   '$2y$10$Z1O1lLdMKfyW48EYv0YgxeT1tBMbPMkppGp.vJfJLPR4wz.1WU87O',
   'active',
   NOW());
