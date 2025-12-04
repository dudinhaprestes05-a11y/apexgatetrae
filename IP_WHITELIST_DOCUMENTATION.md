# Documentação do Sistema de Whitelist de IPs

## ⚠️ AVISO IMPORTANTE

**A whitelist de IPs está ATIVA POR PADRÃO e BLOQUEIA TODOS os acessos quando vazia!**

Ao criar um novo seller ou após aplicar a migração:
- ✅ A whitelist estará ATIVA
- ✅ A lista de IPs estará VAZIA
- ⚠️ **TODOS os acessos à API serão BLOQUEADOS**
- 🔧 **Ação necessária**: Adicione IPs autorizados OU desative a whitelist para permitir acesso

## Visão Geral

O sistema de whitelist de IPs foi implementado para adicionar uma camada extra de segurança ao acesso da API. A whitelist está **ATIVA POR PADRÃO** para todos os sellers.

## Comportamento do Sistema

### Estado Padrão (Whitelist Ativa e Vazia)
- **Whitelist Ativa**: `ip_whitelist_enabled = 1` (padrão)
- **Whitelist Vazia**: `ip_whitelist = []`
- **Resultado**: TODOS os IPs são BLOQUEADOS ⚠️

### Quando IPs São Adicionados
- **Whitelist Ativa**: `ip_whitelist_enabled = 1`
- **Whitelist com IPs**: `ip_whitelist = [{"ip": "192.168.1.1", ...}, ...]`
- **Resultado**: Apenas os IPs cadastrados são permitidos

### Whitelist Desativada
- **Whitelist Desativada**: `ip_whitelist_enabled = 0`
- **Resultado**: Todos os IPs são permitidos (independente da lista)

## Fluxo de Segurança

```
Requisição API
    ↓
Validar Credenciais (API Key + Secret)
    ↓
Whitelist Ativa?
    ├─ NÃO → Permitir Acesso
    └─ SIM → Whitelist Vazia?
            ├─ SIM → Bloquear (403) ⚠️
            └─ NÃO → IP na Lista?
                    ├─ SIM → Permitir Acesso
                    └─ NÃO → Bloquear (403)
```

## Funcionalidades

### 1. Gerenciamento de IPs
- Adicionar até 50 endereços IP
- Suporte para IPs individuais: `192.168.1.1`
- Suporte para ranges CIDR: `192.168.1.0/24`
- Descrição opcional para cada IP
- Remoção individual de IPs

### 2. Ativação/Desativação
- Toggle simples na interface
- Pode ser desativada a qualquer momento
- Sellers podem optar por não usar a whitelist

### 3. Validação Automática
- Validação em todas as requisições da API
- Não afeta o acesso ao painel web
- Logs de tentativas bloqueadas
- Mensagem de erro clara: "Access denied: IP address not authorized"

## Implementação Técnica

### Banco de Dados

```sql
ALTER TABLE sellers
ADD COLUMN ip_whitelist TEXT DEFAULT '[]',
ADD COLUMN ip_whitelist_enabled TINYINT(1) DEFAULT 1;

CREATE INDEX idx_sellers_ip_whitelist_enabled
ON sellers(ip_whitelist_enabled);
```

### Estrutura JSON da Whitelist

```json
[
  {
    "ip": "192.168.1.1",
    "description": "Servidor de produção",
    "added_at": "2024-12-04 10:30:00"
  },
  {
    "ip": "10.0.0.0/24",
    "description": "Rede interna",
    "added_at": "2024-12-04 11:45:00"
  }
]
```

### Métodos Principais

#### Seller Model

```php
// Obter lista de IPs
$whitelist = $sellerModel->getIpWhitelist($sellerId);

// Adicionar IP
$result = $sellerModel->addIpToWhitelist($sellerId, '192.168.1.1', 'Descrição');

// Remover IP
$result = $sellerModel->removeIpFromWhitelist($sellerId, '192.168.1.1');

// Ativar/Desativar
$result = $sellerModel->toggleIpWhitelist($sellerId, true);

// Validar IP
$isAllowed = $sellerModel->isIpWhitelisted($sellerId, $clientIp);
```

#### AuthService

```php
// Validação automática durante autenticação
private function authenticateBasicAuth($apiKey, $apiSecret) {
    // ... validação de credenciais ...

    $this->validateIpWhitelist($seller);

    return $seller;
}
```

## Rotas da API Web

### Interface de Gerenciamento
- `GET /seller/ip-whitelist` - Página de gerenciamento

### Endpoints JSON
- `GET /seller/ip-whitelist/get` - Listar IPs (JSON)
- `POST /seller/ip-whitelist/add` - Adicionar IP
- `POST /seller/ip-whitelist/remove` - Remover IP
- `POST /seller/ip-whitelist/toggle` - Ativar/Desativar

### Exemplos de Requisições

#### Adicionar IP
```javascript
fetch('/seller/ip-whitelist/add', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    ip: '192.168.1.1',
    description: 'Servidor de produção'
  })
});
```

#### Remover IP
```javascript
fetch('/seller/ip-whitelist/remove', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({ ip: '192.168.1.1' })
});
```

#### Toggle Whitelist
```javascript
fetch('/seller/ip-whitelist/toggle', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({ enabled: true })
});
```

## Casos de Uso

### Caso 1: Seller Novo (Padrão)
1. Seller é criado
2. Whitelist está ATIVA e VAZIA
3. **TODOS os acessos à API são BLOQUEADOS** ⚠️
4. Seller precisa ADICIONAR IPs ou DESATIVAR a whitelist para permitir acesso

### Caso 2: Restringir a IPs Específicos
1. Seller acessa `/seller/ip-whitelist`
2. Adiciona IPs permitidos (ex: servidor de produção)
3. Apenas requisições desses IPs são aceitas
4. Tentativas de outros IPs são bloqueadas

### Caso 3: Desativar Proteção
1. Seller acessa `/seller/ip-whitelist`
2. Desativa o toggle da whitelist
3. Todos os IPs são permitidos novamente
4. Lista de IPs é mantida para reativação futura

## Segurança

### Validações Implementadas
- ✅ Formato de IP válido (IPv4)
- ✅ Formato CIDR válido (ex: /24, /16)
- ✅ Máximo de 50 IPs por seller
- ✅ IPs duplicados não são permitidos
- ✅ Logs de tentativas bloqueadas

### Proteções
- ✅ Validação antes da autenticação
- ✅ Não afeta acesso ao painel web
- ✅ Mensagens de erro genéricas (não expõem detalhes)
- ✅ Index no banco para performance

### Limitações
- ⚠️ Apenas IPv4 (IPv6 não suportado)
- ⚠️ Não valida proxies/CDNs automaticamente
- ⚠️ Seller deve gerenciar IPs manualmente

## Interface do Usuário

### Componentes
1. **Card de Status**: Toggle para ativar/desativar
2. **Formulário de Adição**: IP + Descrição opcional
3. **Lista de IPs**: Mostra todos os IPs cadastrados com opção de remover
4. **Informações**: Guia com regras e dicas

### Acessos
- Menu lateral: "Whitelist de IPs"
- Página de credenciais: Link "Gerenciar Whitelist de IPs"

## Migração e Instalação

### Aplicar Migração

Opção 1 - SQL Direto:
```bash
mysql -u usuario -p database < sql/add_ip_whitelist.sql
```

Opção 2 - Script PHP:
```bash
php apply_ip_whitelist_migration.php
```

### Verificar Instalação
```sql
SHOW COLUMNS FROM sellers LIKE 'ip_whitelist%';
```

Deve retornar:
- `ip_whitelist` (TEXT)
- `ip_whitelist_enabled` (TINYINT - DEFAULT 1)

## Logs

Tentativas bloqueadas são registradas com:
- Tipo: `auth`
- Mensagem: `IP not whitelisted`
- Dados: `seller_id`, `ip`, `whitelist_enabled`

```php
$this->logModel->warning('auth', 'IP not whitelisted', [
    'seller_id' => $seller['id'],
    'ip' => $clientIp,
    'whitelist_enabled' => $seller['ip_whitelist_enabled']
]);
```

## Perguntas Frequentes

**P: A whitelist afeta o acesso ao painel web?**
R: Não, apenas requisições à API são validadas.

**P: O que acontece se a whitelist estiver ativa mas vazia?**
R: TODOS os IPs são BLOQUEADOS. Você precisa adicionar IPs ou desativar a whitelist para permitir acesso à API.

**P: Posso usar ranges CIDR?**
R: Sim, formatos como 192.168.1.0/24 são suportados.

**P: Quantos IPs posso adicionar?**
R: Máximo de 50 IPs por seller.

**P: Como desativar a whitelist?**
R: Use o toggle na página de gerenciamento ou desative via API.

**P: A lista de IPs é mantida ao desativar?**
R: Sim, você pode reativar a whitelist e os IPs continuarão lá.
