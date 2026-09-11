-- =============================================================================
-- Manikya Market v5 — platform commission + payouts
-- =============================================================================
-- Platform takes a flat % commission on every order. Merchant earnings credit
-- is the full order total; an equal-and-opposite commission debit is recorded
-- against the merchant's wallet and credited to the platform's commission ledger.
-- Super-admin records bank transfers to merchants via the payouts table.
-- =============================================================================

-- Singleton platform settings: commission rate + platform Razorpay keys + bank info.
CREATE TABLE IF NOT EXISTS platform_settings (
  id              INT NOT NULL AUTO_INCREMENT,
  commission_pct  DECIMAL(5,2) NOT NULL DEFAULT 10.00,
  razorpay_key_id     VARCHAR(120) NULL,
  razorpay_key_secret VARCHAR(200) NULL,
  bank_name       VARCHAR(120) NULL,
  bank_account    VARCHAR(50)  NULL,
  bank_ifsc       VARCHAR(20)  NULL,
  updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Seed one row with default 10% commission
INSERT INTO platform_settings (id, commission_pct, updated_at) VALUES (1, 10.00, NOW())
  ON DUPLICATE KEY UPDATE commission_pct = commission_pct;

-- Track every payout transfer from platform to merchant.
CREATE TABLE IF NOT EXISTS payouts (
  id              INT NOT NULL AUTO_INCREMENT,
  merchant_id     INT NOT NULL,
  amount          DECIMAL(10,2) NOT NULL,
  status          ENUM('pending','paid','failed') NOT NULL DEFAULT 'paid',
  method          ENUM('bank_transfer','razorpay_payout','upi','cash','other') NOT NULL DEFAULT 'bank_transfer',
  reference_no    VARCHAR(120) NULL,
  notes           TEXT NULL,
  created_by      INT NULL,
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  paid_at         DATETIME NULL,
  PRIMARY KEY (id),
  INDEX idx_payouts_merchant (merchant_id),
  INDEX idx_payouts_status (status),
  CONSTRAINT fk_payouts_merchant FOREIGN KEY (merchant_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_payouts_created_by FOREIGN KEY (created_by)  REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Track commission per order so we can audit/recompute later.
ALTER TABLE orders
  ADD COLUMN commission_pct    DECIMAL(5,2)  NULL AFTER total_amount,
  ADD COLUMN commission_amount DECIMAL(10,2) NULL AFTER commission_pct,
  ADD COLUMN merchant_payable  DECIMAL(10,2) NULL AFTER commission_amount;
