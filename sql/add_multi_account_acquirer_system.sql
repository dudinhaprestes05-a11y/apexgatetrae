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
  ADD COLUMN IF NOT EXISTS supports_cashin BOOLEAN DEFAULT true,
  ADD COLUMN IF NOT EXISTS supports_cashout BOOLEAN DEFAULT true;

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
  id SERIAL PRIMARY KEY,
  acquirer_id INTEGER NOT NULL REFERENCES acquirers(id) ON DELETE CASCADE,
  name VARCHAR(100) NOT NULL,
  client_id VARCHAR(255) NOT NULL,
  client_secret VARCHAR(255) NOT NULL,
  merchant_id VARCHAR(255) NOT NULL,
  balance DECIMAL(10, 2) DEFAULT 0.00,
  is_active BOOLEAN DEFAULT true,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE(acquirer_id, name)
);

CREATE INDEX IF NOT EXISTS idx_acquirer_accounts_acquirer ON acquirer_accounts(acquirer_id);
CREATE INDEX IF NOT EXISTS idx_acquirer_accounts_active ON acquirer_accounts(is_active);

-- Step 3: Create seller_acquirer_accounts table
CREATE TABLE IF NOT EXISTS seller_acquirer_accounts (
  id SERIAL PRIMARY KEY,
  seller_id INTEGER NOT NULL REFERENCES sellers(id) ON DELETE CASCADE,
  acquirer_account_id INTEGER NOT NULL REFERENCES acquirer_accounts(id) ON DELETE CASCADE,
  priority INTEGER DEFAULT 1,
  distribution_strategy VARCHAR(20) DEFAULT 'priority_only',
  percentage_allocation INTEGER DEFAULT 0,
  is_active BOOLEAN DEFAULT true,
  total_transactions INTEGER DEFAULT 0,
  total_volume DECIMAL(15, 2) DEFAULT 0.00,
  last_used_at TIMESTAMP,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE(seller_id, acquirer_account_id),
  CHECK (priority > 0),
  CHECK (percentage_allocation >= 0 AND percentage_allocation <= 100),
  CHECK (distribution_strategy IN ('priority_only', 'round_robin', 'percentage', 'least_used'))
);

CREATE INDEX IF NOT EXISTS idx_seller_acquirer_accounts_seller ON seller_acquirer_accounts(seller_id);
CREATE INDEX IF NOT EXISTS idx_seller_acquirer_accounts_account ON seller_acquirer_accounts(acquirer_account_id);
CREATE INDEX IF NOT EXISTS idx_seller_acquirer_accounts_priority ON seller_acquirer_accounts(seller_id, priority);

-- Step 4: Migrate existing acquirer data to accounts
DO $$
DECLARE
  v_podpay_acquirer_id INTEGER;
  v_account_id INTEGER;
BEGIN
  -- Get PodPay acquirer ID
  SELECT id INTO v_podpay_acquirer_id FROM acquirers WHERE code = 'podpay';

  IF v_podpay_acquirer_id IS NOT NULL THEN
    -- Check if default account exists
    SELECT id INTO v_account_id
    FROM acquirer_accounts
    WHERE acquirer_id = v_podpay_acquirer_id AND name = 'Default Account';

    -- Create default account if it doesn't exist (only if there are settings)
    IF v_account_id IS NULL THEN
      INSERT INTO acquirer_accounts (
        acquirer_id,
        name,
        client_id,
        client_secret,
        merchant_id,
        is_active
      )
      SELECT
        v_podpay_acquirer_id,
        'Default Account',
        COALESCE((SELECT value FROM system_settings WHERE key = 'podpay_client_id'), ''),
        COALESCE((SELECT value FROM system_settings WHERE key = 'podpay_client_secret'), ''),
        COALESCE((SELECT value FROM system_settings WHERE key = 'podpay_merchant_id'), ''),
        true
      WHERE EXISTS (SELECT 1 FROM system_settings WHERE key = 'podpay_client_id')
      RETURNING id INTO v_account_id;

      -- Assign default account to all active sellers
      IF v_account_id IS NOT NULL THEN
        INSERT INTO seller_acquirer_accounts (
          seller_id,
          acquirer_account_id,
          priority,
          distribution_strategy,
          is_active
        )
        SELECT
          id,
          v_account_id,
          1,
          'priority_only',
          true
        FROM sellers
        WHERE is_active = true
        ON CONFLICT (seller_id, acquirer_account_id) DO NOTHING;
      END IF;
    END IF;
  END IF;
END $$;

-- Step 5: Add columns to track acquirer account in transactions
ALTER TABLE pix_cashin
  ADD COLUMN IF NOT EXISTS acquirer_account_id INTEGER REFERENCES acquirer_accounts(id);

ALTER TABLE pix_cashout
  ADD COLUMN IF NOT EXISTS acquirer_account_id INTEGER REFERENCES acquirer_accounts(id);

CREATE INDEX IF NOT EXISTS idx_pix_cashin_acquirer_account ON pix_cashin(acquirer_account_id);
CREATE INDEX IF NOT EXISTS idx_pix_cashout_acquirer_account ON pix_cashout(acquirer_account_id);

-- Step 6: Enable RLS on new tables
ALTER TABLE acquirer_accounts ENABLE ROW LEVEL SECURITY;
ALTER TABLE seller_acquirer_accounts ENABLE ROW LEVEL SECURITY;

-- Step 7: Create RLS policies for acquirer_accounts
CREATE POLICY "Admins can view all acquirer accounts"
  ON acquirer_accounts FOR SELECT
  TO authenticated
  USING (
    EXISTS (
      SELECT 1 FROM users
      WHERE users.id = auth.uid()
      AND users.role = 'admin'
    )
  );

CREATE POLICY "Admins can insert acquirer accounts"
  ON acquirer_accounts FOR INSERT
  TO authenticated
  WITH CHECK (
    EXISTS (
      SELECT 1 FROM users
      WHERE users.id = auth.uid()
      AND users.role = 'admin'
    )
  );

CREATE POLICY "Admins can update acquirer accounts"
  ON acquirer_accounts FOR UPDATE
  TO authenticated
  USING (
    EXISTS (
      SELECT 1 FROM users
      WHERE users.id = auth.uid()
      AND users.role = 'admin'
    )
  )
  WITH CHECK (
    EXISTS (
      SELECT 1 FROM users
      WHERE users.id = auth.uid()
      AND users.role = 'admin'
    )
  );

CREATE POLICY "Admins can delete acquirer accounts"
  ON acquirer_accounts FOR DELETE
  TO authenticated
  USING (
    EXISTS (
      SELECT 1 FROM users
      WHERE users.id = auth.uid()
      AND users.role = 'admin'
    )
  );

-- Step 8: Create RLS policies for seller_acquirer_accounts
CREATE POLICY "Admins can view all seller acquirer accounts"
  ON seller_acquirer_accounts FOR SELECT
  TO authenticated
  USING (
    EXISTS (
      SELECT 1 FROM users
      WHERE users.id = auth.uid()
      AND users.role = 'admin'
    )
  );

CREATE POLICY "Sellers can view their own acquirer accounts"
  ON seller_acquirer_accounts FOR SELECT
  TO authenticated
  USING (
    seller_id IN (
      SELECT id FROM sellers WHERE user_id = auth.uid()
    )
  );

CREATE POLICY "Admins can insert seller acquirer accounts"
  ON seller_acquirer_accounts FOR INSERT
  TO authenticated
  WITH CHECK (
    EXISTS (
      SELECT 1 FROM users
      WHERE users.id = auth.uid()
      AND users.role = 'admin'
    )
  );

CREATE POLICY "Admins can update seller acquirer accounts"
  ON seller_acquirer_accounts FOR UPDATE
  TO authenticated
  USING (
    EXISTS (
      SELECT 1 FROM users
      WHERE users.id = auth.uid()
      AND users.role = 'admin'
    )
  )
  WITH CHECK (
    EXISTS (
      SELECT 1 FROM users
      WHERE users.id = auth.uid()
      AND users.role = 'admin'
    )
  );

CREATE POLICY "Admins can delete seller acquirer accounts"
  ON seller_acquirer_accounts FOR DELETE
  TO authenticated
  USING (
    EXISTS (
      SELECT 1 FROM users
      WHERE users.id = auth.uid()
      AND users.role = 'admin'
    )
  );

-- Step 9: Create helper function to get next account for seller
CREATE OR REPLACE FUNCTION get_next_acquirer_account(
  p_seller_id INTEGER,
  p_transaction_type VARCHAR(20) DEFAULT 'cashin'
) RETURNS INTEGER AS $$
DECLARE
  v_account_id INTEGER;
  v_strategy VARCHAR(20);
BEGIN
  -- Get account based on strategy
  SELECT
    saa.acquirer_account_id,
    saa.distribution_strategy
  INTO v_account_id, v_strategy
  FROM seller_acquirer_accounts saa
  JOIN acquirer_accounts aa ON aa.id = saa.acquirer_account_id
  JOIN acquirers a ON a.id = aa.acquirer_id
  WHERE saa.seller_id = p_seller_id
    AND saa.is_active = true
    AND aa.is_active = true
    AND a.is_active = true
    AND (
      (p_transaction_type = 'cashin' AND a.supports_cashin = true)
      OR (p_transaction_type = 'cashout' AND a.supports_cashout = true)
    )
  ORDER BY
    CASE
      WHEN saa.distribution_strategy = 'priority_only' THEN saa.priority
      WHEN saa.distribution_strategy = 'least_used' THEN saa.total_transactions
      ELSE saa.priority
    END ASC,
    saa.last_used_at ASC NULLS FIRST
  LIMIT 1;

  RETURN v_account_id;
END;
$$ LANGUAGE plpgsql;
