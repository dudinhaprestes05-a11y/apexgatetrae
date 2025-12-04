# Sistema de Limites por Transação

## Visão Geral

Sistema completo de controle de limites por transação que permite ao admin configurar:
- Limites mínimos e máximos por transação (cash in e cash out) para cada seller
- Limite diário específico para cash out por seller
- Limites por transação em cada conta adquirente
- Seleção automática de conta baseada no valor da transação

## Mudanças Implementadas

### 1. Banco de Dados

**Migration:** `sql/add_transaction_limits.sql`

#### Tabela `sellers`
- `min_cashin_amount` - Valor mínimo por transação cash in
- `max_cashin_amount` - Valor máximo por transação cash in
- `min_cashout_amount` - Valor mínimo por transação cash out
- `max_cashout_amount` - Valor máximo por transação cash out
- `cashout_daily_limit` - Limite diário específico para cash out
- `cashout_daily_used` - Total usado no dia para cash out
- `cashout_daily_reset_at` - Data de reset do limite diário

#### Tabela `acquirer_accounts`
- `max_cashin_per_transaction` - Limite máximo por transação cash in
- `max_cashout_per_transaction` - Limite máximo por transação cash out
- `min_cashin_per_transaction` - Limite mínimo por transação cash in (padrão: 0.01)
- `min_cashout_per_transaction` - Limite mínimo por transação cash out (padrão: 0.01)

#### Tabela `acquirers`
- **Removido:** `daily_limit`, `daily_used`, `daily_reset_at`

### 2. Models

#### Seller.php
- `checkDailyCashoutLimit($sellerId, $amount)` - Verifica limite diário de cashout
- `incrementDailyCashoutUsed($sellerId, $amount)` - Incrementa uso diário de cashout
- `checkTransactionLimits($sellerId, $amount, $type)` - Valida limites min/max

#### AcquirerAccount.php
- `getNextAccountForSellerWithAmount($sellerId, $amount, $type, $excludeIds)` - Seleciona conta que suporta o valor
- `getMaxTransactionLimitForSeller($sellerId, $type)` - Retorna o limite máximo disponível

#### Acquirer.php
- **Removido:** `checkDailyLimit()`, `incrementDailyUsed()`

### 3. Services

#### AcquirerService.php
- Modificado `selectAccountForSeller()` para aceitar parâmetro `$amount`
- Usa `getNextAccountForSellerWithAmount()` para filtrar contas pelo valor da transação
- Remove validação de limite diário da adquirente (não existe mais)

### 4. Controllers API

#### PixController.php
- Valida limites min/max do seller antes de criar transação
- Retorna erro genérico quando nenhuma conta suporta o valor: "Valor da transação excede o limite permitido"

#### CashoutController.php
- Valida limites min/max do seller
- Valida limite diário de cashout do seller
- Incrementa `cashout_daily_used` após transação bem-sucedida
- Retorna erro genérico quando nenhuma conta suporta o valor

### 5. AdminController

#### Novos Métodos
- `updateSellerLimits($sellerId)` - Atualiza limites de transação do seller

#### Métodos Modificados
- `createAcquirerAccount()` - Inclui limites de transação
- `updateAcquirerAccount($accountId)` - Permite editar limites de transação

### 6. Views Admin

#### seller-details.php
Novo formulário "Limites de Transação":
- Valor mínimo/máximo cash in
- Valor mínimo/máximo cash out
- Limite diário cash out

#### acquirer-accounts.php
Seção "Limites por Transação" no modal de conta:
- Max cash in/cash out por transação
- Min cash in/cash out por transação

### 7. Rotas

**Nova rota:** `/admin/sellers/{id}/limits` (POST) - Atualiza limites do seller

## Fluxo de Validação

### Cash In

1. Validar se valor está dentro dos limites min/max do seller
2. Buscar contas do seller que suportam o valor:
   - `amount >= min_cashin_per_transaction` (conta)
   - `amount <= max_cashin_per_transaction` (conta) ou sem limite
3. Se nenhuma conta: retornar erro genérico
4. Criar transação na conta selecionada

### Cash Out

1. Validar se valor está dentro dos limites min/max do seller
2. Validar se não excede limite diário de cashout do seller
3. Buscar contas do seller que suportam o valor:
   - `amount >= min_cashout_per_transaction` (conta)
   - `amount <= max_cashout_per_transaction` (conta) ou sem limite
4. Se nenhuma conta: retornar erro genérico
5. Criar transação e incrementar `cashout_daily_used`

## Mensagens de Erro

### Erros Específicos (limites do seller)
- "Valor abaixo do mínimo permitido"
- "Valor acima do máximo permitido"
- "Limite diário de saque excedido"

### Erro Genérico (limites das contas)
- "Valor da transação excede o limite permitido"

**Nota:** Não menciona que é problema da conta/adquirente para não expor informações internas.

## Exemplo de Uso

### Cenário
Seller tem 3 contas configuradas:
- Conta A: max_cashin = R$ 1.000
- Conta B: max_cashin = R$ 2.000
- Conta C: max_cashin = R$ 5.000

### Transação de R$ 2.500
- Sistema verifica limites do seller ✓
- Sistema filtra contas: apenas Conta C suporta R$ 2.500
- Transação criada na Conta C ✓

### Transação de R$ 10.000
- Sistema verifica limites do seller ✓
- Sistema filtra contas: nenhuma suporta R$ 10.000
- **Retorna:** "Valor da transação excede o limite permitido" ✗

## Seleção Automática de Conta

O sistema seleciona automaticamente a conta adequada baseado em:
1. Contas configuradas para o seller
2. Limites min/max da transação suportados pela conta
3. Prioridade e estratégia de distribuição configurada
4. Disponibilidade da conta (ativa)

## Valores Padrão

- **Sellers novos:** Limites null (sem restrição, exceto das contas)
- **Contas novas:** Min = 0.01, Max = null (sem limite superior)
- **Cashout daily limit:** null (sem limite por padrão)

## Migração

Execute a migration:
```bash
php apply_migration.php sql/add_transaction_limits.sql
```

Ou via MySQL:
```bash
mysql -u usuario -p database < sql/add_transaction_limits.sql
```

## Notas Importantes

1. Limite `daily_limit` em sellers agora aplica-se apenas a cash in
2. Cash out tem seu próprio limite diário independente
3. Limites null significam "sem restrição"
4. Sistema prioriza segurança: rejeita transação se nenhuma conta suporta
5. Logs detalhados para auditoria de seleção de contas
