CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  role ENUM('buyer','merchant') NOT NULL,
  full_name VARCHAR(120) NOT NULL,
  phone VARCHAR(30) NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  status ENUM('active','disabled') NOT NULL DEFAULT 'active',
  created_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS merchant_profile (
  id INT AUTO_INCREMENT PRIMARY KEY,
  merchant_user_id INT NOT NULL UNIQUE,
  business_name VARCHAR(150) NULL,
  business_address TEXT NULL,
  business_gstin VARCHAR(20) NULL,
  business_pan VARCHAR(20) NULL,
  business_state VARCHAR(80) NULL,
  business_pincode VARCHAR(12) NULL,
  business_bank_name VARCHAR(120) NULL,
  business_bank_account VARCHAR(50) NULL,
  business_bank_ifsc VARCHAR(20) NULL,
  business_logo_path VARCHAR(255) NULL,
  razorpay_key_id VARCHAR(120) NULL,
  razorpay_key_secret VARCHAR(200) NULL,
  twilio_account_sid VARCHAR(120) NULL,
  twilio_auth_token VARCHAR(120) NULL,
  twilio_sms_from VARCHAR(30) NULL,
  twilio_whatsapp_from VARCHAR(30) NULL,
  created_at DATETIME NOT NULL,
  CONSTRAINT fk_mp_merchant FOREIGN KEY (merchant_user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS products (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  product_type VARCHAR(80) NULL,
  part_code VARCHAR(30) NULL,
  description TEXT NULL,
  image_path VARCHAR(255) NULL,
  price_per_kg DECIMAL(10,2) NOT NULL DEFAULT 0,
  shipping_type ENUM('flat','per_kg') NOT NULL DEFAULT 'flat',
  shipping_rate DECIMAL(10,2) NOT NULL DEFAULT 0,
  stock_kg DECIMAL(10,2) NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL,
  UNIQUE KEY idx_products_part_code (part_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS inventory (
  id INT AUTO_INCREMENT PRIMARY KEY,
  product_id INT NOT NULL UNIQUE,
  qty_kg DECIMAL(10,2) NOT NULL DEFAULT 0,
  updated_at DATETIME NOT NULL,
  CONSTRAINT fk_inventory_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS inventory_movements (
  id INT AUTO_INCREMENT PRIMARY KEY,
  product_id INT NOT NULL,
  movement_type ENUM('inward','sale','adjustment') NOT NULL,
  qty_kg DECIMAL(10,2) NOT NULL,
  ref_type VARCHAR(30) NULL,
  ref_id INT NULL,
  notes VARCHAR(255) NULL,
  created_at DATETIME NOT NULL,
  CONSTRAINT fk_inv_mov_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
  INDEX idx_inv_mov_ref (ref_type, ref_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS buyer_addresses (
  id INT AUTO_INCREMENT PRIMARY KEY,
  buyer_id INT NOT NULL,
  label VARCHAR(50) NULL,
  full_name VARCHAR(120) NULL,
  phone VARCHAR(30) NULL,
  address_line1 VARCHAR(200) NOT NULL,
  address_line2 VARCHAR(200) NULL,
  city VARCHAR(80) NOT NULL,
  state VARCHAR(80) NOT NULL,
  pincode VARCHAR(12) NOT NULL,
  is_default TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL,
  CONSTRAINT fk_addr_buyer FOREIGN KEY (buyer_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS orders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_no VARCHAR(30) NOT NULL UNIQUE,
  buyer_id INT NOT NULL,
  status ENUM('new','paid','packed','shipped','delivered','cancelled','returned') NOT NULL DEFAULT 'new',
  subtotal_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
  shipping_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
  total_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
  delivery_address_id INT NULL,
  created_at DATETIME NOT NULL,
  cancelled_at DATETIME NULL,
  cancel_reason VARCHAR(255) NULL,
  returned_at DATETIME NULL,
  return_reason VARCHAR(255) NULL,
  CONSTRAINT fk_order_buyer FOREIGN KEY (buyer_id) REFERENCES users(id) ON DELETE RESTRICT,
  CONSTRAINT fk_order_addr FOREIGN KEY (delivery_address_id) REFERENCES buyer_addresses(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS order_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL,
  product_id INT NOT NULL,
  qty_kg DECIMAL(10,2) NOT NULL,
  price_per_kg DECIMAL(10,2) NOT NULL,
  line_total DECIMAL(10,2) NOT NULL,
  created_at DATETIME NOT NULL,
  CONSTRAINT fk_item_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  CONSTRAINT fk_item_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS payments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL,
  provider ENUM('razorpay') NOT NULL DEFAULT 'razorpay',
  status ENUM('initiated','paid','failed') NOT NULL DEFAULT 'initiated',
  provider_order_id VARCHAR(120) NULL,
  provider_payment_id VARCHAR(120) NULL,
  provider_signature VARCHAR(255) NULL,
  amount DECIMAL(10,2) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL,
  CONSTRAINT fk_payment_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS notification_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  channel ENUM('sms','whatsapp') NOT NULL,
  to_address VARCHAR(50) NOT NULL,
  message TEXT NOT NULL,
  provider ENUM('twilio') NOT NULL DEFAULT 'twilio',
  provider_message_id VARCHAR(120) NULL,
  status ENUM('queued','sent','failed') NOT NULL DEFAULT 'queued',
  created_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Support tickets raised by buyers and responses by merchants
CREATE TABLE IF NOT EXISTS tickets (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NULL,
  buyer_id INT NOT NULL,
  subject VARCHAR(255) NOT NULL,
  status ENUM('open','pending','resolved','closed') NOT NULL DEFAULT 'open',
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  CONSTRAINT fk_ticket_buyer FOREIGN KEY (buyer_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_ticket_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL,
  INDEX idx_ticket_buyer (buyer_id),
  INDEX idx_ticket_order (order_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS ticket_messages (
  id INT AUTO_INCREMENT PRIMARY KEY,
  ticket_id INT NOT NULL,
  sender_type ENUM('buyer','merchant','system') NOT NULL,
  sender_id INT NULL,
  message TEXT NOT NULL,
  created_at DATETIME NOT NULL,
  CONSTRAINT fk_ticket_msg_ticket FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE,
  INDEX idx_ticket_msg_ticket (ticket_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Referral codes: one unique code per buyer
CREATE TABLE IF NOT EXISTS referral_codes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL UNIQUE,
  code VARCHAR(10) NOT NULL UNIQUE,
  created_at DATETIME NOT NULL,
  CONSTRAINT fk_refcode_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Referral tracking: who referred whom
CREATE TABLE IF NOT EXISTS referrals (
  id INT AUTO_INCREMENT PRIMARY KEY,
  referrer_id INT NOT NULL,
  referee_id INT NOT NULL UNIQUE,
  status ENUM('pending','completed','expired') NOT NULL DEFAULT 'pending',
  referee_discount_pct DECIMAL(5,2) NOT NULL DEFAULT 10.00,
  referrer_reward_pct DECIMAL(5,2) NOT NULL DEFAULT 5.00,
  referee_order_id INT NULL,
  discount_amount DECIMAL(10,2) NULL,
  reward_amount DECIMAL(10,2) NULL,
  created_at DATETIME NOT NULL,
  completed_at DATETIME NULL,
  CONSTRAINT fk_ref_referrer FOREIGN KEY (referrer_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_ref_referee FOREIGN KEY (referee_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_ref_order FOREIGN KEY (referee_order_id) REFERENCES orders(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Wallet transactions for referrer earnings
CREATE TABLE IF NOT EXISTS wallet_transactions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  type ENUM('credit','debit') NOT NULL,
  amount DECIMAL(10,2) NOT NULL,
  description VARCHAR(255) NULL,
  ref_type VARCHAR(30) NULL,
  ref_id INT NULL,
  created_at DATETIME NOT NULL,
  CONSTRAINT fk_wallet_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_wallet_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- (returns table removed from schema)
CREATE TABLE IF NOT EXISTS password_resets (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  email VARCHAR(190) NOT NULL,
  token VARCHAR(64) NOT NULL UNIQUE,
  created_at DATETIME NOT NULL,
  expires_at DATETIME NOT NULL,
  used_at DATETIME NULL,
  CONSTRAINT fk_pwd_reset_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_pwd_reset_token (token),
  INDEX idx_pwd_reset_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;