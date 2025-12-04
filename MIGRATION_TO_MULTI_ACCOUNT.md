# Migração para Sistema Multi-Conta

Este documento descreve o processo de migração do sistema de conta única para o sistema de múltiplas contas de adquirente.

## O que muda?

### Antes (Sistema Antigo)
- Uma única conta PodPay configurada nas settings do sistema
- Todos os sellers usam a mesma conta
- Credenciais armazenadas na tabela `system_settings`

### Depois (Sistema Novo)
- Suporte para múltiplas contas de qualquer adquirente
- Cada seller pode ter uma ou mais contas atribuídas
- Sistema de fallback e distribuição de carga
- Credenciais armazenadas na tabela `acquirer_accounts`

## Estrutura das Novas Tabelas

### `acquirer_accounts`
Armazena as contas individuais de cada adquirente:
- Credenciais (client_id, client_secret, merchant_id)
- Nome da conta (para identificação)
- Status e saldo

### `seller_acquirer_accounts`
Relaciona sellers com contas:
- Define prioridades para fallback
- Configura estratégias de distribuição
- Rastreia uso e estatísticas

## Pré-requisitos

1. **Backup do banco de dados**
   ```bash
   mysqldump -u usuario -p nome_banco > backup_antes_migracao.sql
   ```

2. **Verificar credenciais PodPay atuais**
   - Acesse o admin e verifique se as credenciais estão configuradas
   - Ou execute: `php check_acquirer.php`

3. **Ambiente de teste (recomendado)**
   - Execute primeiro em ambiente de teste
   - Valide todas as funcionalidades antes de aplicar em produção

## Como Executar a Migração

### Passo 1: Executar o script de migração

```bash
php migrate_to_multi_account.php
```

O script irá:
1. Criar backup automático dos dados atuais
2. Solicitar confirmação para continuar
3. Aplicar as mudanças no schema do banco
4. Migrar a conta PodPay existente
5. Atribuir a conta a todos os sellers ativos
6. Atualizar transações existentes
7. Verificar se tudo foi aplicado corretamente

### Passo 2: Verificar os logs

Após a execução, verifique:
- `migration_YYYY-MM-DD_HHmmss.log` - Log detalhado da migração
- `backup_migration_YYYY-MM-DD_HHmmss.sql` - Backup automático

### Passo 3: Testar o sistema

1. **Teste de autenticação:**
   ```bash
   php test_podpay.php
   ```

2. **Teste de criação de PIX:**
   - Acesse o sistema como seller
   - Crie uma transação PIX de teste
   - Verifique se o QR Code é gerado

3. **Verificar painel admin:**
   - Acesse `/admin/acquirers`
   - Verifique se a conta aparece corretamente
   - Confira as atribuições de sellers

## O que fazer se algo der errado?

### Opção 1: Restaurar do backup automático

```bash
mysql -u usuario -p nome_banco < backup_migration_YYYY-MM-DD_HHmmss.sql
```

### Opção 2: Restaurar do backup manual

Se você fez backup antes da migração:

```bash
mysql -u usuario -p nome_banco < backup_antes_migracao.sql
```

### Opção 3: Executar rollback manual

```sql
-- Remover novas tabelas
DROP TABLE IF EXISTS seller_acquirer_accounts;
DROP TABLE IF EXISTS acquirer_accounts;

-- Remover colunas das tabelas de transações
ALTER TABLE pix_cashin DROP COLUMN IF EXISTS acquirer_account_id;
ALTER TABLE pix_cashout DROP COLUMN IF EXISTS acquirer_account_id;

-- Restaurar estrutura antiga da tabela acquirers (se necessário)
ALTER TABLE acquirers ADD COLUMN IF NOT EXISTS client_id VARCHAR(255);
ALTER TABLE acquirers ADD COLUMN IF NOT EXISTS client_secret VARCHAR(255);
ALTER TABLE acquirers ADD COLUMN IF NOT EXISTS merchant_id VARCHAR(255);
```

## Verificações Pós-Migração

Execute estas queries para verificar se tudo está correto:

```sql
-- Verificar contas criadas
SELECT * FROM acquirer_accounts;

-- Verificar atribuições de sellers
SELECT
    s.name as seller_name,
    aa.name as account_name,
    saa.priority,
    saa.distribution_strategy
FROM seller_acquirer_accounts saa
JOIN sellers s ON s.id = saa.seller_id
JOIN acquirer_accounts aa ON aa.id = saa.acquirer_account_id;

-- Verificar transações atualizadas
SELECT
    COUNT(*) as total,
    COUNT(acquirer_account_id) as com_conta,
    COUNT(*) - COUNT(acquirer_account_id) as sem_conta
FROM pix_cashin;
```

## Próximos Passos

Após a migração bem-sucedida:

1. **Adicionar mais contas** (opcional)
   - Acesse `/admin/acquirers`
   - Adicione novas contas PodPay ou de outras adquirentes
   - Configure prioridades e estratégias de distribuição

2. **Configurar sellers específicos**
   - Alguns sellers podem ter contas dedicadas
   - Configure distribuição de carga por porcentagem
   - Ative fallback automático

3. **Monitorar uso**
   - Acompanhe estatísticas de uso de cada conta
   - Ajuste estratégias conforme necessário
   - Configure limites e alertas

## Suporte

Se encontrar problemas:

1. Consulte o arquivo de log da migração
2. Verifique os logs do sistema em `/admin/logs`
3. Execute `php check_acquirer.php` para validar credenciais
4. Em caso de dúvidas, consulte a documentação técnica

## Notas Importantes

- ⚠️ **Não delete** os arquivos de backup até ter certeza que tudo está funcionando
- ⚠️ A migração é **segura** e não deleta dados existentes
- ⚠️ As credenciais antigas em `system_settings` **não são removidas** automaticamente
- ✓ Todas as transações existentes continuam acessíveis
- ✓ O sistema continua funcionando durante e após a migração
