/*
  # Velana Configuration Script

  This script configures Velana acquirer accounts in the system.

  ## Important Notes

  1. Replace 'YOUR_VELANA_SECRET_KEY_HERE' with your actual Velana secret key
  2. The secret key should be stored in the `client_id` field
  3. The `client_secret` field can be left NULL or set to a dummy value for Velana
  4. The `merchant_id` field should be NULL for Velana (not used)
  5. Each account can have different limits and priorities

  ## Authentication Format

  Velana uses Basic authentication with the format: "secret_key:x"
  - The system will automatically append ":x" to the secret key
  - Just provide the secret key itself in the client_id field

  ## Example Account Setup

  Below are examples of how to configure Velana accounts:
  - Main account with higher priority (priority 1)
  - Backup account with lower priority (priority 2)

  ## Daily Limits

  - Set appropriate daily limits based on your Velana account balance
  - The system will automatically track daily usage
  - Limits reset daily at midnight
*/

-- Verify Velana acquirer exists
SELECT id, name, code, api_url, status
FROM acquirers
WHERE code = 'velana';

-- Example 1: Configure main Velana account
INSERT INTO acquirer_accounts (
    acquirer_id,
    name,
    client_id,
    client_secret,
    merchant_id,
    base_url,
    priority,
    status,
    daily_limit,
    daily_used,
    daily_reset_at
) VALUES (
    (SELECT id FROM acquirers WHERE code = 'velana'),
    'Velana - Conta Principal',
    'YOUR_VELANA_SECRET_KEY_HERE',  -- Replace with your actual secret key
    NULL,  -- Not used by Velana
    NULL,  -- Not used by Velana
    NULL,  -- Uses default API URL from acquirers table
    1,  -- Highest priority
    'active',
    50000.00,  -- R$ 50,000 daily limit
    0.00,
    CURDATE()
);

-- Example 2: Configure backup Velana account (optional)
INSERT INTO acquirer_accounts (
    acquirer_id,
    name,
    client_id,
    client_secret,
    merchant_id,
    base_url,
    priority,
    status,
    daily_limit,
    daily_used,
    daily_reset_at
) VALUES (
    (SELECT id FROM acquirers WHERE code = 'velana'),
    'Velana - Conta Backup',
    'YOUR_SECOND_VELANA_SECRET_KEY_HERE',  -- Replace with your second secret key
    NULL,
    NULL,
    NULL,
    2,  -- Lower priority (fallback)
    'active',
    30000.00,  -- R$ 30,000 daily limit
    0.00,
    CURDATE()
);

-- View all configured Velana accounts
SELECT
    acc.id,
    acc.name,
    acc.priority,
    acc.status,
    acc.daily_limit,
    acc.daily_used,
    acc.daily_reset_at,
    acq.name as acquirer_name,
    acq.code as acquirer_code
FROM acquirer_accounts acc
INNER JOIN acquirers acq ON acc.acquirer_id = acq.id
WHERE acq.code = 'velana'
ORDER BY acc.priority;

-- Example: Assign Velana account to a specific seller (optional)
-- This makes the account exclusive to that seller
/*
INSERT INTO seller_acquirer_accounts (
    seller_id,
    acquirer_account_id,
    priority,
    status
) VALUES (
    1,  -- Replace with your seller_id
    (SELECT id FROM acquirer_accounts WHERE name = 'Velana - Conta Principal' LIMIT 1),
    1,
    'active'
);
*/

-- View seller-specific account assignments
SELECT
    sa.seller_id,
    s.name as seller_name,
    acc.name as account_name,
    sa.priority,
    sa.status
FROM seller_acquirer_accounts sa
INNER JOIN acquirer_accounts acc ON sa.acquirer_account_id = acc.id
INNER JOIN sellers s ON sa.seller_id = s.id
INNER JOIN acquirers acq ON acc.acquirer_id = acq.id
WHERE acq.code = 'velana'
ORDER BY sa.seller_id, sa.priority;
