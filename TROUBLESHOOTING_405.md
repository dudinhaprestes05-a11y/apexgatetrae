# Solucionando o Erro HTTP 405

## Problema Corrigido ✅

O sistema agora **NÃO cria transações no banco de dados quando o acquirer falha**. O fluxo foi ajustado para:

1. ✅ Validações iniciais (autenticação, limites, antifraude)
2. ✅ Chama o acquirer PRIMEIRO
3. ✅ Se falhar → retorna erro SEM criar nada no banco
4. ✅ Se sucesso → cria a transação no banco com todos os dados

## Causa do Erro HTTP 405

O erro `HTTP 405: Method Not Allowed` indica que o PodPay não está aceitando a requisição. Possíveis causas:

### 1. URL da API Incorreta

A URL configurada pode estar errada ou incompleta.

**Como verificar:**
```bash
php check_acquirer.php
```

Este script mostra a configuração atual de todos os acquirers e identifica problemas.

**URLs possíveis do PodPay:**
- `https://api.podpay.com.br`
- `https://podpay.com.br/api`
- `https://sandbox.podpay.com.br` (ambiente de testes)

### 2. Credenciais Não Configuradas

O acquirer precisa ter `api_key` e `api_secret` configurados.

**Verificar no banco:**
```sql
SELECT id, name, code, api_url,
       CASE WHEN api_key IS NULL OR api_key = '' THEN 'NÃO' ELSE 'SIM' END as api_key_ok,
       CASE WHEN api_secret IS NULL OR api_secret = '' THEN 'NÃO' ELSE 'SIM' END as api_secret_ok,
       status
FROM acquirers
WHERE code = 'podpay';
```

**Configurar credenciais:**
```sql
UPDATE acquirers
SET
    api_url = 'https://api.podpay.com.br',
    api_key = 'SUA_API_KEY_AQUI',
    api_secret = 'SEU_API_SECRET_AQUI',
    status = 'active'
WHERE code = 'podpay';
```

### 3. Endpoint do PodPay Mudou

A integração atual usa o endpoint `/v1/transactions` com método POST.

**Verificar com o PodPay:**
- Qual é o endpoint correto para criar transações PIX?
- O método é POST?
- Qual é a estrutura esperada do payload?

### 4. Formato do Payload

O sistema envia o seguinte payload para o PodPay:

```json
{
  "amount": 10000,
  "currency": "BRL",
  "paymentMethod": "pix",
  "items": [{
    "title": "Recebimento PIX",
    "unitPrice": 10000,
    "quantity": 1,
    "tangible": false
  }],
  "customer": {
    "name": "Nome do Cliente",
    "email": "cliente@example.com",
    "document": {
      "number": "00000000000",
      "type": "cpf"
    }
  },
  "postbackUrl": "https://seu-gateway.com/api/webhook/acquirer?acquirer=podpay"
}
```

Se o PodPay mudou a API, pode ser necessário ajustar este formato.

## Passo a Passo para Resolver

### 1. Verificar Configuração Atual

```bash
php check_acquirer.php
```

Ou acesse: `https://seu-dominio.com/check_acquirer.php`

### 2. Verificar Logs Detalhados

Acesse o painel admin: `/admin/logs`

Filtre por:
- Categoria: `podpay` ou `acquirer`
- Nível: `error`

Procure por mensagens como:
- "PodPay service initialized" → mostra a URL sendo usada
- "Sending request" → mostra os dados enviados
- "HTTP error response" → mostra a resposta do servidor

### 3. Teste Manual com cURL

```bash
curl -X POST https://api.podpay.com.br/v1/transactions \
  -u "SUA_API_KEY:SEU_API_SECRET" \
  -H "Content-Type: application/json" \
  -d '{
    "amount": 10000,
    "currency": "BRL",
    "paymentMethod": "pix",
    "customer": {
      "name": "Teste",
      "email": "teste@example.com",
      "document": {
        "number": "00000000000",
        "type": "cpf"
      }
    }
  }'
```

### 4. Comparar com Documentação do PodPay

Entre em contato com o suporte do PodPay e solicite:
- Documentação atualizada da API
- Exemplos de integração em PHP/cURL
- URL correta (produção e sandbox)
- Validação das suas credenciais

### 5. Ajustar Configuração

Com as informações corretas do PodPay, atualize no banco:

```sql
UPDATE acquirers
SET
    api_url = 'URL_CORRETA_AQUI',
    api_key = 'API_KEY_CORRETA',
    api_secret = 'API_SECRET_CORRETO'
WHERE code = 'podpay';
```

### 6. Testar Novamente

Após ajustar a configuração, teste criar uma transação:

```bash
curl -X POST https://seu-gateway.com/api/pix/create \
  -u "sua_api_key:seu_api_secret" \
  -H "Content-Type: application/json" \
  -d '{
    "amount": 10.00,
    "customer": {
      "name": "Teste",
      "email": "teste@example.com",
      "document": "00000000000"
    }
  }'
```

## Próximos Passos se o Problema Persistir

Se após verificar tudo acima o erro continuar:

1. **Verifique firewall/proxy**: Pode estar bloqueando a conexão
2. **Verifique SSL**: O PodPay pode requerer certificado específico
3. **Contate o suporte PodPay**: Envie os logs detalhados
4. **Considere usar outro acquirer**: Temporariamente enquanto resolve o PodPay

## Recursos Úteis

- Script de verificação: `check_acquirer.php`
- Script de configuração: `sql/insert_podpay_acquirer.sql`
- Guia de integração: `PODPAY_INTEGRATION.md`
- Logs do sistema: `/admin/logs`
