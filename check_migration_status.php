<?php
/**
 * Check Migration Status
 *
 * This script checks if the multi-account migration has been applied
 * and provides information about the current system status.
 */

require_once __DIR__ . '/app/config/config.php';
require_once __DIR__ . '/app/config/database.php';

class MigrationStatusChecker {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    private function tableExists($tableName) {
        $result = $this->db->query("SHOW TABLES LIKE '{$tableName}'")->fetchAll();
        return !empty($result);
    }

    private function columnExists($tableName, $columnName) {
        $result = $this->db->query("SHOW COLUMNS FROM {$tableName} LIKE '{$columnName}'")->fetchAll();
        return !empty($result);
    }

    public function checkStatus() {
        echo "=================================\n";
        echo "Multi-Account Migration Status\n";
        echo "=================================\n\n";

        $isMigrated = true;

        // Check 1: acquirer_accounts table
        echo "1. Checking acquirer_accounts table... ";
        if ($this->tableExists('acquirer_accounts')) {
            echo "✓ EXISTS\n";

            $count = $this->db->query("SELECT COUNT(*) as count FROM acquirer_accounts")->fetch(PDO::FETCH_ASSOC);
            echo "   - Accounts configured: {$count['count']}\n";

            if ($count['count'] > 0) {
                $accounts = $this->db->query("
                    SELECT
                        aa.id,
                        aa.name,
                        a.name as acquirer_name,
                        aa.is_active,
                        aa.created_at
                    FROM acquirer_accounts aa
                    JOIN acquirers a ON a.id = aa.acquirer_id
                ")->fetchAll(PDO::FETCH_ASSOC);

                foreach ($accounts as $account) {
                    $status = $account['is_active'] ? 'ACTIVE' : 'INACTIVE';
                    echo "   - [{$account['id']}] {$account['acquirer_name']}: {$account['name']} ({$status})\n";
                }
            }
        } else {
            echo "✗ NOT FOUND\n";
            $isMigrated = false;
        }

        echo "\n";

        // Check 2: seller_acquirer_accounts table
        echo "2. Checking seller_acquirer_accounts table... ";
        if ($this->tableExists('seller_acquirer_accounts')) {
            echo "✓ EXISTS\n";

            $count = $this->db->query("SELECT COUNT(*) as count FROM seller_acquirer_accounts")->fetch(PDO::FETCH_ASSOC);
            echo "   - Seller assignments: {$count['count']}\n";

            if ($count['count'] > 0) {
                $stats = $this->db->query("
                    SELECT
                        COUNT(DISTINCT seller_id) as sellers_with_accounts,
                        COUNT(DISTINCT acquirer_account_id) as accounts_in_use,
                        AVG(priority) as avg_priority
                    FROM seller_acquirer_accounts
                    WHERE is_active = true
                ")->fetch(PDO::FETCH_ASSOC);

                echo "   - Sellers with accounts: {$stats['sellers_with_accounts']}\n";
                echo "   - Accounts in use: {$stats['accounts_in_use']}\n";
            }
        } else {
            echo "✗ NOT FOUND\n";
            $isMigrated = false;
        }

        echo "\n";

        // Check 3: Transaction tables have acquirer_account_id column
        echo "3. Checking pix_cashin.acquirer_account_id... ";
        if ($this->columnExists('pix_cashin', 'acquirer_account_id')) {
            echo "✓ EXISTS\n";

            $stats = $this->db->query("
                SELECT
                    COUNT(*) as total,
                    COUNT(acquirer_account_id) as with_account,
                    COUNT(*) - COUNT(acquirer_account_id) as without_account
                FROM pix_cashin
            ")->fetch(PDO::FETCH_ASSOC);

            echo "   - Total transactions: {$stats['total']}\n";
            echo "   - With account: {$stats['with_account']}\n";
            echo "   - Without account: {$stats['without_account']}\n";
        } else {
            echo "✗ NOT FOUND\n";
            $isMigrated = false;
        }

        echo "\n";

        echo "4. Checking pix_cashout.acquirer_account_id... ";
        if ($this->columnExists('pix_cashout', 'acquirer_account_id')) {
            echo "✓ EXISTS\n";

            $stats = $this->db->query("
                SELECT
                    COUNT(*) as total,
                    COUNT(acquirer_account_id) as with_account,
                    COUNT(*) - COUNT(acquirer_account_id) as without_account
                FROM pix_cashout
            ")->fetch(PDO::FETCH_ASSOC);

            echo "   - Total transactions: {$stats['total']}\n";
            echo "   - With account: {$stats['with_account']}\n";
            echo "   - Without account: {$stats['without_account']}\n";
        } else {
            echo "✗ NOT FOUND\n";
            $isMigrated = false;
        }

        echo "\n";

        // Check 4: Old columns removed from acquirers
        echo "5. Checking old acquirer columns... ";
        $hasOldColumns = false;

        if ($this->columnExists('acquirers', 'client_id')) {
            echo "client_id still exists ";
            $hasOldColumns = true;
        }
        if ($this->columnExists('acquirers', 'client_secret')) {
            echo "client_secret still exists ";
            $hasOldColumns = true;
        }
        if ($this->columnExists('acquirers', 'merchant_id')) {
            echo "merchant_id still exists ";
            $hasOldColumns = true;
        }

        if ($hasOldColumns) {
            echo "\n   ⚠ Old columns still present (migration incomplete)\n";
        } else {
            echo "✓ REMOVED\n";
        }

        echo "\n";

        // Check 5: Old settings
        echo "6. Checking old PodPay settings... ";
        $oldSettings = $this->db->query("
            SELECT `key`, SUBSTRING(`value`, 1, 20) as value_preview
            FROM system_settings
            WHERE `key` IN ('podpay_client_id', 'podpay_client_secret', 'podpay_merchant_id', 'podpay_api_key', 'podpay_api_secret')
        ")->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($oldSettings)) {
            echo "FOUND\n";
            foreach ($oldSettings as $setting) {
                echo "   - {$setting['key']}: {$setting['value_preview']}...\n";
            }
            echo "   ⚠ These can be removed after confirming migration works\n";
        } else {
            echo "✓ CLEANED\n";
        }

        echo "\n";

        // Final status
        echo "=================================\n";
        if ($isMigrated) {
            echo "Status: ✓ MIGRATED\n";
            echo "=================================\n\n";
            echo "The system has been migrated to multi-account support.\n";

            // Check if there are sellers without accounts
            $sellersWithoutAccounts = $this->db->query("
                SELECT COUNT(*) as count
                FROM sellers s
                WHERE s.status = 'active'
                AND NOT EXISTS (
                    SELECT 1 FROM seller_acquirer_accounts saa
                    WHERE saa.seller_id = s.id
                )
            ")->fetch(PDO::FETCH_ASSOC);

            if ($sellersWithoutAccounts['count'] > 0) {
                echo "\n⚠ WARNING: {$sellersWithoutAccounts['count']} active sellers don't have any acquirer account assigned!\n";
                echo "Run this to see them:\n";
                echo "SELECT id, name, email FROM sellers WHERE status = 'active' AND id NOT IN (SELECT seller_id FROM seller_acquirer_accounts);\n";
            }

        } else {
            echo "Status: ✗ NOT MIGRATED\n";
            echo "=================================\n\n";
            echo "The system is using the old single-account structure.\n";
            echo "Run 'php migrate_to_multi_account.php' to migrate.\n";
        }

        echo "\n";
    }
}

try {
    $checker = new MigrationStatusChecker();
    $checker->checkStatus();
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
