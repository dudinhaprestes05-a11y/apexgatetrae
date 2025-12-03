# Exemplos Completos da API - Gateway PIX

## Autenticação

### Método Recomendado: Basic Authentication

Todas as requisições devem usar HTTP Basic Auth:

```bash
Authorization: Basic base64(API_KEY:API_SECRET)
Content-Type: application/json
```

### Exemplo Completo (cURL)

```bash
curl -X POST https://gateway.seudominio.com/api/pix/create \
  -u "sk_test_demo_key_123456789:sk_secret_demo_key_987654321" \
  -H "Content-Type: application/json" \
  -d '{"amount": 100.50}'
```

### Exemplo em PHP

```php
<?php
$apiKey = 'sk_test_demo_key_123456789';
$apiSecret = 'sk_secret_demo_key_987654321';
$auth = base64_encode("$apiKey:$apiSecret");

$ch = curl_init('https://gateway.seudominio.com/api/pix/create');
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Basic ' . $auth,
    'Content-Type: application/json'
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'amount' => 100.50
]));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);
```

### Método Legado (HMAC)

Para compatibilidade, ainda suportamos autenticação via HMAC:

```bash
X-API-Key: sk_test_demo_key_123456789
X-Signature: <HMAC_SHA256>
Content-Type: application/json
```

Gerando assinatura HMAC:

```php
<?php
$apiSecret = 'YOUR_API_SECRET';
$payload = json_encode($data);
$signature = hash_hmac('sha256', $payload, $apiSecret);
```

## 1. Criar PIX (Cash-in)

### Request

```bash
curl -X POST https://gateway.seudominio.com/api/pix/create \
  -u "sk_test_demo_key_123456789:sk_secret_demo_key_987654321" \
  -H "Content-Type: application/json" \
  -d '{
    "external_id": "ORDER_12345",
    "amount": 100.50,
    "customer": {
      "name": "João Silva",
      "document": "12345678900",
      "email": "joao@example.com"
    }
  }'
```

### Response (Success)

```json
{
  "success": true,
  "message": "PIX transaction created successfully",
  "data": {
    "pix_id": 1,
    "transaction_id": "CASHIN_20251128120000_abc123",
    "external_id": "ORDER_12345",
    "qrcode": "00020126580014br.gov.bcb.pix...",
    "payload": "00020126580014br.gov.bcb.pix...",
    "qrcode_base64": "data:image/png;base64,iVBORw0KGgo...",
    "amount": 100.50,
    "fee_amount": 0.99,
    "net_amount": 99.51,
    "expires_at": "2025-11-28 12:30:00",
    "status": "waiting_payment"
  }
}
```

### Response (Error - Limite Excedido)

```json
{
  "success": false,
  "error": {
    "code": 403,
    "message": "Daily limit exceeded"
  }
}
```

### Response (Error - Bloqueado por Antifraude)

```json
{
  "success": false,
  "error": {
    "code": 403,
    "message": "Transaction blocked by security policies",
    "details": {
      "fraud_analysis": {
        "approved": false,
        "score": 120,
        "level": "high",
        "risks": [
          "Amount exceeds maximum allowed per transaction",
          "Too many transactions in the last hour"
        ]
      }
    }
  }
}
```

## 2. Consultar PIX

### Request

```bash
curl -X GET "https://gateway.seudominio.com/api/pix/consult?pix_id=1" \
  -H "X-API-Key: sk_test_demo_key_123456789"
```

### Response

```json
{
  "success": true,
  "data": {
    "pix_id": 1,
    "transaction_id": "CASHIN_20251128120000_abc123",
    "external_id": "ORDER_12345",
    "amount": 100.50,
    "fee_amount": 0.99,
    "net_amount": 99.51,
    "status": "paid",
    "qrcode": "00020126580014br.gov.bcb.pix...",
    "payload": "00020126580014br.gov.bcb.pix...",
    "paid_at": "2025-11-28 12:15:00",
    "expires_at": "2025-11-28 12:30:00",
    "created_at": "2025-11-28 12:00:00"
  }
}
```

## 3. Listar PIX

### Request (Todos)

```bash
curl -X GET "https://gateway.seudominio.com/api/pix/list?limit=10" \
  -H "X-API-Key: sk_test_demo_key_123456789"
```

### Request (Filtrado)

```bash
curl -X GET "https://gateway.seudominio.com/api/pix/list?status=paid&start_date=2025-11-01&end_date=2025-11-30&limit=50" \
  -H "X-API-Key: sk_test_demo_key_123456789"
```

### Response

```json
{
  "success": true,
  "data": {
    "transactions": [
      {
        "pix_id": 1,
        "transaction_id": "CASHIN_20251128120000_abc123",
        "external_id": "ORDER_12345",
        "amount": 100.50,
        "fee_amount": 0.99,
        "net_amount": 99.51,
        "status": "paid",
        "paid_at": "2025-11-28 12:15:00",
        "created_at": "2025-11-28 12:00:00"
      },
      {
        "pix_id": 2,
        "transaction_id": "CASHIN_20251128130000_def456",
        "external_id": "ORDER_12346",
        "amount": 50.00,
        "fee_amount": 0.49,
        "net_amount": 49.51,
        "status": "waiting_payment",
        "paid_at": null,
        "created_at": "2025-11-28 13:00:00"
      }
    ]
  }
}
```

## 4. Criar Cash-out

### Request (Chave PIX Normal)

```bash
curl -X POST https://gateway.seudominio.com/api/cashout/create \
  -u "sk_test_demo_key_123456789:sk_secret_demo_key_987654321" \
  -H "Content-Type: application/json" \
  -d '{
    "external_id": "PAYOUT_789",
    "amount": 500.00,
    "pix_key": "12345678000190",
    "pix_key_type": "cnpj"
  }'
```

### Request (Copia e Cola Completo)

```bash
curl -X POST https://gateway.seudominio.com/api/cashout/create \
  -u "sk_test_demo_key_123456789:sk_secret_demo_key_987654321" \
  -H "Content-Type: application/json" \
  -d '{
    "external_id": "PAYOUT_790",
    "amount": 1000.00,
    "pix_key": "00020126580014br.gov.bcb.pix0136123e4567-e12b-12d1-a456-426655440000...",
    "pix_key_type": "copypaste"
  }'
```

### Response (Success)

```json
{
  "success": true,
  "message": "Cashout transaction created successfully",
  "data": {
    "cashout_id": 1,
    "transaction_id": "CASHOUT_20251128140000_ghi789",
    "external_id": "PAYOUT_789",
    "amount": 500.00,
    "fee_amount": 4.95,
    "net_amount": 495.05,
    "pix_key": "12345678000190",
    "status": "processing"
  }
}
```

### Response (Error - Saldo Insuficiente)

```json
{
  "success": false,
  "error": {
    "code": 400,
    "message": "Insufficient balance"
  }
}
```

## 5. Consultar Cash-out

### Request

```bash
curl -X GET "https://gateway.seudominio.com/api/cashout/consult?cashout_id=1" \
  -H "X-API-Key: sk_test_demo_key_123456789"
```

### Response

```json
{
  "success": true,
  "data": {
    "cashout_id": 1,
    "transaction_id": "CASHOUT_20251128140000_ghi789",
    "external_id": "PAYOUT_789",
    "amount": 500.00,
    "fee_amount": 4.95,
    "net_amount": 495.05,
    "status": "completed",
    "pix_key": "12345678000190",
    "processed_at": "2025-11-28 14:05:00",
    "created_at": "2025-11-28 14:00:00"
  }
}
```

## 6. Webhooks Recebidos

### Webhook - PIX Pago

```json
POST https://seller.com/webhook
Content-Type: application/json
X-Signature: abc123...
X-Transaction-Id: CASHIN_20251128120000_abc123

{
  "type": "pix.cashin",
  "pix_id": 1,
  "external_id": "ORDER_12345",
  "status": "paid",
  "amount": 100.50,
  "paid_at": "2025-11-28T12:15:00-03:00"
}
```

### Webhook - PIX Expirado

```json
{
  "type": "pix.cashin",
  "pix_id": 2,
  "external_id": "ORDER_12346",
  "status": "expired",
  "amount": 50.00,
  "paid_at": null
}
```

### Webhook - Cash-out Completado

```json
{
  "type": "pix.cashout",
  "cashout_id": 1,
  "external_id": "PAYOUT_789",
  "status": "completed",
  "net_amount": 495.05,
  "fee": 4.95
}
```

### Webhook - Cash-out Falhado

```json
{
  "type": "pix.cashout",
  "cashout_id": 2,
  "external_id": "PAYOUT_790",
  "status": "failed",
  "net_amount": 0,
  "fee": 0
}
```

### Validando Webhook

```php
<?php
$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_SIGNATURE'];
$webhookSecret = 'YOUR_WEBHOOK_SECRET';

$expectedSignature = hash_hmac('sha256', $payload, $webhookSecret);

if (!hash_equals($expectedSignature, $signature)) {
    http_response_code(401);
    exit('Invalid signature');
}

$data = json_decode($payload, true);

if ($data['type'] === 'pix.cashin') {
    if ($data['status'] === 'paid') {
        echo "PIX #{$data['pix_id']} foi pago!";
    }
}

http_response_code(200);
echo 'OK';
```

## 7. Status Possíveis

### Cash-in (PIX)
- `waiting_payment` - Aguardando pagamento
- `pending` - Processando
- `paid` - Pago e confirmado
- `expired` - Expirado (não pago a tempo)
- `cancelled` - Cancelado
- `failed` - Falhou

### Cash-out (Transferência)
- `pending` - Pendente
- `processing` - Em processamento
- `completed` - Concluído com sucesso
- `failed` - Falhou
- `cancelled` - Cancelado

## 8. Códigos HTTP

- `200` - Sucesso
- `400` - Requisição inválida (bad request)
- `401` - Não autorizado (API Key inválida)
- `403` - Proibido (limite excedido, bloqueado)
- `404` - Não encontrado
- `429` - Rate limit excedido
- `500` - Erro interno do servidor
- `503` - Serviço indisponível

## 9. Rate Limiting

Limite padrão: **100 requisições por minuto**

```json
{
  "success": false,
  "error": {
    "code": 429,
    "message": "Rate limit exceeded. Try again later."
  }
}
```

## 10. Exemplo Completo em PHP

```php
<?php

class GatewayPIXClient {
    private $apiKey;
    private $apiSecret;
    private $baseUrl;

    public function __construct($apiKey, $apiSecret, $baseUrl) {
        $this->apiKey = $apiKey;
        $this->apiSecret = $apiSecret;
        $this->baseUrl = rtrim($baseUrl, '/');
    }

    private function generateSignature($payload) {
        return hash_hmac('sha256', $payload, $this->apiSecret);
    }

    private function request($endpoint, $method = 'GET', $data = null) {
        $url = $this->baseUrl . $endpoint;

        $headers = [
            'X-API-Key: ' . $this->apiKey,
            'Content-Type: application/json'
        ];

        $ch = curl_init($url);

        if ($method === 'POST' && $data) {
            $payload = json_encode($data);
            $signature = $this->generateSignature($payload);

            $headers[] = 'X-Signature: ' . $signature;

            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        return [
            'status' => $httpCode,
            'data' => json_decode($response, true)
        ];
    }

    public function createPix($externalId, $amount, $customer) {
        return $this->request('/api/pix/create', 'POST', [
            'external_id' => $externalId,
            'amount' => $amount,
            'customer' => $customer
        ]);
    }

    public function consultPix($pixId) {
        return $this->request("/api/pix/consult?pix_id={$pixId}", 'GET');
    }

    public function createCashout($externalId, $amount, $pixKey, $pixKeyType) {
        return $this->request('/api/cashout/create', 'POST', [
            'external_id' => $externalId,
            'amount' => $amount,
            'pix_key' => $pixKey,
            'pix_key_type' => $pixKeyType
        ]);
    }
}

$client = new GatewayPIXClient(
    'sk_test_demo_key_123456789',
    'YOUR_API_SECRET',
    'https://gateway.seudominio.com'
);

$result = $client->createPix('ORDER_001', 100.50, [
    'name' => 'João Silva',
    'document' => '12345678900',
    'email' => 'joao@example.com'
]);

if ($result['data']['success']) {
    echo "PIX criado! QR Code: " . $result['data']['data']['qrcode'];
} else {
    echo "Erro: " . $result['data']['error']['message'];
}
```

## 11. Testando com cURL

```bash
#!/bin/bash

API_KEY="sk_test_demo_key_123456789"
API_SECRET="YOUR_API_SECRET"
BASE_URL="https://gateway.seudominio.com"

PAYLOAD='{"external_id":"TEST001","amount":10.00,"customer":{"name":"Teste","document":"12345678900","email":"teste@test.com"}}'

SIGNATURE=$(echo -n "$PAYLOAD" | openssl dgst -sha256 -hmac "$API_SECRET" | sed 's/^.* //')

curl -X POST "$BASE_URL/api/pix/create" \
  -H "Content-Type: application/json" \
  -H "X-API-Key: $API_KEY" \
  -H "X-Signature: $SIGNATURE" \
  -d "$PAYLOAD"
```

## 12. Ambiente de Testes

**Base URL:** `http://localhost` ou seu domínio de teste

**Credenciais Demo:**
- API Key: `sk_test_demo_key_123456789`
- API Secret: (veja no banco de dados na tabela `sellers`)

**Seller Demo:**
- Email: `seller@demo.com`
- Saldo inicial: R$ 0,00
- Limite diário: R$ 50.000,00

## 13. Suporte

Para problemas ou dúvidas:
1. Verifique os logs em `/logs/`
2. Consulte `API_DOCUMENTATION.md`
3. Revise `INTEGRACAO_PODPAY.md`
