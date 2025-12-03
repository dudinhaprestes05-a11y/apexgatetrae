/*
  # Tabela de Configurações do Sistema

  1. Nova Tabela
    - `system_settings`
      - `id` (int, primary key)
      - `default_fee_percentage_cashin` - Taxa percentual padrão para cash-in (decimal 5,4)
      - `default_fee_fixed_cashin` - Taxa fixa padrão para cash-in (decimal 10,2)
      - `default_fee_percentage_cashout` - Taxa percentual padrão para cash-out (decimal 5,4)
      - `default_fee_fixed_cashout` - Taxa fixa padrão para cash-out (decimal 10,2)
      - `updated_at` - Data da última atualização
      - `updated_by` - ID do admin que atualizou

  2. Dados Iniciais
    - Insere configurações padrão com taxas zeradas
*/

CREATE TABLE IF NOT EXISTS system_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    default_fee_percentage_cashin DECIMAL(5,4) DEFAULT 0.0000,
    default_fee_fixed_cashin DECIMAL(10,2) DEFAULT 0.00,
    default_fee_percentage_cashout DECIMAL(5,4) DEFAULT 0.0000,
    default_fee_fixed_cashout DECIMAL(10,2) DEFAULT 0.00,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    updated_by INT,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Insere configuração inicial
INSERT INTO system_settings (id, default_fee_percentage_cashin, default_fee_fixed_cashin, default_fee_percentage_cashout, default_fee_fixed_cashout)
VALUES (1, 0.0000, 0.00, 0.0000, 0.00)
ON DUPLICATE KEY UPDATE id = id;
