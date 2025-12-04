<?php
/**
 * Rollback Multi-Account Migration
 *
 * This script reverts the multi-account migration and restores
 * the system to single-account structure.
 *
 * WARNING: This will remove all multi-account configurations!
 *
 * Usage: php rollback_migration.php
 */

require_once __DIR__ . '/app/config/config.php';
require_once __DIR__ . '/app/config/database.php';

class MigrationRollback {
    private $db;
    private $logFile;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->logFile = __DIR__ . '/rollback_' . date('Y-m-d_His') . '.log';
    }

    private function log($message, $level = 'INFO') {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[{$timestamp}] [{$level}] {$message}\n";
        file_put_contents($this->logFile, $logMessage, FILE_APPEND);
        echo $logMessage;
    }

    private function backupMultiAccountData() {
        $this->log('Backing up multi-account data before rollback...');

        $backup = "-- Multi-account data backup before rollback\n";
        $backup .= "-- Created at: " . date('Y-m-d H:i:s') . "\n\n";

        // Backup acquirer_accounts
        try {
            $accounts = $this->db->query("SELECT * FROM acquirer_accounts")->fetchAll(PDO::FETCH_ASSOC);
            if (!empty($accounts)) {
                $backup .= "-- Acquirer Accounts\n";
                foreach ($accounts as $account) {
                    $backup .= "-- ID: {$account['id']}, Name: {$account['name']}, ";
                    $backup .= "Client ID: {$account['client_id']}, Merchant ID: {$account['merchant_id']}\n";
                }
                $backup .= "\n";
            }
        } catch (Exception $e) {
            $this->log('Could not backup acquirer_accounts: ' . $e->getMessage(), 'WARN');
        }

        // Backup seller_acquirer_accounts
        try {
            $assignments = $this->db->query("SELECT * FROM seller_acquirer_accounts")->fetchAll(PDO::FETCH_ASSOC);
            if (!empty($assignments)) {
                $backup .= "-- Seller Account Assignments\n";
                foreach ($assignments as $assignment) {
                    $backup .= "-- Seller ID: {$assignment['seller_id']}, Account ID: {$assignment['acquirer_account_id']}, ";
                    $backup .= "Priority: {$assignment['priority']}, Strategy: {$assignment['distribution_strategy']}\n";
                }
                $backup .= "\n";
            }
        } catch (Exception $e) {
            $this->log('Could not backup seller_acquirer_accounts: ' . $e->getMessage(), 'WARN');
        }

        $backupFile = __DIR__ . '/backup_multi_account_' . date('Y-m-d_His') . '.txt';
        file_put_contents($backupFile, $backup);
        $this->log("Multi-account data backed up to: {$backupFile}");

        return $backupFile;
    }

    private function removeMultiAccountTables() {
        $this->log('Removing multi-account tables...');

        try {
            // Drop seller_acquirer_accounts first (foreign key)
            $this->db->exec("DROP TABLE IF EXISTS seller_acquirer_accounts");
            $this->log('Dropped table: seller_acquirer_accounts');

            // Drop acquirer_accounts
            $this->db->exec("DROP TABLE IF EXISTS acquirer_accounts");
            $this->log('Dropped table: acquirer_accounts');

        } catch (PDOException $e) {
            throw new Exception('Failed to drop tables: ' . $e->getMessage());
        }
    }

    private function removeColumnsFromTransactions() {
        $this->log('Removing acquirer_account_id columns from transaction tables...');

        try {
            // Remove from pix_cashin
            $this->db->exec("ALTER TABLE pix_cashin DROP COLUMN IF EXISTS acquirer_account_id");
            $this->log('Removed acquirer_account_id from pix_cashin');

            // Remove from pix_cashout
            $this->db->exec("ALTER TABLE pix_cashout DROP COLUMN IF EXISTS acquirer_account_id");
            $this->log('Removed acquirer_account_id from pix_cashout');

        } catch (PDOException $e) {
            throw new Exception('Failed to remove columns: ' . $e->getMessage());
        }
    }

    private function restoreAcquirerColumns() {
        $this->log('Restoring old columns to acquirers table...');

        try {
            // Add old columns back
            $this->db->exec("ALTER TABLE acquirers ADD COLUMN IF NOT EXISTS client_id VARCHAR(255)");
            $this->db->exec("ALTER TABLE acquirers ADD COLUMN IF NOT EXISTS client_secret VARCHAR(255)");
            $this->db->exec("ALTER TABLE acquirers ADD COLUMN IF NOT EXISTS merchant_id VARCHAR(255)");

            $this->log('Restored columns: client_id, client_secret, merchant_id');

            // Restore credentials from system_settings if available
            $settings = $this->db->query("
                SELECT `key`, `value`
                FROM system_settings
                WHERE `key` IN ('podpay_client_id', 'podpay_client_secret', 'podpay_merchant_id')
            ")->fetchAll(PDO::FETCH_KEY_PAIR);

            if (!empty($settings)) {
                $clientId = $settings['podpay_client_id'] ?? '';
                $clientSecret = $settings['podpay_client_secret'] ?? '';
                $merchantId = $settings['podpay_merchant_id'] ?? '';

                if (!empty($clientId)) {
                    $stmt = $this->db->prepare("
                        UPDATE acquirers
                        SET client_id = ?,
                            client_secret = ?,
                            merchant_id = ?
                        WHERE code = 'podpay'
                    ");
                    $stmt->execute([$clientId, $clientSecret, $merchantId]);
                    $this->log('Restored PodPay credentials to acquirers table');
                }
            }

        } catch (PDOException $e) {
            throw new Exception('Failed to restore columns: ' . $e->getMessage());
        }
    }

    private function removeHelperFunctions() {
        $this->log('Removing helper functions...');

        try {
            $this->db->exec("DROP FUNCTION IF EXISTS get_next_acquirer_account");
            $this->log('Dropped function: get_next_acquirer_account');
        } catch (PDOException $e) {
            $this->log('Could not drop function: ' . $e->getMessage(), 'WARN');
        }
    }

    private function removeNewAcquirerColumns() {
        $this->log('Removing new acquirer columns...');

        try {
            $this->db->exec("ALTER TABLE acquirers DROP COLUMN IF EXISTS description");
            $this->db->exec("ALTER TABLE acquirers DROP COLUMN IF EXISTS base_url");
            $this->db->exec("ALTER TABLE acquirers DROP COLUMN IF EXISTS supports_cashin");
            $this->db->exec("ALTER TABLE acquirers DROP COLUMN IF EXISTS supports_cashout");

            $this->log('Removed new columns from acquirers table');
        } catch (PDOException $e) {
            $this->log('Could not remove new columns: ' . $e->getMessage(), 'WARN');
        }
    }

    public function run() {
        try {
            $this->log('=== Starting Migration Rollback ===');

            echo "\n";
            echo "⚠️  WARNING: This will rollback the multi-account migration!\n";
            echo "⚠️  All multi-account configurations will be removed!\n";
            echo "⚠️  The system will return to single-account structure.\n";
            echo "\nDo you want to continue? (yes/no): ";

            $handle = fopen("php://stdin", "r");
            $line = trim(fgets($handle));
            fclose($handle);

            if (strtolower($line) !== 'yes') {
                $this->log('Rollback cancelled by user');
                echo "Rollback cancelled.\n";
                return false;
            }

            echo "\n";

            // Step 1: Backup multi-account data
            $backupFile = $this->backupMultiAccountData();

            // Step 2: Remove helper functions
            $this->removeHelperFunctions();

            // Step 3: Remove columns from transactions
            $this->removeColumnsFromTransactions();

            // Step 4: Remove multi-account tables
            $this->removeMultiAccountTables();

            // Step 5: Restore old columns
            $this->restoreAcquirerColumns();

            // Step 6: Remove new acquirer columns
            $this->removeNewAcquirerColumns();

            $this->log('=== Rollback Completed Successfully ===', 'SUCCESS');
            echo "\n";
            echo "✓ Rollback completed successfully!\n";
            echo "✓ Multi-account data backed up to: {$backupFile}\n";
            echo "✓ Log saved to: {$this->logFile}\n";
            echo "\n";
            echo "The system has been restored to single-account structure.\n";
            echo "Verify your PodPay credentials in the system settings.\n";
            echo "\n";

            return true;

        } catch (Exception $e) {
            $this->log('Rollback failed: ' . $e->getMessage(), 'ERROR');
            $this->log('Stack trace: ' . $e->getTraceAsString(), 'ERROR');

            echo "\n";
            echo "✗ Rollback failed: " . $e->getMessage() . "\n";
            echo "✗ Check log file for details: {$this->logFile}\n";
            echo "\n";
            echo "You may need to manually restore the database from a backup.\n";
            echo "\n";

            return false;
        }
    }
}

// Run rollback
$rollback = new MigrationRollback();
$success = $rollback->run();

exit($success ? 0 : 1);
