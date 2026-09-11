-- Migration: Create reporters table for reporter registration with admin verification
-- Run this SQL on the nj_cream database

CREATE TABLE IF NOT EXISTS `reporters` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) NOT NULL,
    `bio` TEXT DEFAULT NULL,
    `experience` VARCHAR(255) DEFAULT NULL,
    `portfolio_url` VARCHAR(500) DEFAULT NULL,
    `areas_of_coverage` VARCHAR(500) DEFAULT NULL,
    `status` ENUM('pending', 'verified', 'rejected') DEFAULT 'pending',
    `admin_notes` TEXT DEFAULT NULL,
    `date_created` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `date_verified` DATETIME DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_user` (`user_id`),
    FOREIGN KEY (`user_id`) REFERENCES `user`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add role column to user table if not exists
ALTER TABLE `user` ADD COLUMN IF NOT EXISTS `role` VARCHAR(20) DEFAULT 'user';
