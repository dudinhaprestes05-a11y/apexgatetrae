# Instruções de Migração

Para aplicar as novas funcionalidades de controle administrativo, você precisa executar a migração do banco de dados.

## Executar Migração

Execute o seguinte comando no terminal:

```bash
php run_migration.php
```

Ou execute manualmente o SQL contido em `sql/add_seller_controls.sql` no seu banco de dados MySQL.

## Novos Campos Adicionados

A migração adiciona os seguintes campos na tabela `sellers`:

- `cashin_enabled` - Controle para ativar/desativar recebimentos PIX
- `cashout_enabled` - Controle para ativar/desativar saques PIX
- `temporarily_blocked` - Flag de bloqueio temporário
- `permanently_blocked` - Flag de bloqueio permanente
- `blocked_reason` - Motivo do bloqueio
- `blocked_at` - Data do bloqueio
- `blocked_by` - ID do admin que bloqueou
- `balance_retention` - Flag para reter saldo
- `revenue_retention_percentage` - Percentual de retenção do faturamento (0-100%)
- `retention_reason` - Motivo da retenção
- `retention_started_at` - Data de início da retenção
- `retention_started_by` - ID do admin que iniciou a retenção

## Verificar Migração

Após executar, você verá uma mensagem de sucesso e todos os novos controles estarão disponíveis na interface administrativa.
