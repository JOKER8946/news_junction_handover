-- =============================================================================
-- Manikya Market v8 — platform-level integrations (Amazon-style)
-- =============================================================================
-- Buyer payments go through the platform's Razorpay (not per merchant).
-- Shipments go through the platform's Delhivery account.
-- Emails go through the platform's SMTP (with per-merchant fallback retained
-- in the helper).
-- =============================================================================

ALTER TABLE platform_settings
  ADD COLUMN delhivery_api_token         VARCHAR(255) NULL,
  ADD COLUMN delhivery_mode              VARCHAR(20)  NOT NULL DEFAULT 'sandbox',
  ADD COLUMN delhivery_warehouse_name    VARCHAR(150) NULL,
  ADD COLUMN delhivery_warehouse_address TEXT NULL,
  ADD COLUMN delhivery_warehouse_city    VARCHAR(80)  NULL,
  ADD COLUMN delhivery_warehouse_state   VARCHAR(80)  NULL,
  ADD COLUMN delhivery_warehouse_pincode VARCHAR(12)  NULL,
  ADD COLUMN delhivery_warehouse_phone   VARCHAR(30)  NULL,
  ADD COLUMN smtp_host       VARCHAR(150) NULL DEFAULT 'smtp.gmail.com',
  ADD COLUMN smtp_port       INT          NULL DEFAULT 587,
  ADD COLUMN smtp_username   VARCHAR(190) NULL,
  ADD COLUMN smtp_password   VARCHAR(190) NULL,
  ADD COLUMN smtp_from_email VARCHAR(190) NULL,
  ADD COLUMN smtp_from_name  VARCHAR(150) NULL DEFAULT 'Manikya Market';
