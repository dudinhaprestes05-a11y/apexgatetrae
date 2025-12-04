# Sistema de Múltiplas Contas de Adquirente

## Visão Geral

O sistema agora suporta múltiplas contas para uma mesma adquirente (ex: PodPay), permitindo que o admin configure várias contas com credenciais diferentes e distribua essas contas entre os sellers.

## Estrutura

### 1. Adquirentes (acquirers)
- Representa o **tipo** de adquirente (PodPay, OutraGateway, etc)
- Contém informações gerais: nome, código, base_url, recursos suportados
- **Não contém mais credenciais** (client_id, client_secret, merchant_id)

### 2. Contas de Adquirente (acquirer_accounts)
- Representa **contas individuais** de uma adquirente
- Cada conta tem suas próprias credenciais (client_id, client_secret, merchant_id)
- Cada conta tem um nome identificador (ex: "Conta Principal", "Conta Backup", etc)
- Pode rastrear saldo disponível
- Pode ser ativada/desativada individualmente

### 3. Relacionamento Seller-Conta (seller_acquirer_accounts)
- Define quais contas cada seller pode usar
- Configurações por seller:
  - **Prioridade**: ordem de preferência das contas (1 = primeira tentativa)
  - **Estratégia de Distribuição**:
    - `priority_only`: usa sempre a conta de maior prioridade
    - `round_robin`: alterna entre contas disponíveis
    - `least_used`: usa a conta menos utilizada
    - `percentage`: distribui por percentual configurado
  - **Percentual de alocação**: para estratégia de percentual
  - Estatísticas: total de transações, volume, última utilização

## Funcionalidades

### Fallback Automático

Quando uma transação falha por motivos recuperáveis (ex: saldo insuficiente), o sistema automaticamente:

1. Identifica o erro como recuperável
2. Exclui a conta que falhou
3. Seleciona a próxima conta disponível baseada na prioridade
4. Tenta a transação novamente
5. Repete até 5 vezes ou até sucesso

**Erros recuperáveis incluem**:
- Saldo insuficiente
- Limite excedido
- Timeout
- Erro de conexão
- Serviço indisponível

### Distribuição de Carga

Dependendo da estratégia configurada, o sistema pode:
- Usar sempre a mesma conta (priority_only)
- Balancear entre múltiplas contas (round_robin)
- Usar a conta menos utilizada (least_used)
- Distribuir por percentual configurado (percentage)

## Fluxo de Uso

### Para o Admin

#### 1. Adicionar Conta de Adquirente

```http
POST /admin/acquirers/{acquirer_id}/accounts
```

**Parâmetros**:
- `name`: Nome da conta (ex: "Conta Principal")
- `client_id`: Client ID da conta
- `client_secret`: Client Secret da conta
- `merchant_id`: Merchant ID da conta
- `balance`: Saldo inicial (opcional)
- `is_active`: Status ativo/inativo

#### 2. Atribuir Conta a Seller

```http
POST /admin/sellers/{seller_id}/accounts
```

**Parâmetros**:
- `account_id`: ID da conta de adquirente
- `priority`: Ordem de prioridade (1, 2, 3...)
- `strategy`: Estratégia de distribuição
- `percentage`: Percentual de alocação (para estratégia percentage)

#### 3. Gerenciar Contas

- **Listar contas**: `GET /admin/acquirers/{acquirer_id}/accounts`
- **Atualizar conta**: `POST /admin/accounts/{account_id}/update`
- **Excluir conta**: `POST /admin/accounts/{account_id}/delete`
- **Ver contas do seller**: `GET /admin/sellers/{seller_id}/accounts`
- **Remover conta do seller**: `POST /admin/sellers/{seller_id}/accounts/{account_id}/remove`
- **Ativar/desativar conta**: `POST /admin/sellers/{seller_id}/accounts/{account_id}/toggle`

### Para Transações

O sistema de transações foi atualizado para usar o novo sistema de contas:

#### PIX Cashin com Fallback

```php
$acquirerService = new AcquirerService();
$result = $acquirerService->createPixCashinWithFallback($sellerId, $data);

if ($result['success']) {
    // Transação criada com sucesso
    $accountId = $result['account_id']; // ID da conta que processou
    $transactionData = $result['data'];
} else {
    // Falhou em todas as contas disponíveis
    $error = $result['error'];
}
```

#### PIX Cashout com Fallback

```php
$acquirerService = new AcquirerService();
$result = $acquirerService->createPixCashoutWithFallback($sellerId, $data);

if ($result['success']) {
    // Transferência criada com sucesso
    $accountId = $result['account_id'];
    $transferData = $result['data'];
} else {
    // Falhou (saldo insuficiente em todas as contas, etc)
    $error = $result['error'];
}
```

## Migração de Dados

A migração SQL (`add_multi_account_acquirer_system.sql`) automaticamente:

1. Cria as novas tabelas
2. Migra credenciais existentes do PodPay para uma conta padrão
3. Atribui a conta padrão a todos os sellers ativos
4. Adiciona colunas nas transações para rastrear qual conta foi usada
5. Configura RLS (Row Level Security) nas novas tabelas

## Exemplo de Configuração

### Cenário: Seller com 2 contas PodPay

**Conta 1**: "Conta Principal" (Prioridade 1)
**Conta 2**: "Conta Backup" (Prioridade 2)

**Estratégia**: priority_only

#### Fluxo de Cashout:

1. Cliente solicita cashout de R$ 1.000
2. Sistema tenta na "Conta Principal"
3. Se retornar "saldo insuficiente":
   - Sistema automaticamente tenta na "Conta Backup"
   - Se sucesso, processa e retorna
4. Se ambas falharem, retorna erro ao cliente

## Benefícios

1. **Redundância**: Se uma conta falhar, outras assumem automaticamente
2. **Distribuição de Carga**: Evita concentrar todo o volume em uma conta
3. **Gestão de Saldo**: Distribui transações conforme saldo disponível
4. **Flexibilidade**: Cada seller pode ter configuração diferente
5. **Controle Granular**: Admin controla exatamente quais contas cada seller usa
6. **Logs Detalhados**: Sistema registra todas as tentativas e fallbacks
7. **Mesma Implementação**: Contas usam a mesma URL e payloads da adquirente

## Próximos Passos

Para implementar a UI completa, será necessário:

1. Criar interface de gerenciamento de contas no painel admin
2. Adicionar página de configuração de contas por seller
3. Dashboard com estatísticas de uso por conta
4. Alertas quando uma conta está com problemas
5. Visualização de distribuição de carga

## Código de Exemplo

### Selecionar Próxima Conta para Seller

```php
$accountModel = new AcquirerAccount();
$account = $accountModel->getNextAccountForSeller($sellerId, 'cashout');

if ($account) {
    echo "Usando conta: " . $account['name'];
    echo "Adquirente: " . $account['acquirer_code'];
}
```

### Executar com Fallback Automático

```php
$acquirerService = new AcquirerService();

$result = $acquirerService->executeWithFallback(
    $sellerId,
    'cashout',
    function($acquirer, $data) use ($acquirerService) {
        return $acquirerService->createPixCashout($acquirer, $data);
    },
    $transactionData
);
```

O sistema está pronto para uso! As rotas de API precisam ser adicionadas ao arquivo de rotas para que os endpoints funcionem.
