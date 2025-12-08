/*
  # Add Velana Integration Support

  ## Overview
  This migration adds support for the Velana acquirer with multi-account system.

  ## Changes Made

  ### 1. Multi-Account Tables (if not exist)
    - `acquirer_accounts` - Stores multiple accounts for each acquirer
    - `seller_acquirer_accounts` - Maps sellers to specific acquirer accounts

  ### 2. Transaction Table Updates
    - Add `acquirer_account_id` to `pix_cashin` table
    - Add `acquirer_account_id` to `pix_cashout` table
    - Add `receipt_url` to `pix_cashout` table for storing PDF receipts

  ### 3. Velana Acquirer Registration
    - Register Velana as an available acquirer in the system
    - Set base API URL: https://api.velana.com.br

  ## Security
    - All tables follow existing RLS and security patterns
    - Foreign key constraints ensure data integrity
    - Indexes added for optimal query performance

  ## Important Notes
    1. Velana uses numeric IDs (not UUIDs like PodPay)
    2. Velana amounts are in cents (must multiply by 100 when sending)
    3. Authentication uses Basic auth with format "secret_key:x"
    4. The secret_key should be stored in `client_id` field of acquirer_accounts
    5. Velana provides receipt URLs for completed cashouts
*/

-- Create acquirer_accounts table if it doesn't exist
CREATE TABLE IF NOT EXISTS `acquirer_accounts` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `acquirer_id` INT UNSIGNED NOT NULL,
  `name` VARCHAR(255) NOT NULL COMMENT 'Friendly name for this account',
  `client_id` VARCHAR(255) DEFAULT NULL COMMENT 'Client ID / API Key / Secret Key',
  `client_secret` VARCHAR(255) DEFAULT NULL COMMENT 'Client Secret (optional for Velana)',
  `merchant_id` VARCHAR(255) DEFAULT NULL COMMENT 'Merchant/Recipient/Withdraw Key',
  `base_url` VARCHAR(500) DEFAULT NULL COMMENT 'Override base URL if different from acquirer',
  `priority` INT DEFAULT 1 COMMENT 'Priority order (lower = higher priority)',
  `status` ENUM('active', 'inactive', 'maintenance') DEFAULT 'active',
  `config` JSON DEFAULT NULL COMMENT 'Additional configuration',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`acquirer_id`) REFERENCES `acquirers`(`id`) ON DELETE CASCADE,
  INDEX `idx_acquirer_id` (`acquirer_id`),
  INDEX `idx_status` (`status`),
  INDEX `idx_priority` (`priority`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create seller_acquirer_accounts mapping table if it doesn't exist
CREATE TABLE IF NOT EXISTS `seller_acquirer_accounts` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `seller_id` INT UNSIGNED NOT NULL,
  `acquirer_account_id` INT UNSIGNED NOT NULL,
  `priority` INT DEFAULT 1 COMMENT 'Priority for this seller (lower = higher priority)',
  `status` ENUM('active', 'inactive') DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`seller_id`) REFERENCES `sellers`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`acquirer_account_id`) REFERENCES `acquirer_accounts`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `unique_seller_account` (`seller_id`, `acquirer_account_id`),
  INDEX `idx_seller_id` (`seller_id`),
  INDEX `idx_account_id` (`acquirer_account_id`),
  INDEX `idx_priority` (`priority`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add acquirer_account_id to pix_cashin if it doesn't exist
DELIMITER $$
CREATE PROCEDURE add_acquirer_account_id_to_cashin()
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.columns
    WHERE table_schema = DATABASE()
    AND table_name = 'pix_cashin'
    AND column_name = 'acquirer_account_id'
  ) THEN
    ALTER TABLE `pix_cashin`
    ADD COLUMN `acquirer_account_id` INT UNSIGNED DEFAULT NULL AFTER `acquirer_id`,
    ADD INDEX `idx_acquirer_account_id` (`acquirer_account_id`);
  END IF;
END$$
DELIMITER ;
CALL add_acquirer_account_id_to_cashin();
DROP PROCEDURE add_acquirer_account_id_to_cashin;

-- Add acquirer_account_id to pix_cashout if it doesn't exist
DELIMITER $$
CREATE PROCEDURE add_acquirer_account_id_to_cashout()
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.columns
    WHERE table_schema = DATABASE()
    AND table_name = 'pix_cashout'
    AND column_name = 'acquirer_account_id'
  ) THEN
    ALTER TABLE `pix_cashout`
    ADD COLUMN `acquirer_account_id` INT UNSIGNED DEFAULT NULL AFTER `acquirer_id`,
    ADD INDEX `idx_acquirer_account_id` (`acquirer_account_id`);
  END IF;
END$$
DELIMITER ;
CALL add_acquirer_account_id_to_cashout();
DROP PROCEDURE add_acquirer_account_id_to_cashout;

-- Add receipt_url to pix_cashout if it doesn't exist (for Velana PDF receipts)
DELIMITER $$
CREATE PROCEDURE add_receipt_url_to_cashout()
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.columns
    WHERE table_schema = DATABASE()
    AND table_name = 'pix_cashout'
    AND column_name = 'receipt_url'
  ) THEN
    ALTER TABLE `pix_cashout`
    ADD COLUMN `receipt_url` VARCHAR(500) DEFAULT NULL COMMENT 'Receipt URL from acquirer' AFTER `end_to_end_id`;
  END IF;
END$$
DELIMITER ;
CALL add_receipt_url_to_cashout();
DROP PROCEDURE add_receipt_url_to_cashout;

-- Register Velana acquirer if it doesn't exist
INSERT INTO `acquirers` (`name`, `code`, `api_url`, `priority_order`, `status`)
SELECT 'Velana', 'velana', 'https://api.velana.com.br', 3, 'active'
WHERE NOT EXISTS (
  SELECT 1 FROM `acquirers` WHERE `code` = 'velana'
);
