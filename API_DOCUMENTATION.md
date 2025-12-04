# Documentação da API - Gateway PIX

## Autenticação

### Método Recomendado: Basic Authentication

O método preferencial de autenticação é via HTTP Basic Auth:

```
Authorization: Basic base64(API_KEY:API_SECRET)
Content-Type: application/json
```

**Exemplos:**

**cURL:**
```bash
curl -X POST https://gateway.seudominio.com/api/pix/create \
  -u "sua_api_key:seu_api_secret" \
  -H "Content-Type: application/json" \
  -d '{"amount": 100.50}'
```

**PHP:**
```php
$apiKey = 'sua_api_key';
$apiSecret = 'seu_api_secret';
$auth = base64_encode("$apiKey:$apiSecret");

$headers = [
    'Authorization: Basic ' . $auth,
    'Content-Type: application/json'
];
```

**Python:**
```python
import requests
import base64

api_key = 'sua_api_key'
api_secret = 'seu_api_secret'
auth = base64.b64encode(f"{api_key}:{api_secret}".encode()).decode()

headers = {
    'Authorization': f'Basic {auth}',
    'Content-Type': 'application/json'
}
```

**Node.js:**
```javascript
const apiKey = 'sua_api_key';
const apiSecret = 'seu_api_secret';
const auth = Buffer.from(`${apiKey}:${apiSecret}`).toString('base64');

const headers = {
  'Authorization': `Basic ${auth}`,
  'Content-Type': 'application/json'
};
```

### Método Alternativo: Headers Customizados

Para compatibilidade com integrações antigas, também suportamos autenticação via headers customizados (requer API Key + API Secret):

**X-API-Key + X-API-Secret:**
```
X-API-Key: sua_api_key
X-API-Secret: seu_api_secret
Content-Type: application/json
```

**Exemplo cURL:**
```bash
curl -X POST https://gateway.seudominio.com/api/pix/create \
  -H "X-API-Key: sua_api_key" \
  -H "X-API-Secret: seu_api_secret" \
  -H "Content-Type: application/json" \
  -d '{"amount": 100.50}'
```

### Segurança da Autenticação

- O **API Secret** enviado na requisição é convertido para hash SHA256 antes da comparação
- Nunca armazene credenciais em texto plano no código
- Use variáveis de ambiente para armazenar API Key e API Secret
- Todas as requisições devem usar HTTPS
- O API Secret nunca é retornado nas respostas da API

## Endpoints

### Base URL
```
https://gateway.seudominio.com
```

---

## PIX Cash-in

### Criar Transação PIX

Cria uma nova transação de recebimento PIX.

**Endpoint:** `POST /api/pix/create`

**Request Body:**
```json
{
  "amount": 100.50,
  "external_id": "pedido-123",
  "pix_type": "dynamic",
  "expires_in_minutes": 30,
  "customer": {
    "name": "João Silva",
    "email": "joao@example.com",
    "document": "12345678900"
  },
  "metadata": {
    "order_id": "12345",
    "customer_id": "987"
  },
  "splits": [
    {
      "seller_id": 2,
      "percentage": 10
    },
    {
      "seller_id": 3,
      "amount": 5.00
    }
  ]
}
```

**Parâmetros:**
- `amount` (float, obrigatório): Valor da transação
- `external_id` (string, opcional): ID externo da transação no seu sistema
- `pix_type` (string, opcional): Tipo do PIX (dynamic, static, qrcode). Default: dynamic
- `expires_in_minutes` (int, opcional): Tempo de expiração em minutos. Default: 30
- `customer` (object, opcional): Dados do cliente pagador
  - `name` (string): Nome do cliente
  - `email` (string): Email do cliente
  - `document` (string): CPF ou CNPJ do cliente
- `metadata` (object, opcional): Dados adicionais
- `splits` (array, opcional): Divisão de pagamento entre sellers

**Response (200):**
```json
{
  "success": true,
  "message": "PIX transaction created successfully",
  "data": {
    "transaction_id": "CASHIN_20231201120000_a1b2c3d4e5f6",
    "external_id": "pedido-123",
    "amount": 100.50,
    "fee_amount": 0.99,
    "net_amount": 99.51,
    "qrcode": "00020126580014br.gov.bcb.pix...",
    "qrcode_base64": "data:image/png;base64,iVBORw0KGgo...",
    "expires_at": "2023-12-01 12:30:00",
    "status": "pending"
  }
}
```

**Erros:**
- `400`: Parâmetros inválidos
- `403`: Limite diário excedido ou bloqueado por antifraude
- `503`: Nenhuma adquirente disponível

---

### Consultar Transação PIX

Consulta o status e detalhes de uma transação.

**Endpoint:** `GET /api/pix/consult?transaction_id=CASHIN_xxx`

**Query Parameters:**
- `transaction_id` (string, obrigatório): ID da transação

**Response (200):**
```json
{
  "success": true,
  "data": {
    "transaction_id": "CASHIN_20231201120000_a1b2c3d4e5f6",
    "external_id": "pedido-123",
    "amount": 100.50,
    "fee_amount": 0.99,
    "net_amount": 99.51,
    "status": "paid",
    "qrcode": "00020126580014br.gov.bcb.pix...",
    "qrcode_base64": "data:image/png;base64,iVBORw0KGgo...",
    "paid_at": "2023-12-01 12:15:00",
    "expires_at": "2023-12-01 12:30:00",
    "created_at": "2023-12-01 12:00:00"
  }
}
```

**Status possíveis:**
- `pending`: Aguardando pagamento
- `processing`: Processando
- `paid`: Pago
- `expired`: Expirado
- `cancelled`: Cancelado
- `failed`: Falhou

---

### Listar Transações PIX

Lista transações do seller com filtros.

**Endpoint:** `GET /api/pix/list`

**Query Parameters:**
- `status` (string, opcional): Filtrar por status
- `start_date` (date, opcional): Data inicial (YYYY-MM-DD)
- `end_date` (date, opcional): Data final (YYYY-MM-DD)
- `limit` (int, opcional): Limite de resultados (máx 100). Default: 50

**Response (200):**
```json
{
  "success": true,
  "data": {
    "transactions": [
      {
        "transaction_id": "CASHIN_20231201120000_a1b2c3d4e5f6",
        "amount": 100.50,
        "fee_amount": 0.99,
        "net_amount": 99.51,
        "status": "paid",
        "paid_at": "2023-12-01 12:15:00",
        "created_at": "2023-12-01 12:00:00"
      }
    ]
  }
}
```

---

## PIX Cash-out

### Criar Saque/Transferência

Cria uma transação de saque PIX.

**Endpoint:** `POST /api/cashout/create`

**Request Body:**
```json
{
  "amount": 50.00,
  "external_id": "saque-456",
  "pix_key": "12345678000190",
  "pix_key_type": "cnpj",
  "beneficiary_name": "Empresa LTDA",
  "beneficiary_document": "12345678000190",
  "metadata": {
    "invoice_id": "INV-001"
  }
}
```

**Parâmetros:**
- `amount` (float, obrigatório): Valor do saque
- `external_id` (string, opcional): ID externo da transação no seu sistema
- `pix_key` (string, obrigatório): Chave PIX de destino
- `pix_key_type` (string, obrigatório): Tipo da chave (cpf, cnpj, email, phone, random)
- `beneficiary_name` (string, obrigatório): Nome do beneficiário
- `beneficiary_document` (string, obrigatório): CPF/CNPJ do beneficiário
- `metadata` (object, opcional): Dados adicionais

**Response (200):**
```json
{
  "success": true,
  "message": "Cashout transaction created successfully",
  "data": {
    "transaction_id": "CASHOUT_20231201130000_b2c3d4e5f6g7",
    "external_id": "saque-456",
    "amount": 50.00,
    "fee_amount": 0.49,
    "net_amount": 49.51,
    "pix_key": "12345678000190",
    "beneficiary_name": "Empresa LTDA",
    "status": "processing"
  }
}
```

**Erros:**
- `400`: Saldo insuficiente ou parâmetros inválidos
- `503`: Nenhuma adquirente disponível

---

### Consultar Saque

**Endpoint:** `GET /api/cashout/consult?transaction_id=CASHOUT_xxx`

**Response (200):**
```json
{
  "success": true,
  "data": {
    "transaction_id": "CASHOUT_20231201130000_b2c3d4e5f6g7",
    "external_id": "saque-456",
    "amount": 50.00,
    "fee_amount": 0.49,
    "net_amount": 49.51,
    "status": "completed",
    "pix_key": "12345678000190",
    "beneficiary_name": "Empresa LTDA",
    "processed_at": "2023-12-01 13:05:00",
    "created_at": "2023-12-01 13:00:00"
  }
}
```

**Status possíveis:**
- `pending`: Pendente
- `processing`: Processando
- `completed`: Completo
- `failed`: Falhou
- `cancelled`: Cancelado

---

### Listar Saques

**Endpoint:** `GET /api/cashout/list`

Mesmos parâmetros e formato do `/api/pix/list`

---

## Webhooks

O sistema envia notificações para a URL configurada em cada seller.

### Estrutura do Webhook

**Headers:**
```
Content-Type: application/json
X-Webhook-Secret: seu_webhook_secret
X-Transaction-Id: transaction_id
User-Agent: Gateway-PIX-Webhook/1.0
```

**Payload:**
```json
{
  "event": "cashin.paid",
  "transaction_id": "CASHIN_20231201120000_a1b2c3d4e5f6",
  "data": {
    "transaction_id": "CASHIN_20231201120000_a1b2c3d4e5f6",
    "amount": 100.50,
    "fee_amount": 0.99,
    "net_amount": 99.51,
    "status": "paid",
    "payer_name": "João Silva",
    "payer_document": "12345678900",
    "end_to_end_id": "E12345678202312011215abcde",
    "paid_at": "2023-12-01 12:15:00",
    "created_at": "2023-12-01 12:00:00"
  },
  "timestamp": "2023-12-01T12:15:00-03:00"
}
```

### Eventos Disponíveis

**Cash-in:**
- `cashin.pending`: Transação criada
- `cashin.paid`: Transação paga
- `cashin.expired`: Transação expirada
- `cashin.cancelled`: Transação cancelada
- `cashin.failed`: Transação falhou

**Cash-out:**
- `cashout.pending`: Saque criado
- `cashout.processing`: Saque em processamento
- `cashout.completed`: Saque concluído
- `cashout.failed`: Saque falhou
- `cashout.cancelled`: Saque cancelado

### Validar Webhook

Sempre valide o webhook secret recebido:

**PHP:**
```php
$payload = file_get_contents('php://input');
$receivedSecret = $_SERVER['HTTP_X_WEBHOOK_SECRET'] ?? '';
$expectedSecret = 'seu_webhook_secret_configurado';

if ($receivedSecret !== $expectedSecret) {
    http_response_code(401);
    exit('Invalid webhook secret');
}

$data = json_decode($payload, true);
// Processar dados do webhook...
```

**Node.js:**
```javascript
app.post('/webhook', (req, res) => {
    const receivedSecret = req.headers['x-webhook-secret'];
    const expectedSecret = 'seu_webhook_secret_configurado';

    if (receivedSecret !== expectedSecret) {
        return res.status(401).send('Invalid webhook secret');
    }

    const data = req.body;
    // Processar dados do webhook...
    res.status(200).send('OK');
});
```

**Python:**
```python
from flask import Flask, request

@app.route('/webhook', methods=['POST'])
def webhook():
    received_secret = request.headers.get('X-Webhook-Secret')
    expected_secret = 'seu_webhook_secret_configurado'

    if received_secret != expected_secret:
        return 'Invalid webhook secret', 401

    data = request.json
    # Processar dados do webhook...
    return 'OK', 200
```

### Retry Policy

O sistema tenta reenviar webhooks falhados automaticamente:
- 1ª tentativa: Imediata (assim que o status muda)
- 2ª tentativa: 1 minuto depois
- 3ª tentativa: 5 minutos depois
- 4ª tentativa: 15 minutos depois
- 5ª tentativa: 1 hora depois
- 6ª tentativa: 2 horas depois

**Resposta esperada:** Retorne status HTTP 2xx (200-299) para confirmar o recebimento

---

## Rate Limiting

- **Limite:** 100 requisições por minuto por API Key
- **Header de resposta:** `X-RateLimit-Remaining: X`
- **Erro 429:** Rate limit excedido

---

## Códigos de Status HTTP

- `200`: Sucesso
- `400`: Requisição inválida
- `401`: Não autorizado (API Key inválida)
- `403`: Proibido (limite excedido, bloqueado)
- `404`: Não encontrado
- `429`: Rate limit excedido
- `500`: Erro interno do servidor
- `503`: Serviço indisponível

---

## Exemplo Completo

```bash
curl -X POST https://gateway.seudominio.com/api/pix/create \
  -H "Content-Type: application/json" \
  -H "X-API-Key: sk_test_demo_key_123456789" \
  -H "X-Signature: a1b2c3d4e5f6..." \
  -d '{
    "amount": 100.50,
    "pix_type": "dynamic",
    "expires_in_minutes": 30
  }'
```

---

## Ambiente de Testes

Use as credenciais fornecidas no README.md para testes.

**Nota:** Em ambiente de testes, as transações não processam valores reais.
