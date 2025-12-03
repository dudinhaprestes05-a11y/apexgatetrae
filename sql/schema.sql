/*
  # Gateway de Pagamentos PIX - Database Schema

  ## Tabelas Principais
  1. sellers - Cadastro de vendedores/merchants
  2. users - Usuários do sistema (admin e sellers)
  3. acquirers - Adquirentes/PSPs integrados
  4. pix_cashin - Transações de recebimento PIX
  5. pix_cashout - Transações de saque/transferência PIX
  6. splits - Configuração de split de pagamentos
  7. webhooks_queue - Fila de webhooks para processamento
  8. callbacks_acquirers - Log de callbacks recebidos das adquirentes
  9. logs - Logs de auditoria do sistema
  10. rate_limits - Controle de rate limiting
*/

CREATE TABLE IF NOT EXISTS `sellers` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) NOT NULL UNIQUE,
  `document` VARCHAR(14) NOT NULL UNIQUE COMMENT 'CPF ou CNPJ',
  `phone` VARCHAR(20) DEFAULT NULL,
  `person_type` ENUM('individual', 'business') NOT NULL COMMENT 'Pessoa física ou jurídica',
  `company_name` VARCHAR(255) DEFAULT NULL COMMENT 'Razão social se PJ',
  `trading_name` VARCHAR(255) DEFAULT NULL COMMENT 'Nome fantasia',
  `monthly_revenue` DECIMAL(15,2) DEFAULT NULL COMMENT 'Faturamento mensal',
  `average_ticket` DECIMAL(15,2) DEFAULT NULL COMMENT 'Ticket médio',
  `api_key` VARCHAR(64) DEFAULT NULL UNIQUE,
  `api_secret` VARCHAR(128) DEFAULT NULL COMMENT 'Para HMAC',
  `webhook_url` VARCHAR(500) DEFAULT NULL,
  `webhook_secret` VARCHAR(128) DEFAULT NULL,
  `status` ENUM('pending', 'active', 'inactive', 'blocked', 'rejected') DEFAULT 'pending',
  `document_status` ENUM('pending', 'under_review', 'approved', 'rejected') DEFAULT 'pending',
  `approval_notes` TEXT DEFAULT NULL,
  `approved_by` INT UNSIGNED DEFAULT NULL,
  `approved_at` TIMESTAMP NULL,
  `balance` DECIMAL(15,2) DEFAULT 0.00,
  `daily_limit` DECIMAL(15,2) DEFAULT 50000.00,
  `daily_used` DECIMAL(15,2) DEFAULT 0.00,
  `daily_reset_at` DATE DEFAULT NULL,
  `fee_percentage_cashin` DECIMAL(5,4) DEFAULT 0.0099 COMMENT 'Taxa cash-in 0.99%',
  `fee_fixed_cashin` DECIMAL(10,2) DEFAULT 0.00,
  `fee_percentage_cashout` DECIMAL(5,4) DEFAULT 0.0199 COMMENT 'Taxa cash-out 1.99%',
  `fee_fixed_cashout` DECIMAL(10,2) DEFAULT 0.00,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_api_key` (`api_key`),
  INDEX `idx_status` (`status`),
  INDEX `idx_document_status` (`document_status`),
  INDEX `idx_daily_reset` (`daily_reset_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `users` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `seller_id` INT UNSIGNED DEFAULT NULL,
  `name` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('admin', 'seller') NOT NULL DEFAULT 'seller',
  `status` ENUM('active', 'inactive') DEFAULT 'active',
  `last_login` TIMESTAMP NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`seller_id`) REFERENCES `sellers`(`id`) ON DELETE CASCADE,
  INDEX `idx_email` (`email`),
  INDEX `idx_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `acquirers` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `code` VARCHAR(50) NOT NULL UNIQUE COMMENT 'Código único da adquirente',
  `api_url` VARCHAR(500) NOT NULL,
  `api_key` VARCHAR(255) DEFAULT NULL,
  `api_secret` VARCHAR(255) DEFAULT NULL,
  `priority_order` INT DEFAULT 1 COMMENT 'Ordem de prioridade (menor = maior prioridade)',
  `status` ENUM('active', 'inactive', 'maintenance') DEFAULT 'active',
  `success_rate` DECIMAL(5,2) DEFAULT 100.00,
  `avg_response_time` INT DEFAULT 0 COMMENT 'Tempo médio de resposta em ms',
  `daily_limit` DECIMAL(15,2) DEFAULT 100000.00,
  `daily_used` DECIMAL(15,2) DEFAULT 0.00,
  `daily_reset_at` DATE NOT NULL,
  `config` JSON DEFAULT NULL COMMENT 'Configurações específicas da adquirente',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_code` (`code`),
  INDEX `idx_status` (`status`),
  INDEX `idx_priority` (`priority_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pix_cashin` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `seller_id` INT UNSIGNED NOT NULL,
  `acquirer_id` INT UNSIGNED NOT NULL,
  `transaction_id` VARCHAR(100) NOT NULL UNIQUE COMMENT 'ID único interno',
  `external_id` VARCHAR(255) DEFAULT NULL COMMENT 'ID externo do seller',
  `acquirer_transaction_id` VARCHAR(255) DEFAULT NULL COMMENT 'ID na adquirente (secureId PodPay)',
  `end_to_end_id` VARCHAR(255) DEFAULT NULL COMMENT 'E2E ID do PIX',
  `amount` DECIMAL(15,2) NOT NULL,
  `fee_amount` DECIMAL(15,2) DEFAULT 0.00,
  `net_amount` DECIMAL(15,2) NOT NULL COMMENT 'Valor líquido após taxas',
  `pix_key` VARCHAR(255) DEFAULT NULL,
  `pix_type` ENUM('qrcode', 'static', 'dynamic') DEFAULT 'dynamic',
  `qrcode` TEXT DEFAULT NULL COMMENT 'Payload copia e cola',
  `qrcode_base64` LONGTEXT DEFAULT NULL,
  `customer_name` VARCHAR(255) DEFAULT NULL,
  `customer_document` VARCHAR(14) DEFAULT NULL,
  `customer_email` VARCHAR(255) DEFAULT NULL,
  `payer_name` VARCHAR(255) DEFAULT NULL,
  `payer_document` VARCHAR(14) DEFAULT NULL,
  `payer_bank` VARCHAR(100) DEFAULT NULL,
  `status` ENUM('waiting_payment', 'pending', 'processing', 'paid', 'approved', 'expired', 'cancelled', 'refused', 'failed') DEFAULT 'waiting_payment',
  `paid_at` TIMESTAMP NULL,
  `expires_at` TIMESTAMP NULL,
  `metadata` JSON DEFAULT NULL,
  `error_code` VARCHAR(50) DEFAULT NULL,
  `error_message` TEXT DEFAULT NULL,
  `webhook_sent` TINYINT(1) DEFAULT 0,
  `webhook_sent_at` TIMESTAMP NULL,
  `webhook_attempts` INT DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`seller_id`) REFERENCES `sellers`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`acquirer_id`) REFERENCES `acquirers`(`id`) ON DELETE RESTRICT,
  INDEX `idx_transaction_id` (`transaction_id`),
  INDEX `idx_external_id` (`external_id`),
  INDEX `idx_seller_id` (`seller_id`),
  INDEX `idx_status` (`status`),
  INDEX `idx_paid_at` (`paid_at`),
  INDEX `idx_created_at` (`created_at`),
  INDEX `idx_end_to_end_id` (`end_to_end_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pix_cashout` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `seller_id` INT UNSIGNED NOT NULL,
  `acquirer_id` INT UNSIGNED NOT NULL,
  `transaction_id` VARCHAR(100) NOT NULL UNIQUE COMMENT 'ID único interno',
  `external_id` VARCHAR(255) DEFAULT NULL COMMENT 'ID externo do seller',
  `acquirer_transaction_id` VARCHAR(255) DEFAULT NULL COMMENT 'ID na adquirente',
  `end_to_end_id` VARCHAR(255) DEFAULT NULL,
  `amount` DECIMAL(15,2) NOT NULL,
  `fee_amount` DECIMAL(15,2) DEFAULT 0.00,
  `net_amount` DECIMAL(15,2) NOT NULL,
  `pix_key` VARCHAR(1000) NOT NULL COMMENT 'Chave PIX ou copypaste completo',
  `pix_key_type` ENUM('cpf', 'cnpj', 'email', 'phone', 'evp', 'copypaste') NOT NULL,
  `net_payout` TINYINT(1) DEFAULT 1 COMMENT 'Taxa descontada do valor',
  `beneficiary_name` VARCHAR(255) DEFAULT NULL,
  `beneficiary_document` VARCHAR(14) DEFAULT NULL,
  `beneficiary_bank` VARCHAR(100) DEFAULT NULL,
  `status` ENUM('pending', 'processing', 'PENDING_QUEUE', 'completed', 'COMPLETED', 'failed', 'cancelled') DEFAULT 'pending',
  `processed_at` TIMESTAMP NULL,
  `metadata` JSON DEFAULT NULL,
  `error_code` VARCHAR(50) DEFAULT NULL,
  `error_message` TEXT DEFAULT NULL,
  `webhook_sent` TINYINT(1) DEFAULT 0,
  `webhook_sent_at` TIMESTAMP NULL,
  `webhook_attempts` INT DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`seller_id`) REFERENCES `sellers`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`acquirer_id`) REFERENCES `acquirers`(`id`) ON DELETE RESTRICT,
  INDEX `idx_transaction_id` (`transaction_id`),
  INDEX `idx_external_id` (`external_id`),
  INDEX `idx_seller_id` (`seller_id`),
  INDEX `idx_status` (`status`),
  INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `splits` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `cashin_id` INT UNSIGNED NOT NULL,
  `seller_id` INT UNSIGNED NOT NULL COMMENT 'Seller que receberá o split',
  `amount` DECIMAL(15,2) NOT NULL,
  `percentage` DECIMAL(5,2) DEFAULT NULL,
  `status` ENUM('pending', 'processed', 'failed') DEFAULT 'pending',
  `processed_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`cashin_id`) REFERENCES `pix_cashin`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`seller_id`) REFERENCES `sellers`(`id`) ON DELETE CASCADE,
  INDEX `idx_cashin_id` (`cashin_id`),
  INDEX `idx_seller_id` (`seller_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `webhooks_queue` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `seller_id` INT UNSIGNED NOT NULL,
  `transaction_id` VARCHAR(100) NOT NULL,
  `transaction_type` ENUM('cashin', 'cashout') NOT NULL,
  `webhook_url` VARCHAR(500) NOT NULL,
  `payload` JSON NOT NULL,
  `signature` VARCHAR(128) NOT NULL,
  `status` ENUM('pending', 'processing', 'sent', 'failed') DEFAULT 'pending',
  `attempts` INT DEFAULT 0,
  `max_attempts` INT DEFAULT 5,
  `next_retry_at` TIMESTAMP NULL,
  `last_error` TEXT DEFAULT NULL,
  `sent_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`seller_id`) REFERENCES `sellers`(`id`) ON DELETE CASCADE,
  INDEX `idx_status` (`status`),
  INDEX `idx_next_retry` (`next_retry_at`),
  INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `callbacks_acquirers` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `acquirer_id` INT UNSIGNED NOT NULL,
  `transaction_id` VARCHAR(100) DEFAULT NULL,
  `acquirer_transaction_id` VARCHAR(255) DEFAULT NULL,
  `payload` JSON NOT NULL,
  `headers` JSON DEFAULT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `status` ENUM('received', 'processed', 'error') DEFAULT 'received',
  `processed_at` TIMESTAMP NULL,
  `error_message` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`acquirer_id`) REFERENCES `acquirers`(`id`) ON DELETE CASCADE,
  INDEX `idx_acquirer_id` (`acquirer_id`),
  INDEX `idx_transaction_id` (`transaction_id`),
  INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `logs` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `level` ENUM('debug', 'info', 'warning', 'error', 'critical') DEFAULT 'info',
  `category` VARCHAR(50) NOT NULL COMMENT 'api, webhook, worker, auth, etc',
  `message` TEXT NOT NULL,
  `context` JSON DEFAULT NULL,
  `user_id` INT UNSIGNED DEFAULT NULL,
  `seller_id` INT UNSIGNED DEFAULT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `user_agent` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_level` (`level`),
  INDEX `idx_category` (`category`),
  INDEX `idx_created_at` (`created_at`),
  INDEX `idx_seller_id` (`seller_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `rate_limits` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `identifier` VARCHAR(255) NOT NULL COMMENT 'IP, API Key ou User ID',
  `endpoint` VARCHAR(255) NOT NULL,
  `requests` INT DEFAULT 0,
  `window_start` TIMESTAMP NOT NULL,
  `window_end` TIMESTAMP NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_rate_limit` (`identifier`, `endpoint`, `window_start`),
  INDEX `idx_identifier` (`identifier`),
  INDEX `idx_window_end` (`window_end`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `seller_documents` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `seller_id` INT UNSIGNED NOT NULL,
  `document_type` ENUM('rg_front', 'rg_back', 'cnh_front', 'cnh_back', 'cpf', 'selfie', 'proof_address', 'social_contract', 'cnpj', 'partner_docs') NOT NULL,
  `file_name` VARCHAR(255) NOT NULL,
  `file_path` VARCHAR(500) NOT NULL,
  `file_size` INT UNSIGNED NOT NULL COMMENT 'Tamanho em bytes',
  `mime_type` VARCHAR(100) NOT NULL,
  `status` ENUM('pending', 'under_review', 'approved', 'rejected') DEFAULT 'pending',
  `reviewed_by` INT UNSIGNED DEFAULT NULL COMMENT 'ID do admin que revisou',
  `reviewed_at` TIMESTAMP NULL,
  `rejection_reason` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`seller_id`) REFERENCES `sellers`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`reviewed_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  INDEX `idx_seller_id` (`seller_id`),
  INDEX `idx_status` (`status`),
  INDEX `idx_document_type` (`document_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `notifications` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED DEFAULT NULL,
  `seller_id` INT UNSIGNED DEFAULT NULL,
  `type` ENUM('info', 'success', 'warning', 'error', 'document_rejected', 'document_approved', 'account_approved', 'account_rejected') NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `message` TEXT NOT NULL,
  `link` VARCHAR(500) DEFAULT NULL,
  `is_read` TINYINT(1) DEFAULT 0,
  `read_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`seller_id`) REFERENCES `sellers`(`id`) ON DELETE CASCADE,
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_seller_id` (`seller_id`),
  INDEX `idx_is_read` (`is_read`),
  INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dados iniciais

INSERT INTO `sellers` (`name`, `email`, `document`, `phone`, `person_type`, `company_name`, `api_key`, `api_secret`, `status`, `document_status`, `approved_at`, `balance`, `daily_limit`, `daily_reset_at`, `fee_percentage_cashin`, `fee_percentage_cashout`) VALUES
('Seller Demo', 'seller@demo.com', '12345678000190', '11999999999', 'business', 'Empresa Demo LTDA', 'sk_test_demo_key_123456789', SHA2('demo_secret_key_987654321', 256), 'active', 'approved', NOW(), 0.00, 50000.00, CURDATE(), 0.0099, 0.0199);

INSERT INTO `users` (`seller_id`, `name`, `email`, `password`, `role`, `status`) VALUES
(NULL, 'Admin System', 'admin@gateway.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'active'),
(1, 'Seller Demo User', 'seller@demo.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'seller', 'active');

INSERT INTO `acquirers` (`name`, `code`, `api_url`, `priority_order`, `status`, `daily_limit`, `daily_reset_at`) VALUES
('Adquirente Principal', 'acquirer_main', 'https://api.acquirer-main.com/v1', 1, 'active', 100000.00, CURDATE()),
('Adquirente Backup', 'acquirer_backup', 'https://api.acquirer-backup.com/v1', 2, 'active', 50000.00, CURDATE());
