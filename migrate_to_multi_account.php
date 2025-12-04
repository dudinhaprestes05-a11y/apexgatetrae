<?php
/**
 * Migration Script: Single Account to Multi-Account Acquirer System
 *
 * This script migrates the system from single acquirer account to multi-account support.
 * It preserves existing PodPay configuration and assigns it to all active sellers.
 *
 * Usage: php migrate_to_multi_account.php
 */

require_once __DIR__ . '/app/config/database.php';

class MultiAccountMigration {
    private $db;
    private $backupFile;
    private $logFile;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->backupFile = __DIR__ . '/backup_migration_' . date('Y-m-d_His') . '.sql';
        $this->logFile = __DIR__ . '/migration_' . date('Y-m-d_His') . '.log';
    }

    private function log($message, $level = 'INFO') {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[{$timestamp}] [{$level}] {$message}\n";
        file_put_contents($this->logFile, $logMessage, FILE_APPEND);
        echo $logMessage;
    }

    private function backupExistingData() {
        $this->log('Creating backup of existing data...');

        $tables = [
            'acquirers',
            'pix_cashin',
            'pix_cashout',
            'sellers',
            'system_settings'
        ];

        $backup = "-- Backup created at " . date('Y-m-d H:i:s') . "\n\n";

        foreach ($tables as $table) {
            $this->log("Backing up table: {$table}");

            // Get table structure
            $createTable = $this->db->query("SHOW CREATE TABLE {$table}")->fetch(PDO::FETCH_ASSOC);
            $backup .= "-- Table: {$table}\n";
            $backup .= $createTable['Create Table'] . ";\n\n";

            // Get table data
            $rows = $this->db->query("SELECT * FROM {$table}")->fetchAll(PDO::FETCH_ASSOC);

            if (!empty($rows)) {
                foreach ($rows as $row) {
                    $columns = array_keys($row);
                    $values = array_map(function($val) {
                        return $val === null ? 'NULL' : $this->db->quote($val);
                    }, array_values($row));

                    $backup .= "INSERT INTO {$table} (`" . implode('`, `', $columns) . "`) VALUES (" . implode(', ', $values) . ");\n";
                }
                $backup .= "\n";
            }
        }

        file_put_contents($this->backupFile, $backup);
        $this->log("Backup created successfully: {$this->backupFile}");
    }

    private function getCurrentPodPayConfig() {
        $this->log('Retrieving current PodPay configuration...');

        $config = [];

        // Check system_settings table
        $settings = $this->db->query("
            SELECT key, value
            FROM system_settings
            WHERE key IN ('podpay_client_id', 'podpay_client_secret', 'podpay_merchant_id', 'podpay_api_key', 'podpay_api_secret')
        ")->fetchAll(PDO::FETCH_KEY_PAIR);

        $config['client_id'] = $settings['podpay_client_id'] ?? $settings['podpay_api_key'] ?? '';
        $config['client_secret'] = $settings['podpay_client_secret'] ?? $settings['podpay_api_secret'] ?? '';
        $config['merchant_id'] = $settings['podpay_merchant_id'] ?? '';

        // Check if acquirers table still has credentials (before migration)
        $acquirer = $this->db->query("
            SELECT client_id, client_secret, merchant_id, api_key, api_secret
            FROM acquirers
            WHERE code = 'podpay'
            LIMIT 1
        ")->fetch(PDO::FETCH_ASSOC);

        if ($acquirer) {
            // Use acquirer table data if available (fallback)
            $config['client_id'] = $config['client_id'] ?: ($acquirer['client_id'] ?? $acquirer['api_key'] ?? '');
            $config['client_secret'] = $config['client_secret'] ?: ($acquirer['client_secret'] ?? $acquirer['api_secret'] ?? '');
            $config['merchant_id'] = $config['merchant_id'] ?: ($acquirer['merchant_id'] ?? '');
        }

        $this->log("Retrieved configuration: " . json_encode([
            'client_id' => substr($config['client_id'], 0, 10) . '...',
            'merchant_id' => $config['merchant_id']
        ]));

        if (empty($config['client_id']) || empty($config['client_secret'])) {
            $this->log('WARNING: No PodPay credentials found in system', 'WARN');
        }

        return $config;
    }

    private function applyMultiAccountSQL() {
        $this->log('Applying multi-account SQL schema...');

        $sqlFile = __DIR__ . '/sql/add_multi_account_acquirer_system.sql';

        if (!file_exists($sqlFile)) {
            throw new Exception("SQL file not found: {$sqlFile}");
        }

        $sql = file_get_contents($sqlFile);

        // Split by semicolons but handle PL/pgSQL blocks
        $statements = [];
        $current = '';
        $inBlock = false;

        $lines = explode("\n", $sql);
        foreach ($lines as $line) {
            $trimmed = trim($line);

            // Skip comments
            if (empty($trimmed) || substr($trimmed, 0, 2) === '--') {
                continue;
            }

            // Check for DO $$ blocks
            if (strpos($trimmed, 'DO $$') !== false || strpos($trimmed, 'DO$$') !== false) {
                $inBlock = true;
            }

            $current .= $line . "\n";

            // End of block
            if ($inBlock && (strpos($trimmed, 'END $$;') !== false || strpos($trimmed, 'END$$;') !== false)) {
                $inBlock = false;
                $statements[] = trim($current);
                $current = '';
            } elseif (!$inBlock && substr($trimmed, -1) === ';') {
                $statements[] = trim($current);
                $current = '';
            }
        }

        if (!empty($current)) {
            $statements[] = trim($current);
        }

        $this->log("Executing " . count($statements) . " SQL statements...");

        foreach ($statements as $index => $statement) {
            if (empty(trim($statement))) {
                continue;
            }

            try {
                $this->db->exec($statement);
                $this->log("Statement " . ($index + 1) . " executed successfully");
            } catch (PDOException $e) {
                // Some errors are acceptable (like column already exists)
                if (strpos($e->getMessage(), 'Duplicate column') !== false ||
                    strpos($e->getMessage(), 'already exists') !== false) {
                    $this->log("Statement " . ($index + 1) . " skipped (already exists): " . substr($statement, 0, 100), 'WARN');
                } else {
                    throw new Exception("Failed to execute statement " . ($index + 1) . ": " . $e->getMessage() . "\nSQL: " . substr($statement, 0, 200));
                }
            }
        }

        $this->log('Multi-account SQL schema applied successfully');
    }

    private function migrateExistingAccount() {
        $this->log('Migrating existing PodPay account...');

        // Get current config
        $config = $this->getCurrentPodPayConfig();

        // Get PodPay acquirer ID
        $acquirer = $this->db->query("
            SELECT id FROM acquirers WHERE code = 'podpay' LIMIT 1
        ")->fetch(PDO::FETCH_ASSOC);

        if (!$acquirer) {
            throw new Exception('PodPay acquirer not found in database');
        }

        $acquirerId = $acquirer['id'];
        $this->log("PodPay acquirer ID: {$acquirerId}");

        // Check if account already exists
        $existingAccount = $this->db->query("
            SELECT id FROM acquirer_accounts
            WHERE acquirer_id = {$acquirerId}
            AND name = 'Default Account'
            LIMIT 1
        ")->fetch(PDO::FETCH_ASSOC);

        if ($existingAccount) {
            $accountId = $existingAccount['id'];
            $this->log("Account already exists with ID: {$accountId}");
        } else {
            // Create the default account if credentials exist
            if (!empty($config['client_id']) && !empty($config['client_secret'])) {
                $stmt = $this->db->prepare("
                    INSERT INTO acquirer_accounts (
                        acquirer_id,
                        name,
                        client_id,
                        client_secret,
                        merchant_id,
                        is_active,
                        created_at,
                        updated_at
                    ) VALUES (?, ?, ?, ?, ?, true, NOW(), NOW())
                ");

                $stmt->execute([
                    $acquirerId,
                    'Default Account',
                    $config['client_id'],
                    $config['client_secret'],
                    $config['merchant_id']
                ]);

                $accountId = $this->db->lastInsertId();
                $this->log("Created new account with ID: {$accountId}");
            } else {
                $this->log('No credentials found, skipping account creation', 'WARN');
                return null;
            }
        }

        return $accountId;
    }

    private function assignAccountToSellers($accountId) {
        if (!$accountId) {
            $this->log('No account to assign to sellers', 'WARN');
            return;
        }

        $this->log('Assigning account to all active sellers...');

        // Get all active sellers
        $sellers = $this->db->query("
            SELECT id, name FROM sellers WHERE status = 'active'
        ")->fetchAll(PDO::FETCH_ASSOC);

        $this->log("Found " . count($sellers) . " active sellers");

        $assigned = 0;
        foreach ($sellers as $seller) {
            // Check if already assigned
            $existing = $this->db->query("
                SELECT id FROM seller_acquirer_accounts
                WHERE seller_id = {$seller['id']}
                AND acquirer_account_id = {$accountId}
            ")->fetch(PDO::FETCH_ASSOC);

            if (!$existing) {
                $stmt = $this->db->prepare("
                    INSERT INTO seller_acquirer_accounts (
                        seller_id,
                        acquirer_account_id,
                        priority,
                        distribution_strategy,
                        is_active,
                        created_at,
                        updated_at
                    ) VALUES (?, ?, 1, 'priority_only', true, NOW(), NOW())
                ");

                $stmt->execute([$seller['id'], $accountId]);
                $assigned++;
                $this->log("Assigned account to seller: {$seller['name']} (ID: {$seller['id']})");
            } else {
                $this->log("Seller already has account assigned: {$seller['name']} (ID: {$seller['id']})", 'INFO');
            }
        }

        $this->log("Account assigned to {$assigned} sellers");
    }

    private function updateExistingTransactions($accountId) {
        if (!$accountId) {
            $this->log('No account to update transactions', 'WARN');
            return;
        }

        $this->log('Updating existing transactions...');

        // Update cashin transactions
        $cashinCount = $this->db->exec("
            UPDATE pix_cashin
            SET acquirer_account_id = {$accountId}
            WHERE acquirer_account_id IS NULL
        ");

        $this->log("Updated {$cashinCount} cashin transactions");

        // Update cashout transactions
        $cashoutCount = $this->db->exec("
            UPDATE pix_cashout
            SET acquirer_account_id = {$accountId}
            WHERE acquirer_account_id IS NULL
        ");

        $this->log("Updated {$cashoutCount} cashout transactions");
    }

    private function verifyMigration() {
        $this->log('Verifying migration...');

        $checks = [];

        // Check acquirer_accounts table exists
        $tables = $this->db->query("SHOW TABLES LIKE 'acquirer_accounts'")->fetchAll();
        $checks['acquirer_accounts_table'] = !empty($tables);

        // Check seller_acquirer_accounts table exists
        $tables = $this->db->query("SHOW TABLES LIKE 'seller_acquirer_accounts'")->fetchAll();
        $checks['seller_acquirer_accounts_table'] = !empty($tables);

        // Check if default account exists
        $account = $this->db->query("SELECT COUNT(*) as count FROM acquirer_accounts")->fetch(PDO::FETCH_ASSOC);
        $checks['accounts_created'] = $account['count'] > 0;

        // Check if sellers have accounts assigned
        $assignments = $this->db->query("SELECT COUNT(*) as count FROM seller_acquirer_accounts")->fetch(PDO::FETCH_ASSOC);
        $checks['seller_assignments'] = $assignments['count'] > 0;

        // Check if columns exist in transaction tables
        $columns = $this->db->query("SHOW COLUMNS FROM pix_cashin LIKE 'acquirer_account_id'")->fetchAll();
        $checks['cashin_column'] = !empty($columns);

        $columns = $this->db->query("SHOW COLUMNS FROM pix_cashout LIKE 'acquirer_account_id'")->fetchAll();
        $checks['cashout_column'] = !empty($columns);

        $allPassed = true;
        foreach ($checks as $check => $passed) {
            $status = $passed ? 'PASS' : 'FAIL';
            $this->log("Check [{$check}]: {$status}", $passed ? 'INFO' : 'ERROR');
            if (!$passed) {
                $allPassed = false;
            }
        }

        return $allPassed;
    }

    public function run() {
        try {
            $this->log('=== Starting Multi-Account Migration ===');
            $this->log('Backup file: ' . $this->backupFile);
            $this->log('Log file: ' . $this->logFile);

            echo "\n";
            echo "WARNING: This migration will modify your database structure.\n";
            echo "A backup will be created at: {$this->backupFile}\n";
            echo "\nDo you want to continue? (yes/no): ";

            $handle = fopen("php://stdin", "r");
            $line = trim(fgets($handle));
            fclose($handle);

            if (strtolower($line) !== 'yes') {
                $this->log('Migration cancelled by user');
                echo "Migration cancelled.\n";
                return false;
            }

            echo "\n";

            // Step 1: Backup
            $this->backupExistingData();

            // Step 2: Get current config
            $config = $this->getCurrentPodPayConfig();

            // Step 3: Apply SQL
            $this->applyMultiAccountSQL();

            // Step 4: Migrate account
            $accountId = $this->migrateExistingAccount();

            // Step 5: Assign to sellers
            $this->assignAccountToSellers($accountId);

            // Step 6: Update transactions
            $this->updateExistingTransactions($accountId);

            // Step 7: Verify
            $verified = $this->verifyMigration();

            if ($verified) {
                $this->log('=== Migration Completed Successfully ===', 'SUCCESS');
                echo "\n";
                echo "✓ Migration completed successfully!\n";
                echo "✓ Backup saved to: {$this->backupFile}\n";
                echo "✓ Log saved to: {$this->logFile}\n";
                echo "\n";
                echo "Next steps:\n";
                echo "1. Test the system thoroughly\n";
                echo "2. Add additional acquirer accounts in the admin panel\n";
                echo "3. Configure seller account assignments\n";
                echo "\n";
                return true;
            } else {
                $this->log('Migration verification failed', 'ERROR');
                echo "\n";
                echo "✗ Migration verification failed. Check the log file for details.\n";
                echo "✗ You may need to restore from backup: {$this->backupFile}\n";
                echo "\n";
                return false;
            }

        } catch (Exception $e) {
            $this->log('Migration failed: ' . $e->getMessage(), 'ERROR');
            $this->log('Stack trace: ' . $e->getTraceAsString(), 'ERROR');

            echo "\n";
            echo "✗ Migration failed: " . $e->getMessage() . "\n";
            echo "✗ Backup is available at: {$this->backupFile}\n";
            echo "✗ Check log file for details: {$this->logFile}\n";
            echo "\n";

            return false;
        }
    }
}

// Run migration
$migration = new MultiAccountMigration();
$success = $migration->run();

exit($success ? 0 : 1);
