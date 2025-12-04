# Sistema de Prioridade de Contas

## Visão Geral

O sistema de prioridade determina a ordem em que as contas de adquirente são utilizadas para processar transações de um seller. Quanto **menor o número de prioridade**, mais cedo a conta será utilizada.

## Como Funciona

### Ordem de Prioridade

- **Prioridade 1** = Primeira conta a ser usada
- **Prioridade 2** = Segunda conta a ser usada
- **Prioridade 3** = Terceira conta a ser usada
- E assim por diante...

### Exemplo Prático

Se um seller tem 3 contas configuradas:

```
Prioridade 1: Conta Principal A
Prioridade 2: Conta Principal B
Prioridade 3: Conta Backup
```

Quando uma transação for criada:
1. O sistema tentará usar **Conta Principal A** primeiro
2. Se falhar por motivo recuperável (saldo insuficiente, limite excedido, etc), tentará **Conta Principal B**
3. Se ainda falhar, tentará **Conta Backup**

## Estratégias de Distribuição

Além da prioridade, você pode configurar diferentes estratégias:

### 1. Priority Only (Padrão)
- Usa sempre a conta de maior prioridade disponível
- Só muda de conta em caso de falha
- **Recomendado para a maioria dos casos**

### 2. Round Robin
- Alterna entre as contas disponíveis
- Distribui a carga uniformemente
- Respeita a prioridade mas alterna entre contas de mesma prioridade

### 3. Least Used
- Usa a conta com menor número de transações
- Equilibra o uso entre todas as contas
- Útil para evitar atingir limites diários

### 4. Percentage
- Distribui transações por percentual configurado
- Exemplo: 70% na Conta A, 30% na Conta B
- Útil para contratos com volumes mínimos garantidos

## Correção Implementada

### Problema Anterior
O método `getAccountsBySeller()` não estava ordenando as contas por prioridade, causando seleção aleatória.

### Solução Aplicada

**1. Correção na Query SQL**
```php
// Antes (ERRADO - sem ORDER BY)
$sql = "SELECT acquirer_account_id
        FROM seller_acquirer_accounts
        WHERE seller_id = ? AND is_active = 1";

// Depois (CORRETO - com ORDER BY)
$sql = "SELECT acquirer_account_id
        FROM seller_acquirer_accounts
        WHERE seller_id = ? AND is_active = 1
        ORDER BY priority ASC, id ASC";
```

**2. Preservação da Ordem**
```php
// Garante que array_diff não altera a ordem
$sellerAccountIds = array_values(array_diff($sellerAccountIds, $excludeAccountIds));
```

**3. Logs Adicionados**
```php
// Agora loga a ordem dos IDs selecionados
$this->logModel->info('acquirer', 'Selecting from seller accounts in priority order', [
    'seller_id' => $sellerId,
    'account_ids' => $sellerAccountIds,
    'amount' => $amount
]);
```

## Como Configurar Prioridades

### Via Painel Administrativo

1. Acesse: **Admin > Sellers > Ver Detalhes**
2. Role até **"Gerenciar Contas"**
3. Para cada conta, defina a prioridade (número entre 1-99)
4. Clique em **"Salvar Alterações"**

### Via API (AdminController)

```php
POST /admin/sellers/accounts/assign

{
    "seller_id": 1,
    "account_id": 5,
    "priority": 1,
    "is_active": true
}
```

### Via SQL Direto

```sql
-- Atualizar prioridade de uma conta específica
UPDATE seller_acquirer_accounts
SET priority = 1
WHERE seller_id = 1 AND acquirer_account_id = 5;

-- Reordenar todas as contas de um seller
UPDATE seller_acquirer_accounts SET priority = 1 WHERE seller_id = 1 AND acquirer_account_id = 5;
UPDATE seller_acquirer_accounts SET priority = 2 WHERE seller_id = 1 AND acquirer_account_id = 7;
UPDATE seller_acquirer_accounts SET priority = 3 WHERE seller_id = 1 AND acquirer_account_id = 9;
```

## Testando a Prioridade

Execute o script de teste:

```bash
php test_priority.php [seller_id]
```

**Exemplo:**
```bash
php test_priority.php 1
```

Este script irá:
1. Listar todas as contas do seller ordenadas por prioridade
2. Simular 5 tentativas de transação com fallback
3. Testar diferentes valores de transação
4. Verificar a ordem dos IDs retornados

### Saída Esperada

```
=== TESTE DE PRIORIDADE DAS CONTAS ===

Testando para Seller ID: 1

1. CONTAS CONFIGURADAS (ordenadas por prioridade):
--------------------------------------------------------------------------------
Prioridade 1: [✓ Ativa] PodPay - Conta Principal 1 (ACC-000001)
  - ID da Conta: 5
  - Status da Conta: ✓
  - Saldo: R$ 10.000,00

Prioridade 2: [✓ Ativa] PodPay - Conta Principal 2 (ACC-000002)
  - ID da Conta: 7
  - Status da Conta: ✓
  - Saldo: R$ 8.500,00

Prioridade 3: [✓ Ativa] PodPay - Conta Backup (ACC-000003)
  - ID da Conta: 9
  - Status da Conta: ✓
  - Saldo: R$ 5.000,00

2. TESTE DE SELEÇÃO (simulando transação de R$ 100,00):
--------------------------------------------------------------------------------
Tentativa 1:
  ✓ Conta selecionada: Conta Principal 1 (ID: 5)

Tentativa 2:
  ✓ Conta selecionada: Conta Principal 2 (ID: 7)

Tentativa 3:
  ✓ Conta selecionada: Conta Backup (ID: 9)
```

## Fallback Automático

O sistema tem fallback automático quando uma transação falha:

```php
Tentativa 1: Usa conta de Prioridade 1
  ↓ (falha recuperável)
Tentativa 2: Usa conta de Prioridade 2
  ↓ (falha recuperável)
Tentativa 3: Usa conta de Prioridade 3
  ↓ (falha recuperável)
...até 5 tentativas ou sucesso
```

### Erros Recuperáveis (Tentam Próxima Conta)
- Saldo insuficiente
- Limite excedido
- Timeout
- Erro de conexão
- Serviço indisponível

### Erros Não Recuperáveis (Param Imediatamente)
- Credenciais inválidas
- Chave PIX inválida
- Documento inválido
- Dados de transação incorretos

## Logs do Sistema

Os logs agora mostram claramente a seleção de contas:

```
[INFO] Selecting from seller accounts in priority order
  - seller_id: 1
  - account_ids: [5, 7, 9]
  - amount: 100

[INFO] Seller-specific account selected
  - seller_id: 1
  - account_id: 5
  - account_name: Conta Principal 1
  - acquirer_code: podpay
  - amount: 100
```

## Boas Práticas

### 1. Configure Sempre uma Conta Backup
```
Prioridade 1: Conta Principal
Prioridade 2: Conta Backup
```

### 2. Use Prioridades Espaçadas
```
✓ BOM: 1, 5, 10 (fácil inserir entre elas)
✗ RUIM: 1, 2, 3 (difícil reordenar depois)
```

### 3. Teste Após Configurar
```bash
php test_priority.php <seller_id>
```

### 4. Monitore os Logs
```bash
tail -f app/logs/app.log | grep "account selected"
```

### 5. Mantenha Contas Ativas com Saldo
- Contas sem saldo serão puladas automaticamente
- Sistema tentará próxima conta da lista
- Evite deixar todas as contas sem saldo

## Troubleshooting

### Problema: Sempre usa a mesma conta
**Causa**: Estratégia configurada como "priority_only"
**Solução**: Mude para "round_robin" ou "least_used" se quiser distribuir

### Problema: Não respeita prioridade
**Causa**: Prioridades iguais em múltiplas contas
**Solução**: Certifique-se que cada conta tem prioridade diferente

### Problema: Conta de prioridade 1 nunca é usada
**Causa**: Conta inativa ou sem atender critérios de valor
**Solução**: Verifique se a conta está ativa e suporta o valor da transação

### Verificar Configuração Atual
```sql
SELECT
    saa.priority,
    saa.is_active,
    aa.name as account_name,
    aa.is_active as account_active
FROM seller_acquirer_accounts saa
JOIN acquirer_accounts aa ON aa.id = saa.acquirer_account_id
WHERE saa.seller_id = 1
ORDER BY saa.priority ASC;
```

## Referências

- [MULTI_ACCOUNT_ACQUIRER_SYSTEM.md](MULTI_ACCOUNT_ACQUIRER_SYSTEM.md) - Sistema completo de múltiplas contas
- [ACCOUNT_IDENTIFIERS.md](ACCOUNT_IDENTIFIERS.md) - Diferença entre os identificadores
- [API_ROUTES_ACQUIRER_ACCOUNTS.md](API_ROUTES_ACQUIRER_ACCOUNTS.md) - APIs de gerenciamento

## Suporte

Para problemas relacionados à prioridade:

1. Execute o script de teste: `php test_priority.php <seller_id>`
2. Verifique os logs: `tail -f app/logs/app.log`
3. Confirme as configurações no banco de dados
4. Certifique-se que as contas estão ativas e com saldo
