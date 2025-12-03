# Teste de Autenticação com Hash SHA256

O sistema agora está configurado para hashear o API Secret recebido com SHA256 antes de comparar com o banco de dados.

## Scripts de Debug Criados

### 1. test_auth_flow.php
Verifica se o hash no banco está correto e gera comandos curl para teste.

```bash
php test_auth_flow.php
```

### 2. debug_auth_request.php
Script interativo que testa qual API Secret você está usando.

```bash
php debug_auth_request.php
```

## Processo de Autenticação

Quando você faz uma requisição:

1. **Você envia**: `API Key` + `API Secret` (texto plano)
2. **Sistema recebe**: O API Secret em texto plano
3. **Sistema converte**: `hash('sha256', $apiSecret)`
4. **Sistema compara**: Hash gerado vs Hash no banco
5. **Resultado**: Se coincidem → autenticado ✅ / Se não → erro 401 ❌

## Credenciais de Teste Padrão

Se o banco foi criado com o schema.sql original:

- **API Key**: `sk_test_demo_key_123456789`
- **API Secret**: `demo_secret_key_987654321`

## Testando a API

### Método 1: Basic Authentication (Recomendado)

```bash
# Gere o token Base64
echo -n "sk_test_demo_key_123456789:demo_secret_key_987654321" | base64

# Use na requisição
curl -X GET http://localhost:8000/api/pix/list \
  -H "Authorization: Basic c2tfdGVzdF9kZW1vX2tleV8xMjM0NTY3ODk6ZGVtb19zZWNyZXRfa2V5Xzk4NzY1NDMyMQ==" \
  -H "Content-Type: application/json"
```

### Método 2: Headers Customizados

```bash
curl -X GET http://localhost:8000/api/pix/list \
  -H "X-API-Key: sk_test_demo_key_123456789" \
  -H "X-API-Secret: demo_secret_key_987654321" \
  -H "Content-Type: application/json"
```

## Debug de Problemas

### Erro: "Invalid credentials"

Significa que o hash não está batendo. Execute:

```bash
php debug_auth_request.php
```

E teste com o API Secret que você está usando.

### Verificar Logs

Se `APP_ENV=development`, os logs de debug aparecem em:
- `error_log` do PHP
- Ou no console do servidor web

Procure por:
```
=== CREDENTIAL COMPARISON ===
Received API Key: ...
Hashed received secret first 4 chars: ...
Stored API Secret (hash) first 4 chars: ...
Secrets match: YES/NO
```

### Regenerar Credenciais

Se nada funcionar, acesse o painel web:

1. Faça login como seller
2. Vá em "API Credentials"
3. Clique em "Regenerar Credenciais"
4. Copie o **novo API Secret** (só aparece uma vez!)
5. Use as novas credenciais

## Verificação Rápida

Execute este comando para ver o hash correto:

```bash
php -r "echo 'Hash de demo_secret_key_987654321: ' . hash('sha256', 'demo_secret_key_987654321') . PHP_EOL;"
```

Resultado esperado:
```
Hash de demo_secret_key_987654321: 8c6976e5b5410415bde908bd4dee15dfb167a9c873fc4bb8a81f6f2ab448a918
```

Compare este hash com o que está no banco de dados na coluna `api_secret` da tabela `sellers`.
