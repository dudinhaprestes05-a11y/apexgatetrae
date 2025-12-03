# Correção de Autenticação - Headers Authorization Bloqueados

## Problema Identificado

A autenticação estava falhando com erro 401 "Authentication is required" mesmo quando credenciais válidas eram enviadas.

**Causa Raiz:** O header `Authorization` não estava chegando ao PHP devido à configuração do servidor web (Nginx/FastCGI). O servidor estava bloqueando/removendo o header antes de passar para o PHP.

### Evidência do Problema

Os logs mostraram que apenas estes headers chegavam ao PHP:
- Cookie, Accept-Encoding, Postman-Token, Cache-Control, Accept
- User-Agent, Content-Type, Content-Length, Connection
- X-Accel-Internal, X-Real-Ip, Host

O header `Authorization` estava completamente ausente.

## Soluções Implementadas

### 1. Múltiplos Métodos de Autenticação

Agora o sistema aceita credenciais através de **4 métodos diferentes**:

#### Método 1: Authorization Header (Ideal)
```bash
# Basic Auth
Authorization: Basic base64(api_key:api_secret)

# Bearer Token
Authorization: Bearer api_key
```

#### Método 2: X-API-Key Headers (Recomendado para Nginx)
```bash
# Apenas API Key
X-API-Key: sua_api_key

# Com API Secret (autenticação completa)
X-API-Key: sua_api_key
X-API-Secret: seu_api_secret
```

#### Método 3: Query Parameters (Alternativa)
```bash
# Apenas API Key
?api_key=sua_api_key

# Com API Secret
?api_key=sua_api_key&api_secret=seu_api_secret
```

#### Método 4: X-API-Key + HMAC Signature
```bash
X-API-Key: sua_api_key
X-Signature: hmac_sha256_signature
```

### 2. Função Helper Melhorada (`helpers.php:244-283`)

A função `getAllHeadersCaseInsensitive()` agora:
- Normaliza capitalização de todos os headers
- Verifica múltiplas variáveis de ambiente: `HTTP_AUTHORIZATION`, `REDIRECT_HTTP_AUTHORIZATION`
- Funciona em Apache, Nginx, PHP-FPM, FastCGI
- Faz fallback automático para `$_SERVER`

### 3. AuthService Atualizado (`AuthService.php:87-146`)

O `AuthService` agora:
- Aceita credenciais via Authorization, X-API-Key, X-API-Secret, ou query params
- Detecta automaticamente o método de autenticação usado
- Adiciona logs detalhados em desenvolvimento
- Prioriza métodos mais seguros (headers sobre query params)

### 4. Configuração Apache (.htaccess:3-4)

Adicionadas regras para passar o Authorization header:
```apache
RewriteCond %{HTTP:Authorization} ^(.+)$
RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
```

### 5. Logs de Debug Melhorados

Em modo desenvolvimento (`APP_ENV=development`):
- Lista todos os headers disponíveis
- Mostra todas as chaves `$_SERVER`
- Indica se Authorization está presente
- Registra tentativas de autenticação

## Como Testar

### Teste 1: X-API-Key + X-API-Secret (RECOMENDADO)

```bash
curl -X POST https://seu-dominio.com/api/pix/create \
  -H "X-API-Key: sua_api_key" \
  -H "X-API-Secret: seu_api_secret" \
  -H "Content-Type: application/json" \
  -d '{
    "amount": 100.00,
    "payer_name": "João Silva",
    "payer_document": "12345678901"
  }'
```

### Teste 2: Query Parameters (Alternativa Simples)

```bash
curl -X POST "https://seu-dominio.com/api/pix/create?api_key=sua_api_key&api_secret=seu_api_secret" \
  -H "Content-Type: application/json" \
  -d '{
    "amount": 100.00,
    "payer_name": "João Silva",
    "payer_document": "12345678901"
  }'
```

### Teste 3: Authorization Header (Se Servidor Configurado)

```bash
curl -X POST https://seu-dominio.com/api/pix/create \
  -H "Authorization: Basic $(echo -n 'sua_api_key:seu_api_secret' | base64)" \
  -H "Content-Type: application/json" \
  -d '{
    "amount": 100.00,
    "payer_name": "João Silva",
    "payer_document": "12345678901"
  }'
```

### Teste 4: X-API-Key + HMAC (Máxima Segurança)

```bash
PAYLOAD='{"amount":100.00,"payer_name":"João Silva","payer_document":"12345678901"}'
SIGNATURE=$(echo -n "$PAYLOAD" | openssl dgst -sha256 -hmac "seu_api_secret" | cut -d' ' -f2)

curl -X POST https://seu-dominio.com/api/pix/create \
  -H "X-API-Key: sua_api_key" \
  -H "X-Signature: $SIGNATURE" \
  -H "Content-Type: application/json" \
  -d "$PAYLOAD"
```

## Configuração do Servidor

### Para Nginx + PHP-FPM

Adicione no seu arquivo de configuração do site:

```nginx
location ~ \.php$ {
    fastcgi_pass unix:/var/run/php/php-fpm.sock;
    fastcgi_index index.php;
    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    include fastcgi_params;

    # IMPORTANTE: Passar Authorization header
    fastcgi_param HTTP_AUTHORIZATION $http_authorization;
    fastcgi_param REDIRECT_HTTP_AUTHORIZATION $http_authorization;
}
```

Ou use headers:

```nginx
location / {
    proxy_set_header Authorization $http_authorization;
    # ... outras configurações
}
```

Depois reinicie o Nginx:
```bash
sudo nginx -t
sudo systemctl restart nginx
```

### Para Apache

O arquivo `.htaccess` já está configurado. Certifique-se de que `mod_rewrite` está ativo:

```bash
sudo a2enmod rewrite
sudo systemctl restart apache2
```

## Ordem de Prioridade

O sistema verifica credenciais nesta ordem:

1. **Authorization Header** (Basic ou Bearer)
2. **X-API-Key Header** (com ou sem X-API-Secret)
3. **Query Parameters** (?api_key=... ou ?api_key=...&api_secret=...)

## Segurança

### Recomendações

✅ **Use X-API-Key headers** - Funcionam em qualquer servidor
✅ **Adicione HMAC signature** - Para máxima segurança
✅ **Use HTTPS sempre** - Essencial para proteger credenciais
✅ **Evite query params em produção** - Credenciais ficam em logs

### Notas Importantes

⚠️ Query parameters são menos seguros porque:
- Aparecem em logs do servidor
- Aparecem no histórico do navegador
- Podem ser compartilhados acidentalmente em URLs

Use query params apenas para testes ou quando headers não funcionam.

## Resultados Esperados

✅ Autenticação funciona via X-API-Key headers
✅ Autenticação funciona via query parameters
✅ Autenticação funciona via Authorization (se servidor configurado)
✅ Logs detalhados para debug
✅ Compatibilidade total com Nginx/Apache/FastCGI

## Arquivos Modificados

1. `.htaccess` - Regras para Apache passar Authorization
2. `app/config/helpers.php` - Função melhorada `getAllHeadersCaseInsensitive()`
3. `app/services/AuthService.php` - Múltiplos métodos de autenticação + logs
4. `app/controllers/api/WebhookController.php` - Usa função case-insensitive

## Troubleshooting

### Se ainda não funcionar

1. **Verifique os logs:**
```bash
tail -f /var/log/nginx/error.log
tail -f /var/log/php-fpm/error.log
```

2. **Teste com X-API-Key primeiro:**
```bash
curl -v -H "X-API-Key: sua_chave" https://seu-dominio.com/api/pix/list
```

3. **Ative modo debug** no `.env`:
```
APP_ENV=development
```

4. **Verifique se seller está ativo:**
```sql
SELECT id, email, status FROM sellers WHERE api_key = 'sua_api_key';
```

### Logs de Debug

Os logs mostrarão:
- `available_headers`: Todos os headers recebidos
- `has_authorization`: Se Authorization está presente
- `Auth headers received`: Lista de headers
- `$_SERVER keys`: Todas as variáveis de servidor

## Próximos Passos

Se X-API-Key headers funcionarem mas Authorization não, isso significa que você precisa configurar o Nginx. Use a configuração acima na seção "Para Nginx + PHP-FPM".
