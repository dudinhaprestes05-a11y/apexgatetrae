/*
  # Sistema de Limites por Transação

  ## Descrição
  Este migration implementa um sistema completo de controle de limites por transação,
  substituindo o controle de volume diário das adquirentes por limites específicos
  em cada conta e seller.

  ## 1. Mudanças na Tabela `sellers`
    - `cashout_daily_limit` - Limite diário específico para operações de saque
    - `cashout_daily_used` - Total usado no dia para saques
    - `cashout_daily_reset_at` - Data de reset do limite diário de saque
    - `min_cashin_amount` - Valor mínimo permitido por transação de cash in
    - `max_cashin_amount` - Valor máximo permitido por transação de cash in
    - `min_cashout_amount` - Valor mínimo permitido por transação de cash out
    - `max_cashout_amount` - Valor máximo permitido por transação de cash out

    Nota: O campo `daily_limit` existente agora aplica-se apenas a cash in.
    Cash out terá seu próprio limite diário independente.

  ## 2. Mudanças na Tabela `acquirers`
    - Remove `daily_limit` - Limite de volume não é mais controlado por adquirente
    - Remove `daily_used` - Sem controle de volume diário
    - Remove `daily_reset_at` - Sem controle de volume diário

  ## 3. Mudanças na Tabela `acquirer_accounts`
    - `max_cashin_per_transaction` - Limite máximo por transação de cash in
    - `max_cashout_per_transaction` - Limite máximo por transação de cash out
    - `min_cashin_per_transaction` - Limite mínimo por transação de cash in
    - `min_cashout_per_transaction` - Limite mínimo por transação de cash out

  ## 4. Lógica de Validação

  ### Cash In:
  1. Validar se o valor está dentro dos limites min/max do seller
  2. Buscar contas do seller que suportam o valor da transação
  3. Se nenhuma conta suporta: retornar erro genérico
  4. Criar transação na conta selecionada

  ### Cash Out:
  1. Validar se o valor está dentro dos limites min/max do seller
  2. Validar se não excede o limite diário de cashout do seller
  3. Buscar contas do seller que suportam o valor da transação
  4. Se nenhuma conta suporta: retornar erro genérico
  5. Criar transação e incrementar cashout_daily_used

  ## 5. Estratégia de Seleção de Conta

  O sistema seleciona automaticamente a conta adequada baseado em:
  - Contas configuradas para o seller
  - Limites min/max da transação suportados pela conta
  - Prioridade e estratégia de distribuição
  - Disponibilidade da conta (ativa)

  ## 6. Valores Padrão

  - Sellers novos: limites null (sem restrição, exceto das contas)
  - Contas novas: min = 0.01, max = null (sem limite superior)
  - Cashout daily limit: null (sem limite por padrão)
*/

-- ============================================================================
-- STEP 1: Adicionar controles de limite por transação na tabela sellers
-- ============================================================================

ALTER TABLE sellers
  ADD COLUMN IF NOT EXISTS cashout_daily_limit DECIMAL(15,2) DEFAULT NULL COMMENT 'Limite diário específico para cashout (null = sem limite)',
  ADD COLUMN IF NOT EXISTS cashout_daily_used DECIMAL(15,2) DEFAULT 0.00 COMMENT 'Total usado no dia atual para cashout',
  ADD COLUMN IF NOT EXISTS cashout_daily_reset_at DATE DEFAULT NULL COMMENT 'Data de reset do limite diário de cashout',
  ADD COLUMN IF NOT EXISTS min_cashin_amount DECIMAL(15,2) DEFAULT NULL COMMENT 'Valor mínimo por transação cashin (null = sem limite)',
  ADD COLUMN IF NOT EXISTS max_cashin_amount DECIMAL(15,2) DEFAULT NULL COMMENT 'Valor máximo por transação cashin (null = sem limite)',
  ADD COLUMN IF NOT EXISTS min_cashout_amount DECIMAL(15,2) DEFAULT NULL COMMENT 'Valor mínimo por transação cashout (null = sem limite)',
  ADD COLUMN IF NOT EXISTS max_cashout_amount DECIMAL(15,2) DEFAULT NULL COMMENT 'Valor máximo por transação cashout (null = sem limite)';

-- Adicionar índice para otimizar reset diário de cashout
ALTER TABLE sellers
  ADD INDEX IF NOT EXISTS idx_cashout_daily_reset (cashout_daily_reset_at);

-- ============================================================================
-- STEP 2: Remover controles de volume diário da tabela acquirers
-- ============================================================================

-- Remover colunas de limite diário (não mais necessárias)
ALTER TABLE acquirers
  DROP COLUMN IF EXISTS daily_limit,
  DROP COLUMN IF EXISTS daily_used,
  DROP COLUMN IF EXISTS daily_reset_at;

-- ============================================================================
-- STEP 3: Adicionar limites por transação na tabela acquirer_accounts
-- ============================================================================

ALTER TABLE acquirer_accounts
  ADD COLUMN IF NOT EXISTS max_cashin_per_transaction DECIMAL(15,2) DEFAULT NULL COMMENT 'Limite máximo por transação cashin (null = sem limite)',
  ADD COLUMN IF NOT EXISTS max_cashout_per_transaction DECIMAL(15,2) DEFAULT NULL COMMENT 'Limite máximo por transação cashout (null = sem limite)',
  ADD COLUMN IF NOT EXISTS min_cashin_per_transaction DECIMAL(15,2) DEFAULT 0.01 COMMENT 'Limite mínimo por transação cashin',
  ADD COLUMN IF NOT EXISTS min_cashout_per_transaction DECIMAL(15,2) DEFAULT 0.01 COMMENT 'Limite mínimo por transação cashout';

-- Adicionar índices compostos para otimizar seleção de contas por limite
ALTER TABLE acquirer_accounts
  ADD INDEX IF NOT EXISTS idx_cashin_limits (is_active, max_cashin_per_transaction),
  ADD INDEX IF NOT EXISTS idx_cashout_limits (is_active, max_cashout_per_transaction);

-- ============================================================================
-- STEP 4: Inicializar dados para sellers e contas existentes
-- ============================================================================

-- Inicializar cashout_daily_reset_at para sellers existentes
UPDATE sellers
SET cashout_daily_reset_at = CURDATE()
WHERE cashout_daily_reset_at IS NULL;

-- ============================================================================
-- STEP 5: Comentários e documentação
-- ============================================================================

-- O campo daily_limit em sellers agora se refere apenas a cash in
-- Para cash out, usar o novo campo cashout_daily_limit

-- Limites null significam "sem restrição"
-- Exemplo: max_cashin_amount = null significa que o seller pode fazer cashin de qualquer valor
-- (limitado apenas pelos limites das contas adquirentes)

-- A lógica de validação é implementada em:
-- - app/models/Seller.php (validação de limites do seller)
-- - app/models/AcquirerAccount.php (seleção de contas por limite)
-- - app/services/AcquirerService.php (orquestração e seleção de conta)
-- - app/controllers/api/PixController.php (validação de cash in)
-- - app/controllers/api/CashoutController.php (validação de cash out)
