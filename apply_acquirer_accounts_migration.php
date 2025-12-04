<?php
require_once __DIR__ . '/app/config/config.php';
require_once __DIR__ . '/app/config/database.php';

try {
    $db = db();

    echo "Aplicando migration para acquirer_accounts...\n\n";

    // Create acquirer_accounts table
    $sql = "
    CREATE TABLE IF NOT EXISTS acquirer_accounts (
      id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      acquirer_id INT UNSIGNED NOT NULL,
      name VARCHAR(100) NOT NULL,
      account_identifier VARCHAR(255) NOT NULL,
      client_id VARCHAR(255),
      client_secret VARCHAR(255),
      merchant_id VARCHAR(255),
      balance DECIMAL(15,2) DEFAULT 0.00,
      daily_limit DECIMAL(15,2) DEFAULT 100000.00,
      daily_used DECIMAL(15,2) DEFAULT 0.00,
      is_active TINYINT(1) DEFAULT 1,
      is_default TINYINT(1) DEFAULT 0,
      config JSON,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      UNIQUE KEY unique_acquirer_name (acquirer_id, name),
      FOREIGN KEY (acquirer_id) REFERENCES acquirers(id) ON DELETE CASCADE,
      INDEX idx_acquirer_accounts_acquirer (acquirer_id),
      INDEX idx_acquirer_accounts_active (is_active)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";

    $db->exec($sql);
    echo "✓ Tabela acquirer_accounts criada\n";

    // Create seller_acquirer_accounts table
    $sql = "
    CREATE TABLE IF NOT EXISTS seller_acquirer_accounts (
      id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      seller_id INT UNSIGNED NOT NULL,
      acquirer_account_id INT UNSIGNED NOT NULL,
      priority INT DEFAULT 1,
      distribution_strategy VARCHAR(20) DEFAULT 'priority_only',
      percentage_allocation INT DEFAULT 0,
      is_active TINYINT(1) DEFAULT 1,
      total_transactions INT DEFAULT 0,
      total_volume DECIMAL(15,2) DEFAULT 0.00,
      last_used_at TIMESTAMP NULL,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      UNIQUE KEY unique_seller_account (seller_id, acquirer_account_id),
      FOREIGN KEY (seller_id) REFERENCES sellers(id) ON DELETE CASCADE,
      FOREIGN KEY (acquirer_account_id) REFERENCES acquirer_accounts(id) ON DELETE CASCADE,
      INDEX idx_seller_acquirer_accounts_seller (seller_id),
      INDEX idx_seller_acquirer_accounts_account (acquirer_account_id),
      INDEX idx_seller_acquirer_accounts_priority (seller_id, priority)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";

    $db->exec($sql);
    echo "✓ Tabela seller_acquirer_accounts criada\n";

    // Add columns to pix_cashin
    $sql = "
    SELECT COUNT(*) as count
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'pix_cashin'
    AND COLUMN_NAME = 'acquirer_account_id'
    ";
    $result = $db->query($sql)->fetch();

    if ($result['count'] == 0) {
        $sql = "ALTER TABLE pix_cashin
                ADD COLUMN acquirer_account_id INT UNSIGNED NULL,
                ADD INDEX idx_pix_cashin_acquirer_account (acquirer_account_id),
                ADD CONSTRAINT fk_pix_cashin_acquirer_account
                FOREIGN KEY (acquirer_account_id) REFERENCES acquirer_accounts(id)";
        $db->exec($sql);
        echo "✓ Coluna acquirer_account_id adicionada em pix_cashin\n";
    }

    // Add columns to pix_cashout
    $sql = "
    SELECT COUNT(*) as count
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'pix_cashout'
    AND COLUMN_NAME = 'acquirer_account_id'
    ";
    $result = $db->query($sql)->fetch();

    if ($result['count'] == 0) {
        $sql = "ALTER TABLE pix_cashout
                ADD COLUMN acquirer_account_id INT UNSIGNED NULL,
                ADD INDEX idx_pix_cashout_acquirer_account (acquirer_account_id),
                ADD CONSTRAINT fk_pix_cashout_acquirer_account
                FOREIGN KEY (acquirer_account_id) REFERENCES acquirer_accounts(id)";
        $db->exec($sql);
        echo "✓ Coluna acquirer_account_id adicionada em pix_cashout\n";
    }

    // Insert sample data
    echo "\nInserindo contas de exemplo...\n";

    // Check if accounts already exist
    $result = $db->query("SELECT COUNT(*) as count FROM acquirer_accounts")->fetch();

    if ($result['count'] == 0) {
        $sql = "
        INSERT INTO acquirer_accounts (acquirer_id, name, account_identifier, client_id, client_secret, merchant_id, is_active, is_default)
        VALUES
          (1, 'Conta Principal 1', 'ACC-MAIN-001', 'client_id_main_1', 'secret_main_1', 'merchant_001', 1, 1),
          (1, 'Conta Principal 2', 'ACC-MAIN-002', 'client_id_main_2', 'secret_main_2', 'merchant_002', 1, 0),
          (2, 'Conta Backup 1', 'ACC-BACKUP-001', 'client_id_backup_1', 'secret_backup_1', 'merchant_003', 1, 1)
        ";
        $db->exec($sql);
        echo "✓ 3 contas de exemplo inseridas\n";
    } else {
        echo "⚠ Contas já existem, pulando inserção\n";
    }

    echo "\n✅ Migration aplicada com sucesso!\n";

} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
    echo "Detalhes: " . $e->getTraceAsString() . "\n";
}
