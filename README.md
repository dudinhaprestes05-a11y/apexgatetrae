# Gateway de Pagamentos PIX

Sistema completo de Gateway de Pagamentos PIX com funcionalidades avançadas de cash-in, cash-out e gerenciamento multi-seller.

> 🆕 **Sistema Multi-Conta:** Agora com suporte para múltiplas contas de adquirentes, distribuição de carga e fallback automático. [Saiba mais sobre a migração](MIGRATION_TO_MULTI_ACCOUNT.md)

## Características

- **PHP 8.0+ Nativo** - Sem frameworks externos
- **Arquitetura MVC** - Código organizado e modular
- **Multi-Seller** - Suporte a múltiplos vendedores
- **Multi-Conta Adquirente** - Múltiplas contas por adquirente com fallback inteligente
- **Distribuição de Carga** - Estratégias configuráveis (prioridade, round-robin, porcentagem)
- **API RESTful** - Endpoints completos para todas operações
- **Sistema de Webhooks** - Fila assíncrona com retry automático
- **Split de Pagamentos** - Divisão automática de valores
- **Antifraude** - Validações e análise de risco
- **Rate Limiting** - Controle de taxa de requisições
- **Logs Completos** - Auditoria detalhada de todas operações

## Requisitos

- PHP 8.0 ou superior
- MySQL 5.7 ou superior
- Apache com mod_rewrite
- Extensões PHP: PDO, PDO_MySQL, cURL, JSON, OpenSSL

## Instalação

### 1. Clone/Baixe o projeto

```bash
cd /var/www/html/gateway-pix
```

### 2. Configure o banco de dados

```bash
mysql -u root -p
CREATE DATABASE gateway_pix CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
exit;

mysql -u root -p gateway_pix < sql/schema.sql
```

### 3. Configure o ambiente

```bash
cp .env.example .env
nano .env
```

Edite as variáveis de ambiente:

```env
DB_HOST=localhost
DB_NAME=gateway_pix
DB_USER=root
DB_PASS=sua_senha
BASE_URL=https://seu-dominio.com
```

### 4. Configure permissões

```bash
chmod -R 755 /var/www/html/gateway-pix
mkdir -p logs
chmod -R 777 logs
```

### 5. Configure o Apache

```apache
<VirtualHost *:80>
    ServerName gateway-pix.local
    DocumentRoot /var/www/html/gateway-pix

    <Directory /var/www/html/gateway-pix>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/gateway-pix-error.log
    CustomLog ${APACHE_LOG_DIR}/gateway-pix-access.log combined
</VirtualHost>
```

### 6. Configure Workers (Cron)

```bash
crontab -e
```

Adicione:

```cron
* * * * * /usr/bin/php /var/www/html/gateway-pix/app/workers/process_webhooks.php
*/5 * * * * /usr/bin/php /var/www/html/gateway-pix/app/workers/reconcile_transactions.php
*/2 * * * * /usr/bin/php /var/www/html/gateway-pix/app/workers/process_payouts.php
```

## Uso da API

### Autenticação

Todas as requisições devem incluir:

```
X-API-Key: sua_api_key
X-Signature: hmac_sha256(payload, api_secret)
```

### Criar PIX (Cash-in)

```bash
POST /api/pix/create

{
  "amount": 100.50,
  "pix_type": "dynamic",
  "expires_in_minutes": 30,
  "metadata": {
    "order_id": "12345"
  },
  "splits": [
    {
      "seller_id": 2,
      "percentage": 10
    }
  ]
}
```

**Resposta:**

```json
{
  "success": true,
  "message": "PIX transaction created successfully",
  "data": {
    "transaction_id": "CASHIN_20231201120000_a1b2c3d4",
    "amount": 100.50,
    "fee_amount": 0.99,
    "net_amount": 99.51,
    "qrcode": "00020126580014br.gov.bcb.pix...",
    "qrcode_base64": "data:image/png;base64,iVBORw0KGgo...",
    "pix_key": "12345678-abcd-1234-efgh-123456789012",
    "expires_at": "2023-12-01 12:30:00",
    "status": "pending"
  }
}
```

### Consultar PIX

```bash
GET /api/pix/consult?transaction_id=CASHIN_20231201120000_a1b2c3d4
```

### Listar Transações

```bash
GET /api/pix/list?status=paid&limit=50
```

### Criar Cashout

```bash
POST /api/cashout/create

{
  "amount": 50.00,
  "pix_key": "12345678000190",
  "pix_key_type": "cnpj",
  "beneficiary_name": "Empresa LTDA",
  "beneficiary_document": "12345678000190"
}
```

### Consultar Cashout

```bash
GET /api/cashout/consult?transaction_id=CASHOUT_20231201120000_a1b2c3d4
```

## Webhooks

O sistema envia webhooks para a URL configurada em cada seller quando há mudança de status.

**Payload:**

```json
{
  "event": "cashin.paid",
  "transaction_id": "CASHIN_20231201120000_a1b2c3d4",
  "data": {
    "transaction_id": "CASHIN_20231201120000_a1b2c3d4",
    "amount": 100.50,
    "status": "paid",
    "paid_at": "2023-12-01 12:15:00"
  },
  "timestamp": "2023-12-01T12:15:00-03:00"
}
```

**Headers:**

```
X-Signature: hmac_sha256(payload, webhook_secret)
X-Transaction-Id: CASHIN_20231201120000_a1b2c3d4
```

## Estrutura do Projeto

```
/
├── index.php                 # Router principal
├── .htaccess                 # Configuração Apache
├── .env                      # Variáveis de ambiente
├── app/
│   ├── config/              # Configurações
│   │   ├── config.php
│   │   ├── database.php
│   │   └── helpers.php
│   ├── models/              # Models
│   │   ├── BaseModel.php
│   │   ├── Seller.php
│   │   ├── Acquirer.php
│   │   ├── PixCashin.php
│   │   ├── PixCashout.php
│   │   ├── User.php
│   │   ├── Log.php
│   │   └── WebhookQueue.php
│   ├── services/            # Serviços
│   │   ├── AuthService.php
│   │   ├── AntiFraudService.php
│   │   ├── AcquirerService.php
│   │   ├── SplitService.php
│   │   └── WebhookService.php
│   ├── controllers/         # Controllers
│   │   └── api/
│   │       ├── PixController.php
│   │       ├── CashoutController.php
│   │       └── WebhookController.php
│   └── workers/             # Workers
│       ├── process_webhooks.php
│       ├── reconcile_transactions.php
│       └── process_payouts.php
├── sql/
│   └── schema.sql           # Schema do banco
└── logs/                    # Logs do sistema
```

## Credenciais Padrão

**Admin:**
- Email: admin@gateway.com
- Senha: password

**Seller Demo:**
- Email: seller@demo.com
- Senha: password
- API Key: sk_test_demo_key_123456789

## Segurança

- Autenticação via API Key + HMAC SHA256
- Rate limiting configurável
- Validação de CPF/CNPJ
- Sistema antifraude integrado
- Logs completos de auditoria
- Headers de segurança configurados

## Suporte

Para dúvidas e suporte, consulte a documentação técnica ou entre em contato com o desenvolvedor.

## Licença

Proprietário - Todos os direitos reservados
