/*
  # Add IP Whitelist System to Sellers

  This migration adds IP whitelist functionality to the sellers table.

  ## Changes

  1. New Columns in `sellers` table:
    - `ip_whitelist` (TEXT) - JSON array storing whitelisted IP addresses and CIDR ranges
    - `ip_whitelist_enabled` (BOOLEAN) - Flag to enable/disable IP whitelist verification

  2. Features:
    - Stores IP addresses and CIDR ranges in JSON format
    - Enabled by default for enhanced security
    - When enabled with empty whitelist, ALL IPs are BLOCKED
    - Sellers must add IPs or disable whitelist to allow access
    - When IPs are added, only whitelisted IPs can access the API
    - Can be disabled to always allow all IPs

  3. Security:
    - Adds additional layer of security for API access
    - Prevents unauthorized access even with valid credentials
    - Protects against credential theft and unauthorized usage
*/

-- Add IP whitelist columns to sellers table
ALTER TABLE sellers
ADD COLUMN IF NOT EXISTS ip_whitelist TEXT DEFAULT '[]',
ADD COLUMN IF NOT EXISTS ip_whitelist_enabled TINYINT(1) DEFAULT 1;

-- Add index for faster queries on whitelist-enabled sellers
CREATE INDEX IF NOT EXISTS idx_sellers_ip_whitelist_enabled
ON sellers(ip_whitelist_enabled);
