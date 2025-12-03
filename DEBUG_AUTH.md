# Debug de Autenticação - Invalid Credentials

## Status Atual

✅ Headers `X-API-Key` e `X-API-Secret` agora chegam ao PHP
❌ Validação retorna "Invalid credentials" com credenciais corretas

## Mudanças Aplicadas

1. **Comparação com trim()** - `AuthService.php:87` agora remove espaços em branco
2. **Logs detalhados** - Em desenvolvimento mostra comparação byte-a-byte
3. **Scripts de teste** - Ferramentas para verificar credenciais no banco

## Como Debugar

### Passo 1: Verificar Credenciais no Banco

```bash
curl https://seu-dominio.com/test_credentials.php
```

Isso mostrará:
- Todos os sellers ativos
- Preview das API Keys e Secrets
- Comprimento das strings

### Passo 2: Testar Credenciais Específicas

```bash
curl -X POST https://seu-dominio.com/verify_auth.php \
  -H "Content-Type: application/json" \
  -d '{
    "api_key": "sua_api_key_aqui",
    "api_secret": "seu_api_secret_aqui"
  }'
```

Isso mostrará:
- Se a API Key existe no banco
- Comparação detalhada dos secrets
- Se há espaços em branco extras
- Se a validação passaria

### Passo 3: Ver Logs Detalhados

Com `APP_ENV=development` no `.env`, os logs mostrarão:

```
=== X-API-KEY AUTH DETECTED ===
X-API-Key: 12345678...
X-API-Secret present: YES
X-API-Secret length: 32
X-API-Secret first 4 chars: abcd

=== CREDENTIAL COMPARISON ===
Received API Key: 12345678...
Received API Secret length: 32
Received API Secret first 4 chars: abcd
Stored API Secret length: 32
Stored API Secret first 4 chars: abcd
Secrets match: YES/NO
Secrets match (trimmed): YES/NO
```

Verifique:
- `/var/log/php-fpm/error.log`
- `/var/log/nginx/error.log`
- Logs da aplicação

### Passo 4: Testar com Credenciais Reais

```bash
# Primeiro, pegue as credenciais
CREDS=$(curl -s https://seu-dominio.com/test_credentials.php | jq -r '.sellers[0]')
API_KEY=$(echo $CREDS | jq -r '.api_key')
API_SECRET=$(echo $CREDS | jq -r '.api_secret')

# Teste a autenticação
curl -X POST https://seu-dominio.com/api/pix/create \
  -H "X-API-Key: $API_KEY" \
  -H "X-API-Secret: $API_SECRET" \
  -H "Content-Type: application/json" \
  -d '{
    "amount": 100.00,
    "payer_name": "Teste",
    "payer_document": "12345678901"
  }'
```

## Problemas Comuns

### 1. Espaços em Branco

**Sintoma:** Secret parece correto mas falha na validação

**Solução:** Já implementada com `trim()` na linha 87

**Como verificar:**
```bash
curl -X POST https://seu-dominio.com/verify_auth.php -d '{"api_key":"...","api_secret":"..."}'
```

Veja: `has_whitespace_stored` e `has_whitespace_received`

### 2. API Secret Não Foi Salvo

**Sintoma:** `stored_secret_length: 0` ou `stored_secret_preview: ...`

**Solução:** Recriar as credenciais do seller

```sql
UPDATE sellers
SET api_secret = 'novo_secret_aqui'
WHERE api_key = 'sua_api_key';
```

### 3. Seller Inativo

**Sintoma:** Erro muda para "Seller account is not active"

**Solução:**
```sql
UPDATE sellers SET status = 'active' WHERE email = 'seu@email.com';
```

### 4. Encoding/Charset

**Sintoma:** Comprimentos diferentes mesmo visualmente iguais

**Solução:** Verificar encoding do banco

```sql
SHOW CREATE TABLE sellers;
```

Deve ser: `DEFAULT CHARSET=utf8mb4`

### 5. Headers Case Sensitivity

**Sintoma:** `X-API-Secret present: NO` mesmo enviando

**Teste:**
```bash
# Tente diferentes variações
curl -H "X-Api-Key: ..." -H "X-Api-Secret: ..."
curl -H "X-API-KEY: ..." -H "X-API-SECRET: ..."
curl -H "x-api-key: ..." -H "x-api-secret: ..."
```

A função `getAllHeadersCaseInsensitive()` normaliza mas teste todos.

## Checklist de Debug

- [ ] Verifiquei que o seller existe: `/test_credentials.php`
- [ ] Confirmei as credenciais no banco
- [ ] Testei credenciais com `/verify_auth.php`
- [ ] Verifiquei os logs em desenvolvimento
- [ ] Confirmei que seller está ativo
- [ ] Testei com as credenciais exatas do banco
- [ ] Verifiquei se não há espaços extras
- [ ] Confirmei que `APP_ENV=development` está setado

## Teste Rápido

Execute este comando (substitua as credenciais):

```bash
# 1. Liste os sellers
curl https://seu-dominio.com/test_credentials.php

# 2. Copie api_key e api_secret do resultado

# 3. Verifique as credenciais
curl -X POST https://seu-dominio.com/verify_auth.php \
  -H "Content-Type: application/json" \
  -d '{"api_key":"COLE_AQUI","api_secret":"COLE_AQUI"}'

# 4. Se success=true, teste a API real
curl -X POST https://seu-dominio.com/api/pix/create \
  -H "X-API-Key: COLE_AQUI" \
  -H "X-API-Secret: COLE_AQUI" \
  -H "Content-Type: application/json" \
  -d '{"amount":100.00,"payer_name":"Teste","payer_document":"12345678901"}'
```

## Próximos Passos

Se `/verify_auth.php` mostra `success: true` mas a API real ainda falha:

1. Verifique os logs detalhados (deve aparecer `=== X-API-KEY AUTH DETECTED ===`)
2. Confirme que não há middleware bloqueando
3. Verifique se o endpoint está usando `CheckAuth` middleware
4. Teste com query parameters como alternativa

Se `/verify_auth.php` mostra `success: false`:

1. Veja o campo `comparison` no resultado
2. Copie as credenciais EXATAMENTE como aparecem no banco
3. Evite copiar/colar que pode adicionar caracteres invisíveis
4. Considere regenerar as credenciais do seller

## Contato com Suporte

Quando reportar o problema, inclua:

1. Resultado de `/test_credentials.php`
2. Resultado de `/verify_auth.php` (mascare os últimos dígitos)
3. Logs de erro do PHP
4. Versão do PHP: `php -v`
5. Servidor: Nginx ou Apache
