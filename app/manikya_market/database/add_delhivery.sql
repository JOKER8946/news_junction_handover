-- Delhivery API Integration - Schema Changes
-- Run on production: mysql -u root -p king_mango < database/add_delhivery.sql

ALTER TABLE `merchant_profile` ADD COLUMN IF NOT EXISTS `delhivery_api_token` VARCHAR(255) NULL;
ALTER TABLE `merchant_profile` ADD COLUMN IF NOT EXISTS `delhivery_mode` VARCHAR(20) NOT NULL DEFAULT 'sandbox';
ALTER TABLE `merchant_profile` ADD COLUMN IF NOT EXISTS `delhivery_warehouse_name` VARCHAR(150) NULL;
ALTER TABLE `merchant_profile` ADD COLUMN IF NOT EXISTS `delhivery_warehouse_address` TEXT NULL;
ALTER TABLE `merchant_profile` ADD COLUMN IF NOT EXISTS `delhivery_warehouse_city` VARCHAR(80) NULL;
ALTER TABLE `merchant_profile` ADD COLUMN IF NOT EXISTS `delhivery_warehouse_state` VARCHAR(80) NULL;
ALTER TABLE `merchant_profile` ADD COLUMN IF NOT EXISTS `delhivery_warehouse_pincode` VARCHAR(12) NULL;
ALTER TABLE `merchant_profile` ADD COLUMN IF NOT EXISTS `delhivery_warehouse_phone` VARCHAR(30) NULL;

ALTER TABLE `orders` ADD COLUMN IF NOT EXISTS `delhivery_waybill` VARCHAR(50) NULL;
ALTER TABLE `orders` ADD COLUMN IF NOT EXISTS `delhivery_shipment_id` VARCHAR(100) NULL;
ALTER TABLE `orders` ADD COLUMN IF NOT EXISTS `delhivery_status` VARCHAR(80) NULL;
ALTER TABLE `orders` ADD COLUMN IF NOT EXISTS `delhivery_status_synced_at` DATETIME NULL;
ALTER TABLE `orders` ADD COLUMN IF NOT EXISTS `delhivery_pickup_token` VARCHAR(100) NULL;

CREATE TABLE IF NOT EXISTS `delhivery_tracking_events` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_id` INT NOT NULL,
  `waybill` VARCHAR(50) NOT NULL,
  `scan_type` VARCHAR(50) NULL,
  `scan_status` VARCHAR(200) NULL,
  `location` VARCHAR(200) NULL,
  `event_time` DATETIME NULL,
  `raw_json` TEXT NULL,
  `created_at` DATETIME NOT NULL,
  INDEX `idx_dte_order` (`order_id`),
  INDEX `idx_dte_waybill` (`waybill`),
  CONSTRAINT `fk_dte_order` FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
