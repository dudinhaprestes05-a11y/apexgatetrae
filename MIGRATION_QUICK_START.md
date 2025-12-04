# Migração Multi-Conta - Início Rápido

Guia rápido para migrar o sistema de conta única para múltiplas contas.

## 📋 Pré-requisitos

1. Backup do banco de dados
2. Credenciais PodPay configuradas
3. Acesso ao servidor via terminal

## 🚀 Passo a Passo (5 minutos)

### 1. Verificar pré-requisitos

```bash
php verify_before_migration.php
```

Se aparecer "✓ System is ready for migration!", continue para o passo 2.

### 2. Verificar status atual

```bash
php check_migration_status.php
```

Se aparecer "Status: ✗ NOT MIGRATED", continue para o passo 3.

### 3. Executar migração

```bash
php migrate_to_multi_account.php
```

- Responda "yes" quando solicitado
- O script criará backup automático
- Aguarde a conclusão (pode levar 1-2 minutos)

### 4. Verificar resultado

```bash
php check_migration_status.php
```

Deve aparecer "Status: ✓ MIGRATED"

### 5. Testar sistema

```bash
php test_podpay.php
```

Se tudo estiver OK, teste criar uma transação PIX pelo admin ou seller.

## ✅ O que foi feito?

- ✓ Criadas tabelas `acquirer_accounts` e `seller_acquirer_accounts`
- ✓ Conta PodPay existente migrada como "Default Account"
- ✓ Todos os sellers ativos vinculados à conta
- ✓ Transações existentes vinculadas à conta
- ✓ Backup automático criado

## 🔄 Se algo der errado

### Opção 1: Rollback automático

```bash
php rollback_migration.php
```

### Opção 2: Restaurar do backup

```bash
# Lista os backups disponíveis
ls -lh backup_migration_*.sql

# Restaura (substitua YYYY-MM-DD_HHmmss pela data/hora do backup)
mysql -u usuario -p nome_banco < backup_migration_YYYY-MM-DD_HHmmss.sql
```

## 📁 Arquivos criados pela migração

- `backup_migration_YYYY-MM-DD_HHmmss.sql` - Backup automático
- `migration_YYYY-MM-DD_HHmmss.log` - Log detalhado
- `rollback_YYYY-MM-DD_HHmmss.log` - Log de rollback (se executado)

## 📚 Próximos passos

Após migração bem-sucedida:

1. **Adicionar mais contas** (opcional)
   - Acesse `/admin/acquirers`
   - Clique em "Adicionar Conta"

2. **Configurar distribuição** (opcional)
   - Vá em "Gerenciar Contas" de um seller
   - Configure prioridades e estratégias

3. **Monitorar uso**
   - Veja estatísticas em `/admin/acquirers`
   - Ajuste conforme necessário

## 🆘 Suporte

Se encontrar problemas:

1. Verifique o log: `migration_YYYY-MM-DD_HHmmss.log`
2. Execute: `php check_migration_status.php`
3. Consulte: [MIGRATION_TO_MULTI_ACCOUNT.md](MIGRATION_TO_MULTI_ACCOUNT.md)

## ⚡ Comandos úteis

```bash
# Verificar se está pronto para migrar
php verify_before_migration.php

# Status da migração
php check_migration_status.php

# Executar migração
php migrate_to_multi_account.php

# Reverter migração
php rollback_migration.php

# Testar PodPay
php test_podpay.php

# Verificar credenciais
php check_acquirer.php
```

## 🔐 Segurança

- ✓ Backup automático criado antes da migração
- ✓ Nenhum dado é deletado
- ✓ Credenciais antigas preservadas em `system_settings`
- ✓ RLS (Row Level Security) aplicado automaticamente
- ✓ Rollback disponível a qualquer momento

## ⏱️ Tempo estimado

- Verificação pré-requisitos: ~10 segundos
- Verificação status: ~10 segundos
- Migração: ~1-2 minutos
- Verificação pós-migração: ~10 segundos
- **Total: ~2-3 minutos**

## 💡 Dica

Execute primeiro em ambiente de teste antes de aplicar em produção!
