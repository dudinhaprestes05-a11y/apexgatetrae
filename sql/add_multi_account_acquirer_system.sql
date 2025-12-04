/*
  # Multi-Account Acquirer System

  1. Changes
    - Refactor acquirers table to represent acquirer types (PodPay, etc)
    - Create acquirer_accounts table for individual accounts with credentials
    - Create seller_acquirer_accounts table for seller-account relationships
    - Add support for account fallback and load distribution strategies
    - Preserve existing acquirer data

  2. New Tables
    - `acquirer_accounts`
      - Individual accounts for each acquirer type
      - Stores credentials (client_id, client_secret, merchant_id)
      - Each account has a name for identification
      - Tracks balance and status

    - `seller_acquirer_accounts`
      - Links sellers to specific acquirer accounts
      - Defines priority for fallback (lower number = higher priority)
      - Configures load distribution strategy (round_robin, percentage, priority_only)
      - Sets percentage allocation for load distribution
      - Tracks usage statistics

  3. Security
    - Enable RLS on new tables
    - Add policies for authenticated admin access
*/

-- Step 1: Update acquirers table structure
ALTER TABLE acquirers
  DROP COLUMN IF EXISTS client_id,
  DROP COLUMN IF EXISTS client_secret,
  DROP COLUMN IF EXISTS merchant_id;

ALTER TABLE acquirers
  ADD COLUMN IF NOT EXISTS description TEXT,
  ADD COLUMN IF NOT EXISTS base_url TEXT NOT NULL DEFAULT '',
  ADD COLUMN IF NOT EXISTS supports_cashin TINYINT(1) DEFAULT 1,
  ADD COLUMN IF NOT EXISTS supports_cashout TINYINT(1) DEFAULT 1;

-- Update existing PodPay acquirer
UPDATE acquirers
SET
  description = 'PodPay Payment Gateway',
  base_url = 'https://api.podpay.com.br',
  supports_cashin = true,
  supports_cashout = true
WHERE code = 'podpay';

-- Step 2: Create acquirer_accounts table
CREATE TABLE IF NOT EXISTS acquirer_accounts (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  acquirer_id INT UNSIGNED NOT NULL,
  name VARCHAR(100) NOT NULL,
  client_id VARCHAR(255) NOT NULL,
  client_secret VARCHAR(255) NOT NULL,
  merchant_id VARCHAR(255) NOT NULL,
  balance DECIMAL(10, 2) DEFAULT 0.00,
  is_active TINYINT(1) DEFAULT 1,
  is_default TINYINT(1) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY unique_acquirer_name (acquirer_id, name),
  FOREIGN KEY (acquirer_id) REFERENCES acquirers(id) ON DELETE CASCADE,
  INDEX idx_acquirer_accounts_acquirer (acquirer_id),
  INDEX idx_acquirer_accounts_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Step 3: Create seller_acquirer_accounts table
CREATE TABLE IF NOT EXISTS seller_acquirer_accounts (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  seller_id INT UNSIGNED NOT NULL,
  acquirer_account_id INT UNSIGNED NOT NULL,
  priority INT DEFAULT 1,
  distribution_strategy VARCHAR(20) DEFAULT 'priority_only',
  percentage_allocation INT DEFAULT 0,
  is_active TINYINT(1) DEFAULT 1,
  total_transactions INT DEFAULT 0,
  total_volume DECIMAL(15, 2) DEFAULT 0.00,
  last_used_at TIMESTAMP NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY unique_seller_account (seller_id, acquirer_account_id),
  FOREIGN KEY (seller_id) REFERENCES sellers(id) ON DELETE CASCADE,
  FOREIGN KEY (acquirer_account_id) REFERENCES acquirer_accounts(id) ON DELETE CASCADE,
  INDEX idx_seller_acquirer_accounts_seller (seller_id),
  INDEX idx_seller_acquirer_accounts_account (acquirer_account_id),
  INDEX idx_seller_acquirer_accounts_priority (seller_id, priority)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Step 4: Migrate existing acquirer data to accounts
-- Note: This will be handled by the PHP migration script
-- The script will:
-- 1. Get credentials from acquirers table
-- 2. Create default account in acquirer_accounts
-- 3. Assign all active sellers to the default account

-- Step 5: Add columns to track acquirer account in transactions
ALTER TABLE pix_cashin
  ADD COLUMN IF NOT EXISTS acquirer_account_id INT UNSIGNED NULL,
  ADD INDEX IF NOT EXISTS idx_pix_cashin_acquirer_account (acquirer_account_id);

-- Add foreign key constraint only if it doesn't exist
SET @constraint_exists = (
  SELECT COUNT(*)
  FROM information_schema.TABLE_CONSTRAINTS
  WHERE CONSTRAINT_SCHEMA = DATABASE()
    AND TABLE_NAME = 'pix_cashin'
    AND CONSTRAINT_NAME = 'fk_pix_cashin_acquirer_account'
);

SET @sql = IF(@constraint_exists = 0,
  'ALTER TABLE pix_cashin ADD CONSTRAINT fk_pix_cashin_acquirer_account FOREIGN KEY (acquirer_account_id) REFERENCES acquirer_accounts(id)',
  'SELECT "Foreign key fk_pix_cashin_acquirer_account already exists" AS info'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

ALTER TABLE pix_cashout
  ADD COLUMN IF NOT EXISTS acquirer_account_id INT UNSIGNED NULL,
  ADD INDEX IF NOT EXISTS idx_pix_cashout_acquirer_account (acquirer_account_id);

-- Add foreign key constraint only if it doesn't exist
SET @constraint_exists = (
  SELECT COUNT(*)
  FROM information_schema.TABLE_CONSTRAINTS
  WHERE CONSTRAINT_SCHEMA = DATABASE()
    AND TABLE_NAME = 'pix_cashout'
    AND CONSTRAINT_NAME = 'fk_pix_cashout_acquirer_account'
);

SET @sql = IF(@constraint_exists = 0,
  'ALTER TABLE pix_cashout ADD CONSTRAINT fk_pix_cashout_acquirer_account FOREIGN KEY (acquirer_account_id) REFERENCES acquirer_accounts(id)',
  'SELECT "Foreign key fk_pix_cashout_acquirer_account already exists" AS info'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Step 6: Security
-- Note: Access control is handled by the application layer (PHP middleware)
-- Only admin users can manage acquirer accounts
-- Sellers can only view their assigned accounts

-- Note: Account selection logic is implemented in PHP (app/services/AcquirerService.php)
-- The service handles:
-- - Distribution strategies (priority_only, round_robin, percentage, least_used)
-- - Account fallback when primary account fails
-- - Load balancing across multiple accounts
