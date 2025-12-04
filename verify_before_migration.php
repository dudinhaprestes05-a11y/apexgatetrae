<?php
/**
 * Pre-Migration Verification Script
 *
 * This script checks if the system is ready for migration to multi-account structure.
 * Run this before executing the migration script.
 */

require_once __DIR__ . '/app/config/database.php';

class PreMigrationChecker {
    private $db;
    private $checks = [];

    public function __construct() {
        $this->db = Database::getInstance();
    }

    private function addCheck($name, $status, $message, $level = 'info') {
        $this->checks[] = [
            'name' => $name,
            'status' => $status,
            'message' => $message,
            'level' => $level
        ];
    }

    private function checkDatabaseConnection() {
        try {
            $this->db->query("SELECT 1")->fetch();
            $this->addCheck(
                'Database Connection',
                true,
                'Connected successfully',
                'success'
            );
            return true;
        } catch (Exception $e) {
            $this->addCheck(
                'Database Connection',
                false,
                'Failed: ' . $e->getMessage(),
                'error'
            );
            return false;
        }
    }

    private function checkPodPayCredentials() {
        $settings = $this->db->query("
            SELECT key, value
            FROM system_settings
            WHERE key IN ('podpay_client_id', 'podpay_client_secret', 'podpay_merchant_id', 'podpay_api_key', 'podpay_api_secret')
        ")->fetchAll(PDO::FETCH_KEY_PAIR);

        $clientId = $settings['podpay_client_id'] ?? $settings['podpay_api_key'] ?? '';
        $clientSecret = $settings['podpay_client_secret'] ?? $settings['podpay_api_secret'] ?? '';
        $merchantId = $settings['podpay_merchant_id'] ?? '';

        if (empty($clientId) || empty($clientSecret)) {
            $this->addCheck(
                'PodPay Credentials',
                false,
                'No credentials found in system_settings. Configure PodPay first.',
                'error'
            );
            return false;
        }

        $this->addCheck(
            'PodPay Credentials',
            true,
            'Found: client_id=' . substr($clientId, 0, 10) . '..., merchant_id=' . $merchantId,
            'success'
        );
        return true;
    }

    private function checkPodPayAcquirer() {
        $acquirer = $this->db->query("
            SELECT * FROM acquirers WHERE code = 'podpay' LIMIT 1
        ")->fetch(PDO::FETCH_ASSOC);

        if (!$acquirer) {
            $this->addCheck(
                'PodPay Acquirer',
                false,
                'PodPay acquirer not found in database. Run sql/schema.sql first.',
                'error'
            );
            return false;
        }

        $this->addCheck(
            'PodPay Acquirer',
            true,
            'Found: ' . $acquirer['name'] . ' (ID: ' . $acquirer['id'] . ')',
            'success'
        );
        return true;
    }

    private function checkActiveSellers() {
        $count = $this->db->query("
            SELECT COUNT(*) as count FROM sellers WHERE status = 'active'
        ")->fetch(PDO::FETCH_ASSOC);

        if ($count['count'] == 0) {
            $this->addCheck(
                'Active Sellers',
                true,
                'No active sellers found (migration will still work)',
                'warning'
            );
        } else {
            $this->addCheck(
                'Active Sellers',
                true,
                'Found ' . $count['count'] . ' active sellers',
                'success'
            );
        }
        return true;
    }

    private function checkExistingTransactions() {
        $cashin = $this->db->query("SELECT COUNT(*) as count FROM pix_cashin")->fetch(PDO::FETCH_ASSOC);
        $cashout = $this->db->query("SELECT COUNT(*) as count FROM pix_cashout")->fetch(PDO::FETCH_ASSOC);

        $total = $cashin['count'] + $cashout['count'];

        if ($total > 0) {
            $this->addCheck(
                'Existing Transactions',
                true,
                "Found {$total} transactions ({$cashin['count']} cashin, {$cashout['count']} cashout)",
                'info'
            );
        } else {
            $this->addCheck(
                'Existing Transactions',
                true,
                'No transactions found (new system)',
                'info'
            );
        }
        return true;
    }

    private function checkAlreadyMigrated() {
        try {
            $tables = $this->db->query("SHOW TABLES LIKE 'acquirer_accounts'")->fetchAll();

            if (!empty($tables)) {
                $count = $this->db->query("SELECT COUNT(*) as count FROM acquirer_accounts")->fetch(PDO::FETCH_ASSOC);

                $this->addCheck(
                    'Migration Status',
                    false,
                    'System already migrated (' . $count['count'] . ' accounts found)',
                    'warning'
                );
                return false;
            }

            $this->addCheck(
                'Migration Status',
                true,
                'System not migrated yet (ready for migration)',
                'success'
            );
            return true;

        } catch (Exception $e) {
            $this->addCheck(
                'Migration Status',
                true,
                'System not migrated yet',
                'success'
            );
            return true;
        }
    }

    private function checkBackupSpace() {
        $backupDir = __DIR__;
        $freeSpace = disk_free_space($backupDir);
        $freeSpaceMB = round($freeSpace / 1024 / 1024, 2);

        if ($freeSpaceMB < 100) {
            $this->addCheck(
                'Disk Space',
                false,
                "Only {$freeSpaceMB} MB free. Need at least 100 MB for backup.",
                'error'
            );
            return false;
        }

        $this->addCheck(
            'Disk Space',
            true,
            "{$freeSpaceMB} MB free space available",
            'success'
        );
        return true;
    }

    private function checkPHPVersion() {
        $version = phpversion();
        $required = '7.4.0';

        if (version_compare($version, $required, '<')) {
            $this->addCheck(
                'PHP Version',
                false,
                "PHP {$version} found, need {$required} or higher",
                'error'
            );
            return false;
        }

        $this->addCheck(
            'PHP Version',
            true,
            "PHP {$version}",
            'success'
        );
        return true;
    }

    private function printResults() {
        echo "\n";
        echo "=====================================\n";
        echo "  Pre-Migration Verification Report\n";
        echo "=====================================\n\n";

        $errors = 0;
        $warnings = 0;

        foreach ($this->checks as $check) {
            $symbol = match($check['level']) {
                'success' => '✓',
                'warning' => '⚠',
                'error' => '✗',
                default => 'ℹ'
            };

            $color = match($check['level']) {
                'success' => "\033[0;32m",
                'warning' => "\033[0;33m",
                'error' => "\033[0;31m",
                default => "\033[0;37m"
            };

            $reset = "\033[0m";

            echo "{$color}{$symbol} {$check['name']}{$reset}\n";
            echo "  {$check['message']}\n\n";

            if ($check['level'] === 'error') {
                $errors++;
            } elseif ($check['level'] === 'warning') {
                $warnings++;
            }
        }

        echo "=====================================\n";

        if ($errors > 0) {
            echo "\033[0;31m✗ {$errors} error(s) found\033[0m\n";
            echo "Please fix the errors before running migration.\n\n";
            return false;
        }

        if ($warnings > 0) {
            echo "\033[0;33m⚠ {$warnings} warning(s) found\033[0m\n";
            echo "Review warnings before proceeding.\n\n";
        }

        echo "\033[0;32m✓ System is ready for migration!\033[0m\n\n";
        echo "Next steps:\n";
        echo "1. Make a manual backup: mysqldump -u user -p database > backup.sql\n";
        echo "2. Run migration: php migrate_to_multi_account.php\n";
        echo "3. Verify migration: php check_migration_status.php\n\n";

        return true;
    }

    public function run() {
        echo "Running pre-migration checks...\n";

        $this->checkPHPVersion();
        $this->checkDatabaseConnection();
        $this->checkBackupSpace();
        $this->checkAlreadyMigrated();
        $this->checkPodPayAcquirer();
        $this->checkPodPayCredentials();
        $this->checkActiveSellers();
        $this->checkExistingTransactions();

        return $this->printResults();
    }
}

try {
    $checker = new PreMigrationChecker();
    $ready = $checker->run();
    exit($ready ? 0 : 1);
} catch (Exception $e) {
    echo "\n\033[0;31mError: " . $e->getMessage() . "\033[0m\n\n";
    exit(1);
}
