-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 26, 2026 at 12:47 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `manikya_market`
--

-- --------------------------------------------------------

--
-- Table structure for table `buyer_addresses`
--

CREATE TABLE `buyer_addresses` (
  `id` int(11) NOT NULL,
  `buyer_id` int(11) NOT NULL,
  `label` varchar(50) DEFAULT NULL,
  `full_name` varchar(120) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `address_line1` varchar(200) NOT NULL,
  `address_line2` varchar(200) DEFAULT NULL,
  `city` varchar(80) NOT NULL,
  `state` varchar(80) NOT NULL,
  `pincode` varchar(12) NOT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `buyer_addresses`
--

INSERT INTO `buyer_addresses` (`id`, `buyer_id`, `label`, `full_name`, `phone`, `address_line1`, `address_line2`, `city`, `state`, `pincode`, `is_default`, `created_at`) VALUES
(1, 4, 'Home', 'Test Buyer', '+919000000000', '789 Buyer Lane', NULL, 'Bangalore', 'Karnataka', '560001', 1, '2026-05-26 12:38:01');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `slug` varchar(160) NOT NULL,
  `description` text DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `slug`, `description`, `image_path`, `sort_order`, `is_active`, `created_at`) VALUES
(1, 'Fashions', 'fashions', NULL, NULL, 10, 1, '2026-05-26 12:08:29'),
(2, 'Jewellery', 'jewellery', NULL, NULL, 20, 1, '2026-05-26 12:08:29'),
(3, 'Refurbished Electronics and Appliances', 'refurbished-electronics-appliances', NULL, NULL, 30, 1, '2026-05-26 12:08:29'),
(4, 'Big Discount on Electronics and Appliances', 'big-discount-electronics-appliances', NULL, NULL, 40, 1, '2026-05-26 12:08:29'),
(5, 'Mega Discount on Factory Clearances', 'mega-discount-factory-clearances', NULL, NULL, 50, 1, '2026-05-26 12:08:29'),
(6, 'Automobile Two Wheeler and Four Wheeler', 'automobile-two-four-wheeler', NULL, NULL, 60, 1, '2026-05-26 12:08:29'),
(7, 'Furnitures', 'furnitures', NULL, NULL, 70, 1, '2026-05-26 12:08:29');

-- --------------------------------------------------------

--
-- Table structure for table `coupons`
--

CREATE TABLE `coupons` (
  `id` int(11) NOT NULL,
  `code` varchar(40) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `discount_type` enum('percent','fixed') NOT NULL DEFAULT 'percent',
  `discount_value` decimal(10,2) NOT NULL,
  `min_order_value` decimal(10,2) NOT NULL DEFAULT 0.00,
  `max_discount` decimal(10,2) DEFAULT NULL,
  `usage_limit` int(11) DEFAULT NULL,
  `used_count` int(11) NOT NULL DEFAULT 0,
  `expires_at` datetime DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `coupons`
--

INSERT INTO `coupons` (`id`, `code`, `description`, `discount_type`, `discount_value`, `min_order_value`, `max_discount`, `usage_limit`, `used_count`, `expires_at`, `is_active`, `created_at`) VALUES
(1, 'WELCOME10', NULL, 'percent', 10.00, 500.00, NULL, NULL, 0, NULL, 1, '2026-05-26 14:46:57');

-- --------------------------------------------------------

--
-- Table structure for table `delhivery_tracking_events`
--

CREATE TABLE `delhivery_tracking_events` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `waybill` varchar(50) NOT NULL,
  `scan_type` varchar(50) DEFAULT NULL,
  `scan_status` varchar(200) DEFAULT NULL,
  `location` varchar(200) DEFAULT NULL,
  `event_time` datetime DEFAULT NULL,
  `raw_json` text DEFAULT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `gate_entry`
--

CREATE TABLE `gate_entry` (
  `id` int(11) NOT NULL,
  `merchant_user_id` int(11) NOT NULL,
  `entry_no` varchar(50) NOT NULL,
  `supplier_name` varchar(150) NOT NULL,
  `vehicle_number` varchar(30) DEFAULT NULL,
  `driver_name` varchar(100) DEFAULT NULL,
  `driver_phone` varchar(30) DEFAULT NULL,
  `invoice_number` varchar(50) DEFAULT NULL,
  `invoice_date` date DEFAULT NULL,
  `invoice_packages` int(11) DEFAULT NULL,
  `dc_number` varchar(50) DEFAULT NULL,
  `dc_date` date DEFAULT NULL,
  `dc_packages` int(11) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `status` enum('received','processing','completed','rejected') DEFAULT 'received',
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `grn`
--

CREATE TABLE `grn` (
  `id` int(11) NOT NULL,
  `gate_entry_id` int(11) NOT NULL,
  `merchant_user_id` int(11) NOT NULL,
  `grn_no` varchar(50) NOT NULL,
  `supplier_name` varchar(150) NOT NULL,
  `invoice_number` varchar(50) DEFAULT NULL,
  `dc_number` varchar(50) DEFAULT NULL,
  `expected_qty_kg` decimal(10,2) NOT NULL DEFAULT 0.00,
  `received_qty_kg` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` enum('draft','pending_qc','qc_passed','qc_rejected','approved','completed') DEFAULT 'draft',
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `grn_inspection`
--

CREATE TABLE `grn_inspection` (
  `id` int(11) NOT NULL,
  `grn_id` int(11) NOT NULL,
  `inspection_type` enum('quality','quantity','condition') NOT NULL,
  `inspector_id` int(11) NOT NULL,
  `status` enum('pass','fail') NOT NULL,
  `remarks` text DEFAULT NULL,
  `inspected_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `grn_items`
--

CREATE TABLE `grn_items` (
  `id` int(11) NOT NULL,
  `grn_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `expected_qty_kg` decimal(10,2) NOT NULL,
  `received_qty_kg` decimal(10,2) NOT NULL DEFAULT 0.00,
  `qc_status` enum('pending','pass','fail') DEFAULT 'pending',
  `qc_remarks` text DEFAULT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inventory`
--

CREATE TABLE `inventory` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `qty_kg` decimal(10,2) NOT NULL DEFAULT 0.00,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inventory_movements`
--

CREATE TABLE `inventory_movements` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `movement_type` enum('inward','sale','adjustment') NOT NULL,
  `qty_kg` decimal(10,2) NOT NULL,
  `ref_type` varchar(30) DEFAULT NULL,
  `ref_id` int(11) DEFAULT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `logistics_profile`
--

CREATE TABLE `logistics_profile` (
  `id` int(11) NOT NULL,
  `logistics_user_id` int(11) NOT NULL,
  `company_name` varchar(150) DEFAULT NULL,
  `vehicle_no` varchar(50) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `merchant_profile`
--

CREATE TABLE `merchant_profile` (
  `id` int(11) NOT NULL,
  `merchant_user_id` int(11) NOT NULL,
  `status` enum('pending','approved','disabled') NOT NULL DEFAULT 'pending',
  `primary_category_id` int(11) DEFAULT NULL,
  `business_name` varchar(150) DEFAULT NULL,
  `business_address` text DEFAULT NULL,
  `business_gstin` varchar(20) DEFAULT NULL,
  `business_pan` varchar(20) DEFAULT NULL,
  `business_state` varchar(80) DEFAULT NULL,
  `business_pincode` varchar(12) DEFAULT NULL,
  `business_bank_name` varchar(120) DEFAULT NULL,
  `business_bank_account` varchar(50) DEFAULT NULL,
  `business_bank_ifsc` varchar(20) DEFAULT NULL,
  `business_logo_path` varchar(255) DEFAULT NULL,
  `razorpay_key_id` varchar(120) DEFAULT NULL,
  `razorpay_key_secret` varchar(200) DEFAULT NULL,
  `twilio_account_sid` varchar(120) DEFAULT NULL,
  `twilio_auth_token` varchar(120) DEFAULT NULL,
  `twilio_sms_from` varchar(30) DEFAULT NULL,
  `twilio_whatsapp_from` varchar(30) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `delhivery_api_token` varchar(255) DEFAULT NULL,
  `delhivery_mode` varchar(20) NOT NULL DEFAULT 'sandbox',
  `delhivery_warehouse_name` varchar(150) DEFAULT NULL,
  `delhivery_warehouse_address` text DEFAULT NULL,
  `delhivery_warehouse_city` varchar(80) DEFAULT NULL,
  `delhivery_warehouse_state` varchar(80) DEFAULT NULL,
  `delhivery_warehouse_pincode` varchar(12) DEFAULT NULL,
  `delhivery_warehouse_phone` varchar(30) DEFAULT NULL,
  `google_client_id` varchar(255) DEFAULT NULL,
  `google_client_secret` varchar(255) DEFAULT NULL,
  `smtp_host` varchar(150) DEFAULT 'smtp.gmail.com',
  `smtp_port` int(11) DEFAULT 587,
  `smtp_username` varchar(190) DEFAULT NULL,
  `smtp_password` varchar(190) DEFAULT NULL,
  `smtp_from_email` varchar(190) DEFAULT NULL,
  `smtp_from_name` varchar(150) DEFAULT NULL,
  `home_bg_color` varchar(20) DEFAULT '#fff8f0',
  `home_bg_image` varchar(255) DEFAULT NULL,
  `referral_referrer_pct` decimal(5,2) NOT NULL DEFAULT 5.00,
  `referral_referee_pct` decimal(5,2) NOT NULL DEFAULT 10.00,
  `referral_max_per_user` int(11) NOT NULL DEFAULT 5,
  `hero_eyebrow` varchar(120) DEFAULT NULL,
  `hero_title_main` varchar(180) DEFAULT NULL,
  `hero_title_sub` varchar(180) DEFAULT NULL,
  `hero_description` text DEFAULT NULL,
  `hero_discount_text` varchar(120) DEFAULT NULL,
  `hero_discount_sub` varchar(120) DEFAULT NULL,
  `hero_cta_text` varchar(60) DEFAULT NULL,
  `hero_cta_link` varchar(255) DEFAULT NULL,
  `promo1_title` varchar(180) DEFAULT NULL,
  `promo1_description` text DEFAULT NULL,
  `promo1_cta_text` varchar(60) DEFAULT NULL,
  `promo1_cta_link` varchar(255) DEFAULT NULL,
  `promo1_emoji` varchar(20) DEFAULT NULL,
  `promo1_active` tinyint(1) NOT NULL DEFAULT 1,
  `promo2_title` varchar(180) DEFAULT NULL,
  `promo2_description` text DEFAULT NULL,
  `promo2_cta_text` varchar(60) DEFAULT NULL,
  `promo2_cta_link` varchar(255) DEFAULT NULL,
  `promo2_emoji` varchar(20) DEFAULT NULL,
  `promo2_active` tinyint(1) NOT NULL DEFAULT 1,
  `logistics_vendor_id` int(11) DEFAULT NULL,
  `logistics_service_charge` decimal(10,2) DEFAULT 0.00,
  `logistics_service_charge_type` enum('flat','percent') DEFAULT 'flat',
  `logistics_onboarded_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `merchant_profile`
--

INSERT INTO `merchant_profile` (`id`, `merchant_user_id`, `status`, `primary_category_id`, `business_name`, `business_address`, `business_gstin`, `business_pan`, `business_state`, `business_pincode`, `business_bank_name`, `business_bank_account`, `business_bank_ifsc`, `business_logo_path`, `razorpay_key_id`, `razorpay_key_secret`, `twilio_account_sid`, `twilio_auth_token`, `twilio_sms_from`, `twilio_whatsapp_from`, `created_at`, `delhivery_api_token`, `delhivery_mode`, `delhivery_warehouse_name`, `delhivery_warehouse_address`, `delhivery_warehouse_city`, `delhivery_warehouse_state`, `delhivery_warehouse_pincode`, `delhivery_warehouse_phone`, `google_client_id`, `google_client_secret`, `smtp_host`, `smtp_port`, `smtp_username`, `smtp_password`, `smtp_from_email`, `smtp_from_name`, `home_bg_color`, `home_bg_image`, `referral_referrer_pct`, `referral_referee_pct`, `referral_max_per_user`, `hero_eyebrow`, `hero_title_main`, `hero_title_sub`, `hero_description`, `hero_discount_text`, `hero_discount_sub`, `hero_cta_text`, `hero_cta_link`, `promo1_title`, `promo1_description`, `promo1_cta_text`, `promo1_cta_link`, `promo1_emoji`, `promo1_active`, `promo2_title`, `promo2_description`, `promo2_cta_text`, `promo2_cta_link`, `promo2_emoji`, `promo2_active`, `logistics_vendor_id`, `logistics_service_charge`, `logistics_service_charge_type`, `logistics_onboarded_at`) VALUES
(1, 2, 'approved', 1, 'Test Bazaar', '123 Test Street', NULL, NULL, 'Karnataka', '560011', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-05-26 12:24:24', NULL, 'sandbox', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'smtp.gmail.com', 587, NULL, NULL, NULL, NULL, '#fff8f0', NULL, 5.00, 10.00, 5, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, NULL, 1, NULL, 0.00, 'flat', NULL),
(2, 3, 'approved', 2, 'Sparkle Jewels', '456 Gem Lane', NULL, NULL, 'Karnataka', '560020', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-05-26 12:38:01', NULL, 'sandbox', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'smtp.gmail.com', 587, NULL, NULL, NULL, NULL, '#fff8f0', NULL, 5.00, 10.00, 5, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, NULL, 1, NULL, 0.00, 'flat', NULL),
(3, 5, 'disabled', NULL, 'KINGMANGO', 'venkatagowda layout', NULL, NULL, 'Karnataka', '560024', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-05-26 13:41:52', NULL, 'sandbox', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'smtp.gmail.com', 587, NULL, NULL, NULL, NULL, '#fff8f0', NULL, 5.00, 10.00, 5, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, NULL, 1, NULL, 0.00, 'flat', NULL),
(4, 6, 'approved', NULL, 'KINGMANGO', 'venkatagowda layout', NULL, NULL, 'Karnataka', '560024', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-05-26 13:53:01', NULL, 'sandbox', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'smtp.gmail.com', 587, NULL, NULL, NULL, NULL, '#fff8f0', NULL, 5.00, 10.00, 5, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, NULL, 1, NULL, 0.00, 'flat', NULL),
(5, 1, 'disabled', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-05-26 14:02:01', NULL, 'sandbox', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'smtp.gmail.com', 587, NULL, NULL, NULL, NULL, '#fff8f0', NULL, 5.00, 10.00, 5, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, NULL, 1, NULL, 0.00, 'flat', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `notification_logs`
--

CREATE TABLE `notification_logs` (
  `id` int(11) NOT NULL,
  `channel` enum('sms','whatsapp','email') NOT NULL,
  `to_address` varchar(190) NOT NULL,
  `message` text NOT NULL,
  `provider` enum('twilio','smtp') NOT NULL DEFAULT 'smtp',
  `provider_message_id` varchar(120) DEFAULT NULL,
  `order_id` int(11) DEFAULT NULL,
  `merchant_id` int(11) DEFAULT NULL,
  `recipient_role` enum('buyer','merchant') DEFAULT NULL,
  `status` enum('queued','sent','failed') NOT NULL DEFAULT 'queued',
  `error_message` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notification_logs`
--

INSERT INTO `notification_logs` (`id`, `channel`, `to_address`, `message`, `provider`, `provider_message_id`, `order_id`, `merchant_id`, `recipient_role`, `status`, `error_message`, `created_at`) VALUES
(1, 'email', 'testmerchant@example.com', 'New order #MM260526143301441-1 on Test Bazaar\n\nTest BazaarYou have a new orderA buyer just placed an order with Test Bazaar. Log in to your dashboard to confirm and arrange shipment.Order number: MM260526143301441-1ItemQtyUnitLineTest Cotton Shirt2.00&#8377;599.00&#8377;1,248.00Subtotal: &#8377;1,198.00Shipping: &#8377;50.00Total: &#8377;1,248.00Delivery addressTest Buyer789 Buyer Lane, Bangalore, Karnataka &mdash; 560001Phone: +919000000000Manikya Market &middot; You are receiving this because of an order on your store.', 'smtp', NULL, 3, 2, 'merchant', 'failed', 'via platform: SMTP Error: Could not authenticate. | SMTP Error: Could not authenticate.', '2026-05-26 14:33:04'),
(2, 'email', 'buyer@example.com', 'Order #MM260526143301441-1 confirmed\n\nManikya MarketThanks Test Buyer, your order is confirmed.Sold by Test Bazaar. We will notify you again when it ships.Order number: MM260526143301441-1ItemQtyUnitLineTest Cotton Shirt2.00&#8377;599.00&#8377;1,248.00Subtotal: &#8377;1,198.00Shipping: &#8377;50.00Total: &#8377;1,248.00Delivery addressTest Buyer789 Buyer Lane, Bangalore, Karnataka &mdash; 560001Phone: +919000000000Manikya Market &middot; You are receiving this because of an order on your store.', 'smtp', NULL, 3, 2, 'buyer', 'failed', 'via platform: SMTP Error: Could not authenticate. | SMTP Error: Could not authenticate.', '2026-05-26 14:33:08'),
(3, 'email', 'merchant2@example.com', 'New order #MM260526143301441-2 on Sparkle Jewels\n\nSparkle JewelsYou have a new orderA buyer just placed an order with Sparkle Jewels. Log in to your dashboard to confirm and arrange shipment.Order number: MM260526143301441-2ItemQtyUnitLineGold Plated Earrings1.00&#8377;1,500.00&#8377;1,530.00Subtotal: &#8377;1,500.00Shipping: &#8377;30.00Total: &#8377;1,530.00Delivery addressTest Buyer789 Buyer Lane, Bangalore, Karnataka &mdash; 560001Phone: +919000000000Manikya Market &middot; You are receiving this because of an order on your store.', 'smtp', NULL, 4, 3, 'merchant', 'failed', 'via platform: SMTP Error: Could not authenticate. | SMTP Error: Could not authenticate.', '2026-05-26 14:33:11'),
(4, 'email', 'buyer@example.com', 'Order #MM260526143301441-2 confirmed\n\nManikya MarketThanks Test Buyer, your order is confirmed.Sold by Sparkle Jewels. We will notify you again when it ships.Order number: MM260526143301441-2ItemQtyUnitLineGold Plated Earrings1.00&#8377;1,500.00&#8377;1,530.00Subtotal: &#8377;1,500.00Shipping: &#8377;30.00Total: &#8377;1,530.00Delivery addressTest Buyer789 Buyer Lane, Bangalore, Karnataka &mdash; 560001Phone: +919000000000Manikya Market &middot; You are receiving this because of an order on your store.', 'smtp', NULL, 4, 3, 'buyer', 'failed', 'via platform: SMTP Error: Could not authenticate. | SMTP Error: Could not authenticate.', '2026-05-26 14:33:14'),
(5, 'email', 'buyer@example.com', 'Order #MM260526143301441-1 shipped\n\nManikya MarketYour order from Test Bazaar is on its way.Tracking will be available shortly.Order number: MM260526143301441-1ItemQtyUnitLineTest Cotton Shirt2.00&#8377;599.00&#8377;1,248.00Subtotal: &#8377;1,198.00Shipping: &#8377;50.00Total: &#8377;1,248.00Delivery addressTest Buyer789 Buyer Lane, Bangalore, Karnataka &mdash; 560001Phone: +919000000000Manikya Market &middot; You are receiving this because of an order on your store.', 'smtp', NULL, 3, 2, 'buyer', 'failed', 'via platform: SMTP Error: Could not authenticate. | SMTP Error: Could not authenticate.', '2026-05-26 14:33:35'),
(6, 'email', 'buyer@example.com', 'Order #MM260526143301441-1 delivered\n\nManikya MarketYour order has been delivered.Thanks for shopping Test Bazaar on Manikya Market.Order number: MM260526143301441-1ItemQtyUnitLineTest Cotton Shirt2.00&#8377;599.00&#8377;1,248.00Subtotal: &#8377;1,198.00Shipping: &#8377;50.00Total: &#8377;1,248.00Delivery addressTest Buyer789 Buyer Lane, Bangalore, Karnataka &mdash; 560001Phone: +919000000000Manikya Market &middot; You are receiving this because of an order on your store.', 'smtp', NULL, 3, 2, 'buyer', 'failed', 'via platform: SMTP Error: Could not authenticate. | SMTP Error: Could not authenticate.', '2026-05-26 14:33:38');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `order_no` varchar(30) NOT NULL,
  `parent_order_no` varchar(40) DEFAULT NULL,
  `buyer_id` int(11) NOT NULL,
  `merchant_id` int(11) DEFAULT NULL,
  `status` enum('new','paid','packed','ready_to_pick','shipped','in_transit','delivered','cancelled','return_requested','returned','refunded') NOT NULL DEFAULT 'new',
  `subtotal_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `shipping_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `commission_pct` decimal(5,2) DEFAULT NULL,
  `commission_amount` decimal(10,2) DEFAULT NULL,
  `merchant_payable` decimal(10,2) DEFAULT NULL,
  `delivery_address_id` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `invoice_file` varchar(255) DEFAULT NULL,
  `invoice_generated_at` datetime DEFAULT NULL,
  `delhivery_waybill` varchar(50) DEFAULT NULL,
  `delhivery_shipment_id` varchar(100) DEFAULT NULL,
  `delhivery_status` varchar(80) DEFAULT NULL,
  `delhivery_status_synced_at` datetime DEFAULT NULL,
  `delhivery_pickup_token` varchar(100) DEFAULT NULL,
  `discount_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `coupon_id` int(11) DEFAULT NULL,
  `coupon_code` varchar(40) DEFAULT NULL,
  `coupon_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `tax_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `wallet_deduction` decimal(10,2) NOT NULL DEFAULT 0.00,
  `cancelled_at` datetime DEFAULT NULL,
  `cancel_reason` varchar(255) DEFAULT NULL,
  `returned_at` datetime DEFAULT NULL,
  `return_reason` varchar(255) DEFAULT NULL,
  `delivered_at` datetime DEFAULT NULL,
  `logistics_user_id` int(11) DEFAULT NULL,
  `shipped_at` datetime DEFAULT NULL,
  `shipping_tracking_no` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `order_no`, `parent_order_no`, `buyer_id`, `merchant_id`, `status`, `subtotal_amount`, `shipping_amount`, `total_amount`, `commission_pct`, `commission_amount`, `merchant_payable`, `delivery_address_id`, `created_at`, `invoice_file`, `invoice_generated_at`, `delhivery_waybill`, `delhivery_shipment_id`, `delhivery_status`, `delhivery_status_synced_at`, `delhivery_pickup_token`, `discount_amount`, `coupon_id`, `coupon_code`, `coupon_amount`, `tax_amount`, `wallet_deduction`, `cancelled_at`, `cancel_reason`, `returned_at`, `return_reason`, `delivered_at`, `logistics_user_id`, `shipped_at`, `shipping_tracking_no`) VALUES
(3, 'MM260526143301441-1', 'MM260526143301441', 4, 2, 'delivered', 1198.00, 50.00, 1248.00, 10.00, 124.80, 1123.20, 1, '2026-05-26 14:33:01', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, NULL, NULL, 0.00, 0.00, 0.00, NULL, NULL, NULL, NULL, '2026-05-26 14:33:35', NULL, '2026-05-26 14:33:32', NULL),
(4, 'MM260526143301441-2', 'MM260526143301441', 4, 3, 'new', 1500.00, 30.00, 1530.00, NULL, NULL, NULL, 1, '2026-05-26 14:33:08', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, NULL, NULL, 0.00, 0.00, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `qty_kg` decimal(10,2) NOT NULL,
  `price_per_kg` decimal(10,2) NOT NULL,
  `line_total` decimal(10,2) NOT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `qty_kg`, `price_per_kg`, `line_total`, `created_at`) VALUES
(3, 3, 1, 2.00, 599.00, 1248.00, '2026-05-26 14:33:01'),
(4, 4, 2, 1.00, 1500.00, 1530.00, '2026-05-26 14:33:08');

-- --------------------------------------------------------

--
-- Table structure for table `order_tracking`
--

CREATE TABLE `order_tracking` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `status` varchar(50) NOT NULL,
  `updated_by_type` enum('merchant','logistics','admin') NOT NULL DEFAULT 'merchant',
  `updated_by_id` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_tracking`
--

INSERT INTO `order_tracking` (`id`, `order_id`, `status`, `updated_by_type`, `updated_by_id`, `notes`, `updated_at`) VALUES
(1, 3, 'shipped', 'merchant', 2, 'Status updated to shipped', '2026-05-26 09:03:35'),
(2, 3, 'delivered', 'merchant', 2, 'Status updated to delivered', '2026-05-26 09:03:38');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `provider` enum('razorpay','cod') NOT NULL DEFAULT 'razorpay',
  `status` enum('initiated','paid','failed') NOT NULL DEFAULT 'initiated',
  `provider_order_id` varchar(120) DEFAULT NULL,
  `provider_payment_id` varchar(120) DEFAULT NULL,
  `provider_signature` varchar(255) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_at` datetime NOT NULL,
  `razorpay_order_id` varchar(60) DEFAULT NULL,
  `razorpay_payment_id` varchar(60) DEFAULT NULL,
  `razorpay_signature` varchar(190) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `order_id`, `provider`, `status`, `provider_order_id`, `provider_payment_id`, `provider_signature`, `amount`, `created_at`, `razorpay_order_id`, `razorpay_payment_id`, `razorpay_signature`) VALUES
(1, 3, 'cod', 'initiated', NULL, NULL, NULL, 1248.00, '2026-05-26 14:33:01', NULL, NULL, NULL),
(2, 4, 'cod', 'initiated', NULL, NULL, NULL, 1530.00, '2026-05-26 14:33:08', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `payouts`
--

CREATE TABLE `payouts` (
  `id` int(11) NOT NULL,
  `merchant_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `status` enum('pending','paid','failed') NOT NULL DEFAULT 'paid',
  `method` enum('bank_transfer','razorpay_payout','upi','cash','other') NOT NULL DEFAULT 'bank_transfer',
  `reference_no` varchar(120) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `paid_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `platform_settings`
--

CREATE TABLE `platform_settings` (
  `id` int(11) NOT NULL,
  `commission_pct` decimal(5,2) NOT NULL DEFAULT 10.00,
  `gst_rate_pct` decimal(5,2) NOT NULL DEFAULT 18.00,
  `razorpay_key_id` varchar(120) DEFAULT NULL,
  `razorpay_key_secret` varchar(200) DEFAULT NULL,
  `bank_name` varchar(120) DEFAULT NULL,
  `bank_account` varchar(50) DEFAULT NULL,
  `bank_ifsc` varchar(20) DEFAULT NULL,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `delhivery_api_token` varchar(255) DEFAULT NULL,
  `delhivery_mode` varchar(20) NOT NULL DEFAULT 'sandbox',
  `delhivery_warehouse_name` varchar(150) DEFAULT NULL,
  `delhivery_warehouse_address` text DEFAULT NULL,
  `delhivery_warehouse_city` varchar(80) DEFAULT NULL,
  `delhivery_warehouse_state` varchar(80) DEFAULT NULL,
  `delhivery_warehouse_pincode` varchar(12) DEFAULT NULL,
  `delhivery_warehouse_phone` varchar(30) DEFAULT NULL,
  `smtp_host` varchar(150) DEFAULT 'smtp.gmail.com',
  `smtp_port` int(11) DEFAULT 587,
  `smtp_username` varchar(190) DEFAULT NULL,
  `smtp_password` varchar(190) DEFAULT NULL,
  `smtp_from_email` varchar(190) DEFAULT NULL,
  `smtp_from_name` varchar(150) DEFAULT 'Manikya Market'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `platform_settings`
--

INSERT INTO `platform_settings` (`id`, `commission_pct`, `gst_rate_pct`, `razorpay_key_id`, `razorpay_key_secret`, `bank_name`, `bank_account`, `bank_ifsc`, `updated_at`, `delhivery_api_token`, `delhivery_mode`, `delhivery_warehouse_name`, `delhivery_warehouse_address`, `delhivery_warehouse_city`, `delhivery_warehouse_state`, `delhivery_warehouse_pincode`, `delhivery_warehouse_phone`, `smtp_host`, `smtp_port`, `smtp_username`, `smtp_password`, `smtp_from_email`, `smtp_from_name`) VALUES
(1, 10.00, 18.00, 'rzp_test_DUMMY_PLATFORM', 'platformSecret123', NULL, NULL, NULL, '2026-05-26 14:32:23', 'plat-delh-token', 'sandbox', NULL, NULL, NULL, NULL, '560001', NULL, 'smtp.gmail.com', 587, 'platform@example.com', 'app-password-here', 'platform@example.com', 'Manikya Market');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `merchant_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `product_type` varchar(80) DEFAULT NULL,
  `part_code` varchar(30) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `price_per_kg` decimal(10,2) NOT NULL DEFAULT 0.00,
  `shipping_type` enum('flat','per_kg') NOT NULL DEFAULT 'flat',
  `shipping_rate` decimal(10,2) NOT NULL DEFAULT 0.00,
  `stock_kg` decimal(10,2) NOT NULL DEFAULT 0.00,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `merchant_id`, `category_id`, `name`, `product_type`, `part_code`, `description`, `image_path`, `price_per_kg`, `shipping_type`, `shipping_rate`, `stock_kg`, `is_active`, `created_at`) VALUES
(1, 2, 1, 'Test Cotton Shirt', 'Men\'s shirt', NULL, 'Sample product for E2E test', NULL, 599.00, 'flat', 50.00, 10.00, 1, '2026-05-26 12:24:46'),
(2, 3, 2, 'Gold Plated Earrings', 'Jewellery', NULL, 'Sample multi-merchant test', NULL, 1500.00, 'flat', 30.00, 5.00, 1, '2026-05-26 12:38:01');

-- --------------------------------------------------------

--
-- Table structure for table `ratings`
--

CREATE TABLE `ratings` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `buyer_id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `rating` int(11) NOT NULL,
  `feedback` text DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `helpful_votes` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rating_helpful`
--

CREATE TABLE `rating_helpful` (
  `rating_id` int(11) NOT NULL,
  `buyer_id` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `referrals`
--

CREATE TABLE `referrals` (
  `id` int(11) NOT NULL,
  `referrer_id` int(11) NOT NULL,
  `referee_id` int(11) NOT NULL,
  `status` enum('pending','completed','expired') NOT NULL DEFAULT 'pending',
  `referee_discount_pct` decimal(5,2) NOT NULL DEFAULT 10.00,
  `referrer_reward_pct` decimal(5,2) NOT NULL DEFAULT 5.00,
  `referee_order_id` int(11) DEFAULT NULL,
  `discount_amount` decimal(10,2) DEFAULT NULL,
  `reward_amount` decimal(10,2) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `completed_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `referral_codes`
--

CREATE TABLE `referral_codes` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `code` varchar(10) NOT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `returns`
--

CREATE TABLE `returns` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `buyer_id` int(11) NOT NULL,
  `merchant_id` int(11) DEFAULT NULL,
  `reason` varchar(255) NOT NULL,
  `details` text DEFAULT NULL,
  `status` enum('requested','approved','rejected','received','refunded') NOT NULL DEFAULT 'requested',
  `decision_notes` text DEFAULT NULL,
  `refund_amount` decimal(10,2) DEFAULT NULL,
  `requested_at` datetime NOT NULL DEFAULT current_timestamp(),
  `decided_at` datetime DEFAULT NULL,
  `refunded_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tickets`
--

CREATE TABLE `tickets` (
  `id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `buyer_id` int(11) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `status` enum('open','pending','resolved','escalated') NOT NULL DEFAULT 'open',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tickets`
--

INSERT INTO `tickets` (`id`, `order_id`, `buyer_id`, `subject`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 4, 'Item arrived damaged', 'escalated', '2026-05-23 14:18:34', '2026-05-26 14:21:07'),
(2, 2, 4, 'Where is my order?', 'pending', '2026-05-25 14:18:34', '2026-05-26 02:18:34');

-- --------------------------------------------------------

--
-- Table structure for table `ticket_messages`
--

CREATE TABLE `ticket_messages` (
  `id` int(11) NOT NULL,
  `ticket_id` int(11) NOT NULL,
  `sender_type` enum('buyer','merchant','super_admin') NOT NULL,
  `sender_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ticket_messages`
--

INSERT INTO `ticket_messages` (`id`, `ticket_id`, `sender_type`, `sender_id`, `message`, `image_path`, `created_at`) VALUES
(1, 1, 'buyer', 4, 'The shirt I received has a tear near the collar. Please advise.', NULL, '2026-05-23 14:18:34'),
(2, 2, 'buyer', 4, 'Hi, when will my earrings arrive?', NULL, '2026-05-25 14:18:34'),
(3, 2, 'merchant', 3, 'Hi, your order is packed and will ship tomorrow.', NULL, '2026-05-26 02:18:34'),
(4, 1, 'super_admin', 1, 'Hi, Manikya Market support stepping in. Could you share a photo of the damage?', NULL, '2026-05-26 14:18:34');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `role` enum('buyer','merchant','logistics','super_admin') NOT NULL,
  `full_name` varchar(120) NOT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `email` varchar(190) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `status` enum('active','disabled') NOT NULL DEFAULT 'active',
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `role`, `full_name`, `phone`, `email`, `password_hash`, `status`, `created_at`) VALUES
(1, 'super_admin', 'Prashanth (Super Admin)', NULL, 'YOUR_ADMIN_EMAIL', '$2y$10$Z1O1lLdMKfyW48EYv0YgxeT1tBMbPMkppGp.vJfJLPR4wz.1WU87O', 'active', '2026-05-26 12:08:29'),
(2, 'merchant', 'Test Merchant', '+919999999999', 'testmerchant@example.com', '$2y$10$yJiPnSgTekV0s2ZR0rMhpeMGGeLW1ZnMK8THFrUN/jNwldYBUCFsO', 'active', '2026-05-26 12:24:24'),
(3, 'merchant', 'Second Bazaar', NULL, 'merchant2@example.com', '$2y$10$Z1O1lLdMKfyW48EYv0YgxeT1tBMbPMkppGp.vJfJLPR4wz.1WU87O', 'active', '2026-05-26 12:38:01'),
(4, 'buyer', 'Test Buyer', NULL, 'buyer@example.com', '$2y$10$Z1O1lLdMKfyW48EYv0YgxeT1tBMbPMkppGp.vJfJLPR4wz.1WU87O', 'active', '2026-05-26 12:38:01'),
(5, 'merchant', 'hari kishan', '+911234567890', 'harikishan89198@gmail.com', '$2y$10$RI.s76dnF63NZ0Dcbv9NiOSobNKEWsq8NbbZqLD.0IKzO3fV16erC', 'active', '2026-05-26 13:41:52'),
(6, 'merchant', 'hari kishan', '+911234567890', 'harikishan8584@gmail.com', '$2y$10$SqHYaX2.z1M5wXcQExEsDu.eiBOVB7L8fcG94DSk3.sEZRHWiPavS', 'active', '2026-05-26 13:53:01');

-- --------------------------------------------------------

--
-- Table structure for table `wallet_transactions`
--

CREATE TABLE `wallet_transactions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` enum('credit','debit') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `ref_type` varchar(30) DEFAULT NULL,
  `ref_id` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `wallet_transactions`
--

INSERT INTO `wallet_transactions` (`id`, `user_id`, `type`, `amount`, `description`, `ref_type`, `ref_id`, `created_at`) VALUES
(1, 2, 'credit', 1123.20, 'Earnings from order #MM260526143301441-1 (net of 10.00% platform fee)', 'order', 3, '2026-05-26 14:33:35'),
(2, 1, 'credit', 124.80, 'Commission from order #MM260526143301441-1 (10.00%)', 'commission', 3, '2026-05-26 14:33:35');

-- --------------------------------------------------------

--
-- Table structure for table `wishlists`
--

CREATE TABLE `wishlists` (
  `id` int(11) NOT NULL,
  `buyer_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `wishlists`
--

INSERT INTO `wishlists` (`id`, `buyer_id`, `product_id`, `created_at`) VALUES
(1, 4, 1, '2026-05-26 14:46:57');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `buyer_addresses`
--
ALTER TABLE `buyer_addresses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_addr_buyer` (`buyer_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_categories_slug` (`slug`);

--
-- Indexes for table `coupons`
--
ALTER TABLE `coupons`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_coupons_code` (`code`);

--
-- Indexes for table `delhivery_tracking_events`
--
ALTER TABLE `delhivery_tracking_events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_dte_order` (`order_id`),
  ADD KEY `idx_dte_waybill` (`waybill`);

--
-- Indexes for table `gate_entry`
--
ALTER TABLE `gate_entry`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `entry_no` (`entry_no`),
  ADD KEY `idx_gate_merchant` (`merchant_user_id`);

--
-- Indexes for table `grn`
--
ALTER TABLE `grn`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `grn_no` (`grn_no`),
  ADD KEY `fk_grn_gate` (`gate_entry_id`),
  ADD KEY `fk_grn_approver` (`approved_by`),
  ADD KEY `idx_grn_merchant` (`merchant_user_id`),
  ADD KEY `idx_grn_status` (`status`);

--
-- Indexes for table `grn_inspection`
--
ALTER TABLE `grn_inspection`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_insp_grn` (`grn_id`),
  ADD KEY `fk_insp_user` (`inspector_id`);

--
-- Indexes for table `grn_items`
--
ALTER TABLE `grn_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_grn_item_grn` (`grn_id`),
  ADD KEY `fk_grn_item_product` (`product_id`);

--
-- Indexes for table `inventory`
--
ALTER TABLE `inventory`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `product_id` (`product_id`);

--
-- Indexes for table `inventory_movements`
--
ALTER TABLE `inventory_movements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_inv_mov_product` (`product_id`),
  ADD KEY `idx_inv_mov_ref` (`ref_type`,`ref_id`);

--
-- Indexes for table `logistics_profile`
--
ALTER TABLE `logistics_profile`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `logistics_user_id` (`logistics_user_id`);

--
-- Indexes for table `merchant_profile`
--
ALTER TABLE `merchant_profile`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `merchant_user_id` (`merchant_user_id`),
  ADD KEY `idx_merchant_profile_status` (`status`),
  ADD KEY `idx_merchant_profile_primary_category` (`primary_category_id`);

--
-- Indexes for table `notification_logs`
--
ALTER TABLE `notification_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_notif_order` (`order_id`),
  ADD KEY `idx_notif_merchant` (`merchant_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_no` (`order_no`),
  ADD KEY `fk_order_buyer` (`buyer_id`),
  ADD KEY `fk_order_addr` (`delivery_address_id`),
  ADD KEY `idx_orders_merchant` (`merchant_id`),
  ADD KEY `idx_orders_parent` (`parent_order_no`),
  ADD KEY `idx_orders_coupon` (`coupon_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_item_order` (`order_id`),
  ADD KEY `fk_item_product` (`product_id`);

--
-- Indexes for table `order_tracking`
--
ALTER TABLE `order_tracking`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_order_id` (`order_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_updated_at` (`updated_at`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_payment_order` (`order_id`);

--
-- Indexes for table `payouts`
--
ALTER TABLE `payouts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_payouts_merchant` (`merchant_id`),
  ADD KEY `idx_payouts_status` (`status`),
  ADD KEY `fk_payouts_created_by` (`created_by`);

--
-- Indexes for table `platform_settings`
--
ALTER TABLE `platform_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_products_part_code` (`part_code`),
  ADD KEY `idx_products_merchant` (`merchant_id`),
  ADD KEY `idx_products_category` (`category_id`);

--
-- Indexes for table `ratings`
--
ALTER TABLE `ratings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_ratings_order` (`order_id`),
  ADD KEY `idx_ratings_product` (`product_id`);

--
-- Indexes for table `rating_helpful`
--
ALTER TABLE `rating_helpful`
  ADD PRIMARY KEY (`rating_id`,`buyer_id`),
  ADD KEY `fk_rating_helpful_buyer` (`buyer_id`);

--
-- Indexes for table `referrals`
--
ALTER TABLE `referrals`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `referee_id` (`referee_id`),
  ADD KEY `fk_ref_referrer` (`referrer_id`);

--
-- Indexes for table `referral_codes`
--
ALTER TABLE `referral_codes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `returns`
--
ALTER TABLE `returns`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_returns_order` (`order_id`),
  ADD KEY `idx_returns_status` (`status`),
  ADD KEY `fk_returns_buyer` (`buyer_id`),
  ADD KEY `fk_returns_merchant` (`merchant_id`);

--
-- Indexes for table `tickets`
--
ALTER TABLE `tickets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_tickets_buyer` (`buyer_id`),
  ADD KEY `idx_tickets_order` (`order_id`),
  ADD KEY `idx_tickets_status` (`status`);

--
-- Indexes for table `ticket_messages`
--
ALTER TABLE `ticket_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ticket_messages_ticket` (`ticket_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `wallet_transactions`
--
ALTER TABLE `wallet_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_wallet_user` (`user_id`);

--
-- Indexes for table `wishlists`
--
ALTER TABLE `wishlists`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_wishlists_buyer_product` (`buyer_id`,`product_id`),
  ADD KEY `idx_wishlists_buyer` (`buyer_id`),
  ADD KEY `fk_wishlists_product` (`product_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `buyer_addresses`
--
ALTER TABLE `buyer_addresses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `coupons`
--
ALTER TABLE `coupons`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `delhivery_tracking_events`
--
ALTER TABLE `delhivery_tracking_events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gate_entry`
--
ALTER TABLE `gate_entry`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `grn`
--
ALTER TABLE `grn`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `grn_inspection`
--
ALTER TABLE `grn_inspection`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `grn_items`
--
ALTER TABLE `grn_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `inventory`
--
ALTER TABLE `inventory`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `inventory_movements`
--
ALTER TABLE `inventory_movements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `logistics_profile`
--
ALTER TABLE `logistics_profile`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `merchant_profile`
--
ALTER TABLE `merchant_profile`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `notification_logs`
--
ALTER TABLE `notification_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `order_tracking`
--
ALTER TABLE `order_tracking`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `payouts`
--
ALTER TABLE `payouts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `platform_settings`
--
ALTER TABLE `platform_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `ratings`
--
ALTER TABLE `ratings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `referrals`
--
ALTER TABLE `referrals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `referral_codes`
--
ALTER TABLE `referral_codes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `returns`
--
ALTER TABLE `returns`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tickets`
--
ALTER TABLE `tickets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `ticket_messages`
--
ALTER TABLE `ticket_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `wallet_transactions`
--
ALTER TABLE `wallet_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `wishlists`
--
ALTER TABLE `wishlists`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `buyer_addresses`
--
ALTER TABLE `buyer_addresses`
  ADD CONSTRAINT `fk_addr_buyer` FOREIGN KEY (`buyer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `delhivery_tracking_events`
--
ALTER TABLE `delhivery_tracking_events`
  ADD CONSTRAINT `fk_dte_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `gate_entry`
--
ALTER TABLE `gate_entry`
  ADD CONSTRAINT `fk_gate_merchant` FOREIGN KEY (`merchant_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `grn`
--
ALTER TABLE `grn`
  ADD CONSTRAINT `fk_grn_approver` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_grn_gate` FOREIGN KEY (`gate_entry_id`) REFERENCES `gate_entry` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_grn_merchant` FOREIGN KEY (`merchant_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `grn_inspection`
--
ALTER TABLE `grn_inspection`
  ADD CONSTRAINT `fk_insp_grn` FOREIGN KEY (`grn_id`) REFERENCES `grn` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_insp_user` FOREIGN KEY (`inspector_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `grn_items`
--
ALTER TABLE `grn_items`
  ADD CONSTRAINT `fk_grn_item_grn` FOREIGN KEY (`grn_id`) REFERENCES `grn` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_grn_item_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `inventory`
--
ALTER TABLE `inventory`
  ADD CONSTRAINT `fk_inventory_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `inventory_movements`
--
ALTER TABLE `inventory_movements`
  ADD CONSTRAINT `fk_inv_mov_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `logistics_profile`
--
ALTER TABLE `logistics_profile`
  ADD CONSTRAINT `fk_logistics_user` FOREIGN KEY (`logistics_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `merchant_profile`
--
ALTER TABLE `merchant_profile`
  ADD CONSTRAINT `fk_merchant_profile_primary_category` FOREIGN KEY (`primary_category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_mp_merchant` FOREIGN KEY (`merchant_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `fk_order_addr` FOREIGN KEY (`delivery_address_id`) REFERENCES `buyer_addresses` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_order_buyer` FOREIGN KEY (`buyer_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `fk_orders_coupon` FOREIGN KEY (`coupon_id`) REFERENCES `coupons` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_orders_merchant` FOREIGN KEY (`merchant_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `fk_item_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_item_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `order_tracking`
--
ALTER TABLE `order_tracking`
  ADD CONSTRAINT `fk_tracking_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `fk_payment_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payouts`
--
ALTER TABLE `payouts`
  ADD CONSTRAINT `fk_payouts_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_payouts_merchant` FOREIGN KEY (`merchant_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `fk_products_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`),
  ADD CONSTRAINT `fk_products_merchant` FOREIGN KEY (`merchant_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `rating_helpful`
--
ALTER TABLE `rating_helpful`
  ADD CONSTRAINT `fk_rating_helpful_buyer` FOREIGN KEY (`buyer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_rating_helpful_rating` FOREIGN KEY (`rating_id`) REFERENCES `ratings` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `referrals`
--
ALTER TABLE `referrals`
  ADD CONSTRAINT `fk_ref_referee` FOREIGN KEY (`referee_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ref_referrer` FOREIGN KEY (`referrer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `referral_codes`
--
ALTER TABLE `referral_codes`
  ADD CONSTRAINT `fk_refcode_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `returns`
--
ALTER TABLE `returns`
  ADD CONSTRAINT `fk_returns_buyer` FOREIGN KEY (`buyer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_returns_merchant` FOREIGN KEY (`merchant_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_returns_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tickets`
--
ALTER TABLE `tickets`
  ADD CONSTRAINT `fk_tickets_buyer` FOREIGN KEY (`buyer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_tickets_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `ticket_messages`
--
ALTER TABLE `ticket_messages`
  ADD CONSTRAINT `fk_ticket_messages_ticket` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `wallet_transactions`
--
ALTER TABLE `wallet_transactions`
  ADD CONSTRAINT `fk_wallet_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `wishlists`
--
ALTER TABLE `wishlists`
  ADD CONSTRAINT `fk_wishlists_buyer` FOREIGN KEY (`buyer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_wishlists_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
