-- =============================================================================
-- Manikya Market v4 — email notifications via per-merchant SMTP
-- =============================================================================

-- Extend notification_logs to support email (was sms/whatsapp via twilio only).
ALTER TABLE notification_logs
  MODIFY COLUMN channel  ENUM('sms','whatsapp','email') NOT NULL,
  MODIFY COLUMN provider ENUM('twilio','smtp')          NOT NULL DEFAULT 'smtp';

-- Add a column linking the notification to the order/merchant for traceability.
-- Old `to_address` was VARCHAR(50) — fine for phone numbers but truncates emails.
ALTER TABLE notification_logs MODIFY COLUMN to_address VARCHAR(190) NOT NULL;

ALTER TABLE notification_logs
  ADD COLUMN order_id    INT NULL AFTER provider_message_id,
  ADD COLUMN merchant_id INT NULL AFTER order_id,
  ADD COLUMN recipient_role ENUM('buyer','merchant') NULL AFTER merchant_id,
  ADD COLUMN error_message VARCHAR(255) NULL AFTER status,
  ADD INDEX idx_notif_order (order_id),
  ADD INDEX idx_notif_merchant (merchant_id);
