# Velana Integration Documentation

## Overview

This document describes the complete integration with Velana payment acquirer, including cash-in (PIX receiving), cash-out (PIX transfers), webhook processing with security validation, and multi-account support.

## Table of Contents

1. [Authentication](#authentication)
2. [API Endpoints](#api-endpoints)
3. [Cash-In (PIX Receiving)](#cash-in-pix-receiving)
4. [Cash-Out (PIX Transfers)](#cash-out-pix-transfers)
5. [Transaction Consultation](#transaction-consultation)
6. [Webhook Security Validation](#webhook-security-validation)
7. [Status Mapping](#status-mapping)
8. [Multi-Account System](#multi-account-system)
9. [Configuration](#configuration)
10. [Error Handling](#error-handling)
11. [Differences from PodPay](#differences-from-podpay)

---

## Authentication

### Format
Velana uses HTTP Basic Authentication with a specific format:

```
Authorization: Basic base64(secret_key:x)
```

**Important:** The literal string `:x` must be appended to your secret key before base64 encoding.

### Example
If your secret key is `sk_test_abc123`:
```php
$secretKey = 'sk_test_abc123';
$authToken = base64_encode($secretKey . ':x');
// Authorization: Basic c2tfdGVzdF9hYmMxMjM6eA==
```

### Storage
- Store the secret key in the `client_id` field of `acquirer_accounts` table
- The `client_secret` field can be NULL or contain a dummy value (not used by Velana)
- The `merchant_id` field should be NULL (not used by Velana)

---

## API Endpoints

### Base URL
```
https://api.velana.com.br
```

### Available Endpoints
- **POST** `/v1/transactions` - Create PIX transaction (cash-in)
- **GET** `/v1/transactions/{id}` - Consult transaction
- **POST** `/v1/transfers` - Create PIX transfer (cash-out)
- **GET** `/v1/transfers/{id}` - Consult transfer

---

## Cash-In (PIX Receiving)

### Request

**Endpoint:** `POST /v1/transactions`

**Headers:**
```
Content-Type: application/json
Accept: application/json
Authorization: Basic {base64(secret_key:x)}
```

**Payload:**
```json
{
  "amount": 60000,
  "currency": "BRL",
  "paymentMethod": "pix",
  "items": [
    {
      "title": "Recebimento",
      "unitPrice": 60000,
      "quantity": 1,
      "tangible": false
    }
  ],
  "customer": {
    "name": "Nome do Cliente",
    "email": "cliente@example.com",
    "document": {
      "number": "12345678900",
      "type": "cpf"
    }
  },
  "postbackUrl": "https://your-domain.com/api/webhook/acquirer?acquirer=velana"
}
```

**Important Notes:**
- `amount` is in **cents** (600.00 BRL = 60000 cents)
- Document type is automatically determined: 11 digits = cpf, 14 digits = cnpj
- Email must be valid; system generates fake Gmail address if invalid
- Total `amount` must equal `unitPrice × quantity`

### Response

```json
{
  "id": 123454623,
  "amount": 60000,
  "paidAmount": 60000,
  "refundedAmount": 0,
  "companyId": 96446,
  "installments": 1,
  "paymentMethod": "pix",
  "status": "waiting_payment",
  "postbackUrl": "https://your-domain.com/api/webhook/acquirer?acquirer=velana",
  "secureId": "3d1ca9e2-3108-4bdf-9b8c-b5a5ab178068",
  "secureUrl": "https://link.velana.com.br/pagar/3d1ca9e2-3108-4bdf-9b8c-b5a5ab178068",
  "createdAt": "2025-12-08T14:50:28.000Z",
  "paidAt": null,
  "customer": {
    "name": "Nome do Cliente",
    "email": "cliente@example.com",
    "document": {
      "number": "12345678900",
      "type": "cpf"
    }
  },
  "pix": {
    "qrcode": "00020126820014br.gov.bcb.pix...",
    "expirationDate": "2025-12-11",
    "end2EndId": null
  },
  "fee": {
    "fixedAmount": 65,
    "spreadPercentage": 0,
    "estimatedFee": 65,
    "netAmount": 59935
  }
}
```

**Key Fields:**
- `id` (numeric): Velana's transaction ID - store as `acquirer_transaction_id`
- `secureId` (UUID): Alternative identifier
- `pix.qrcode`: PIX copy-paste code for payment
- `pix.expirationDate`: Payment expiration date
- `status`: Current transaction status
- `fee.netAmount`: Amount after fees (in cents)

---

## Cash-Out (PIX Transfers)

### Request

**Endpoint:** `POST /v1/transfers`

**Headers:**
```
Content-Type: application/json
Accept: application/json
Authorization: Basic {base64(secret_key:x)}
```

**Payload:**
```json
{
  "method": "pix",
  "amount": 60000,
  "pixKey": "user@example.com",
  "pixKeyType": "email",
  "postbackUrl": "https://your-domain.com/api/webhook/acquirer?acquirer=velana"
}
```

**PIX Key Types:**
- `cpf` - CPF (11 digits, numbers only)
- `cnpj` - CNPJ (14 digits, numbers only)
- `email` - Email address
- `phone` - Phone number (+55...)
- `evp` - Random key (UUID format)

**Important Notes:**
- `amount` is in **cents**
- PIX key must match the specified type
- No need to specify beneficiary name/document (Velana retrieves from DICT)

### Response

```json
{
  "id": 789456123,
  "amount": 60000,
  "method": "pix",
  "status": "in_analysis",
  "pixKey": "user@example.com",
  "pixKeyType": "email",
  "createdAt": "2025-12-08T15:00:00.000Z"
}
```

**Key Fields:**
- `id` (numeric): Transfer ID - store as `acquirer_transaction_id`
- `status`: Transfer status (see status mapping below)

---

## Transaction Consultation

### Consult Cash-In Transaction

**Endpoint:** `GET /v1/transactions/{id}`

**Example:**
```bash
curl --request GET \
  --url https://api.velana.com.br/v1/transactions/123454623 \
  --header 'accept: application/json' \
  --header 'authorization: Basic base64(secret_key:x)'
```

**Response:**
```json
{
  "id": 123454623,
  "amount": 60000,
  "paidAmount": 60000,
  "status": "paid",
  "secureId": "3d1ca9e2-3108-4bdf-9b8c-b5a5ab178068",
  "paidAt": "2025-12-08T14:53:36.000Z",
  "pix": {
    "qrcode": "00020126820014br.gov.bcb.pix...",
    "end2EndId": "E4524641020251208145372NRPSmtKOx"
  }
}
```

### Consult Cash-Out Transfer

**Endpoint:** `GET /v1/transfers/{id}`

**Example:**
```bash
curl --request GET \
  --url https://api.velana.com.br/v1/transfers/789456123 \
  --header 'accept: application/json' \
  --header 'authorization: Basic base64(secret_key:x)'
```

**Response:**
```json
{
  "id": 789456123,
  "amount": 60000,
  "status": "success",
  "receiptUrl": "https://velana.com.br/receipt/xyz",
  "completedAt": "2025-12-08T15:05:00.000Z"
}
```

---

## Webhook Security Validation

### Overview

The system implements **mandatory webhook validation** by consulting Velana's API before processing any status change. This prevents fraudulent webhooks and ensures data integrity.

### Validation Flow

1. **Receive Webhook** from Velana
2. **Parse Webhook Data** using `VelanaService::parseWebhook()`
3. **Find Transaction** in database using `acquirer_transaction_id`
4. **Consult Velana API** to verify the webhook data:
   - For cash-in: `GET /v1/transactions/{id}`
   - For cash-out: `GET /v1/transfers/{id}`
5. **Compare Data:**
   - Status must match (after mapping)
   - Amount must match (tolerance: 0.01 BRL)
6. **Process or Reject:**
   - ✅ If validation passes: Update transaction and process
   - ❌ If validation fails: Log security warning and reject webhook

### Implementation

The validation is implemented in `WebhookController`:

```php
// For cash-in transactions
private function verifyWebhookWithAcquirer($transaction, $webhookData) {
    // Retrieves account credentials
    // Consults Velana API
    // Compares status and amount
    // Returns true/false
}

// For cash-out transactions
private function verifyCashoutWebhookWithAcquirer($transaction, $webhookData) {
    // Same process for transfers
    // Also extracts receiptUrl if available
}
```

### Security Benefits

- ✅ Prevents webhook spoofing/manipulation
- ✅ Validates status changes directly from source
- ✅ Detects discrepancies in payment amounts
- ✅ Logs all validation attempts for audit
- ✅ Uses the same account credentials that created the transaction

### Webhook Format

**Cash-In Webhook:**
```json
{
  "type": "transaction",
  "data": {
    "id": 123454623,
    "amount": 60000,
    "paidAmount": 60000,
    "status": "paid",
    "secureId": "3d1ca9e2-3108-4bdf-9b8c-b5a5ab178068",
    "pix": {
      "end2EndId": "E4524641020251208145372NRPSmtKOx",
      "qrcode": "00020126820014br.gov.bcb.pix..."
    },
    "paidAt": "2025-12-08T14:53:36.000Z"
  }
}
```

**Cash-Out Webhook:**
```json
{
  "type": "transfer",
  "data": {
    "id": 789456123,
    "amount": 60000,
    "status": "success",
    "receiptUrl": "https://velana.com.br/receipt/xyz",
    "completedAt": "2025-12-08T15:05:00.000Z"
  }
}
```

---

## Status Mapping

### Cash-In Status

| Velana Status | Internal Status | Description |
|--------------|-----------------|-------------|
| `waiting_payment` | `waiting_payment` | Waiting for customer payment |
| `paid` | `paid` | Payment confirmed |
| `refused` | `failed` | Payment refused |
| `cancelled` | `cancelled` | Transaction cancelled |
| `expired` | `expired` | Payment deadline expired |

### Cash-Out Status

| Velana Status | Internal Status | Description |
|--------------|-----------------|-------------|
| `in_analysis` | `processing` | Transfer under analysis |
| `pending` | `processing` | Transfer pending |
| `processing` | `processing` | Transfer being processed |
| `success` | `completed` | Transfer completed successfully |
| `failed` | `failed` | Transfer failed |
| `cancelled` | `cancelled` | Transfer cancelled |

---

## Multi-Account System

### Overview

The system supports multiple Velana accounts for:
- **Load balancing** across different accounts
- **Redundancy** and automatic fallback
- **Daily limit management**
- **Seller-specific accounts**

### Account Configuration

Each Velana account in `acquirer_accounts` table has:

- `client_id`: Velana secret key
- `priority`: Lower number = higher priority
- `daily_limit`: Maximum daily transaction volume
- `daily_used`: Tracks usage (resets daily)
- `status`: active/inactive/maintenance

### Selection Logic

1. **Check seller-specific accounts** (if configured)
2. **Sort by priority** (ascending)
3. **Check availability:**
   - Status must be 'active'
   - Daily limit not exceeded
4. **Execute transaction**
5. **Automatic fallback** on failure

### Fallback Scenarios

The system retries with next account if:
- ❌ Insufficient balance
- ❌ Daily limit exceeded
- ❌ Timeout/connection error
- ❌ Service unavailable

### Example Setup

```sql
-- High priority account (main)
INSERT INTO acquirer_accounts VALUES (
  ..., 'Velana Main', 'sk_live_main', NULL, NULL, NULL, 1, 'active', 50000.00, ...
);

-- Lower priority (backup)
INSERT INTO acquirer_accounts VALUES (
  ..., 'Velana Backup', 'sk_live_backup', NULL, NULL, NULL, 2, 'active', 30000.00, ...
);
```

---

## Configuration

### Step 1: Run Migration

```bash
mysql -u username -p database_name < sql/add_velana_support.sql
```

This creates:
- Multi-account tables (if not exist)
- Velana acquirer registration
- Necessary indexes and foreign keys

### Step 2: Configure Accounts

Edit `sql/configure_velana.sql` with your credentials:

```sql
INSERT INTO acquirer_accounts (
    acquirer_id,
    name,
    client_id,  -- Your Velana secret key here
    ...
) VALUES (
    (SELECT id FROM acquirers WHERE code = 'velana'),
    'Velana - Conta Principal',
    'YOUR_ACTUAL_SECRET_KEY',  -- ⚠️ Replace this
    NULL,
    NULL,
    NULL,
    1,
    'active',
    50000.00,
    0.00,
    CURDATE()
);
```

Then run:
```bash
mysql -u username -p database_name < sql/configure_velana.sql
```

### Step 3: Verify Configuration

```sql
-- Check Velana acquirer
SELECT * FROM acquirers WHERE code = 'velana';

-- Check configured accounts
SELECT acc.*, acq.name as acquirer_name
FROM acquirer_accounts acc
JOIN acquirers acq ON acc.acquirer_id = acq.id
WHERE acq.code = 'velana';
```

### Step 4: Test Integration

Create a test cash-in transaction through your API and verify:
1. Transaction created successfully
2. QR code generated
3. Webhook received and validated
4. Status updated correctly

---

## Error Handling

### Common Errors

| HTTP Code | Error | Solution |
|-----------|-------|----------|
| 400 | Invalid amount | Check amount is positive and in cents |
| 401 | Authentication failed | Verify secret key format (secret_key:x) |
| 404 | Transaction not found | Check transaction ID is correct |
| 422 | Invalid document | Validate CPF/CNPJ format (numbers only) |
| 422 | Invalid email | Provide valid email or let system generate |
| 422 | Invalid PIX key | Match key format with keyType |
| 429 | Rate limit exceeded | Implement retry with exponential backoff |
| 500 | Internal server error | Check Velana status page, retry later |

### Retry Strategy

For retryable errors (timeout, 500, 503):
```php
$maxRetries = 3;
$retryDelay = 1000; // milliseconds

for ($i = 0; $i < $maxRetries; $i++) {
    $result = $velana->createTransaction($data);
    if ($result['success']) break;

    if ($this->isRetryable($result['error'])) {
        usleep($retryDelay * 1000);
        $retryDelay *= 2; // Exponential backoff
    } else {
        break; // Non-retryable error
    }
}
```

### Logging

All Velana operations are logged with context:

```php
$logModel->info('velana', 'Creating PIX transaction', [
    'transaction_id' => $data['transaction_id'],
    'amount' => $data['amount']
]);
```

Log categories:
- `velana` - Service operations
- `webhook` - Webhook processing
- `acquirer` - Account selection and fallback

---

## Differences from PodPay

### Key Differences

| Feature | Velana | PodPay |
|---------|--------|--------|
| Transaction ID | Numeric (123456) | String/UUID |
| Amount Format | Cents (integer) | Cents (integer) |
| Authentication | Basic `secret_key:x` | Basic `client_id:client_secret` |
| Withdraw Key | Not used | Required (x-withdraw-key header) |
| Receipt URL | Available for cashouts | Not available |
| Status Names | Different | Different |
| Email Validation | Strict (generates fake if invalid) | More flexible |

### Code Similarities

Both services follow the same pattern:
- Service class with create/consult methods
- Webhook parsing and validation
- Status mapping
- Integration in AcquirerService
- Multi-account support

### Migration Notes

If migrating from PodPay to Velana:
1. ✅ Same database structure
2. ✅ Same multi-account system
3. ✅ Same webhook security validation
4. ⚠️ Different authentication format
5. ⚠️ Different status values
6. ⚠️ Different transaction ID format

---

## Troubleshooting

### Webhook Not Received

**Check:**
1. Webhook URL is publicly accessible
2. SSL certificate is valid
3. Firewall allows Velana IPs
4. `postbackUrl` is correctly set in requests

### Webhook Validation Fails

**Check:**
1. Transaction has `acquirer_account_id` set
2. Account credentials are correct
3. Transaction ID matches exactly
4. Velana API is accessible from your server

### Balance Issues

**Check:**
1. Velana account has sufficient balance
2. Daily limits are not exceeded
3. Account status is 'active'
4. Fallback accounts are configured

### Amount Discrepancies

**Remember:**
- Always multiply by 100 when sending to Velana (BRL to cents)
- Always divide by 100 when receiving from Velana (cents to BRL)
- Use `DECIMAL(15,2)` in database for amounts in BRL

---

## Support

For Velana-specific issues:
- 📧 Email: suporte@velana.com.br
- 📚 Docs: https://docs.velana.com.br
- 🔧 Dashboard: https://dashboard.velana.com.br

For system integration issues:
- Check logs in `logs` table with category 'velana' or 'webhook'
- Review callback history in `callbacks_acquirers` table
- Verify account configuration in `acquirer_accounts` table

---

## Testing Checklist

Before going to production:

- [ ] SQL migration executed successfully
- [ ] Velana accounts configured with real credentials
- [ ] Test cash-in transaction completes successfully
- [ ] QR code displays and can be scanned
- [ ] Webhook received and validated correctly
- [ ] Test cash-out transfer completes successfully
- [ ] Receipt URL saved and accessible
- [ ] Fallback system works (test with insufficient balance)
- [ ] Daily limits enforced correctly
- [ ] Status transitions logged properly
- [ ] Seller balance updated correctly
- [ ] All error scenarios handled gracefully

---

**Last Updated:** 2025-12-08
**Version:** 1.0.0
