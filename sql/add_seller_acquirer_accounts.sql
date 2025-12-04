/*
  # Add Seller Acquirer Accounts System

  This migration creates the relationship between sellers and processing accounts (acquirer_accounts),
  allowing administrators to configure which accounts each seller can use and set priority orders.

  1. New Tables
    - `seller_acquirer_accounts`
      - `id` (int, primary key, auto-increment)
      - `seller_id` (int, foreign key to sellers)
      - `acquirer_account_id` (int, foreign key to acquirer_accounts)
      - `priority` (int, lower number = higher priority)
      - `is_active` (boolean, whether this account is active for the seller)
      - `created_at` (timestamp)
      - `updated_at` (timestamp)

  2. Security
    - Indexes for performance on foreign keys and priority queries
    - Unique constraint to prevent duplicate seller-account assignments

  3. Important Notes
    - Priority determines the order in which accounts are selected for transactions
    - Lower priority number = higher priority (1 = first choice, 2 = second choice, etc.)
    - If no accounts are configured for a seller, the system falls back to default account selection
    - Only active accounts are considered for transaction processing
*/

-- Create seller_acquirer_accounts table
CREATE TABLE IF NOT EXISTS seller_acquirer_accounts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  seller_id INT NOT NULL,
  acquirer_account_id INT NOT NULL,
  priority INT NOT NULL DEFAULT 1,
  is_active BOOLEAN DEFAULT TRUE,
  distribution_strategy ENUM('priority_only', 'least_used', 'round_robin', 'percentage') DEFAULT 'priority_only',
  percentage_allocation DECIMAL(5,2) DEFAULT 0,
  total_transactions INT DEFAULT 0,
  total_volume DECIMAL(15,2) DEFAULT 0,
  last_used_at TIMESTAMP NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  FOREIGN KEY (seller_id) REFERENCES sellers(id) ON DELETE CASCADE,
  FOREIGN KEY (acquirer_account_id) REFERENCES acquirer_accounts(id) ON DELETE CASCADE,

  UNIQUE KEY unique_seller_account (seller_id, acquirer_account_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create indexes for performance (ignore errors if already exist)
DROP INDEX IF EXISTS idx_seller_acquirer_accounts_seller ON seller_acquirer_accounts;
DROP INDEX IF EXISTS idx_seller_acquirer_accounts_account ON seller_acquirer_accounts;
DROP INDEX IF EXISTS idx_seller_acquirer_accounts_priority ON seller_acquirer_accounts;

CREATE INDEX idx_seller_acquirer_accounts_seller ON seller_acquirer_accounts(seller_id);
CREATE INDEX idx_seller_acquirer_accounts_account ON seller_acquirer_accounts(acquirer_account_id);
CREATE INDEX idx_seller_acquirer_accounts_priority ON seller_acquirer_accounts(seller_id, priority, is_active);
