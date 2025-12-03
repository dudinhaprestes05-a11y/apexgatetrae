# Instruções para Usar HMAC-SHA256

Se você quer que o sistema use HMAC-SHA256 ao invés de SHA256 simples, siga estes passos:

## 1. Defina a Chave HMAC

Adicione no `.env`:

```bash
HMAC_SECRET_KEY=sua_chave_secreta_muito_segura_aqui
```

## 2. Atualize o config.php

Adicione no `/app/config/config.php`:

```php
define('HMAC_SECRET_KEY', getenv('HMAC_SECRET_KEY') ?: 'chave_padrao_dev');
```

## 3. Atualize AuthService.php

**Linha 77**, altere de:

```php
$hashedApiSecret = hash('sha256', $apiSecret);
```

Para:

```php
$hashedApiSecret = hash_hmac('sha256', $apiSecret, HMAC_SECRET_KEY);
```

## 4. Atualize Seller.php

**Linha 81 e 93**, altere de:

```php
'api_secret' => hash('sha256', generateApiSecret())
```

Para:

```php
'api_secret' => hash_hmac('sha256', generateApiSecret(), HMAC_SECRET_KEY)
```

## 5. Atualize SellerController.php

**Linha 198**, altere de:

```php
'api_secret' => $hashedSecret
```

Onde `$hashedSecret` é calculado com:

```php
$hashedSecret = hash_hmac('sha256', $newApiSecret, HMAC_SECRET_KEY);
```

## 6. Atualize AdminController.php

**Linha 155**, altere de:

```php
'api_secret' => hash('sha256', $apiSecret),
```

Para:

```php
'api_secret' => hash_hmac('sha256', $apiSecret, HMAC_SECRET_KEY),
```

## 7. Atualize os Sellers Existentes

Execute o script:

```bash
php switch_hash_method.php
```

E escolha a opção 2 (HMAC-SHA256), informando a mesma chave que você definiu no `.env`.

---

## OU: Manter SHA256 Simples (Recomendado)

Se você quer usar SHA256 simples (que é mais simples e suficientemente seguro):

1. Execute: `php fix_demo_credentials.php`
2. Isso vai corrigir o hash no banco para usar SHA256
3. O código atual já está preparado para SHA256

**SHA256 vs HMAC:**

- **SHA256**: Simples, rápido, não precisa de chave
- **HMAC-SHA256**: Mais complexo, precisa de chave secreta compartilhada

Para o caso de uso de API Secrets (onde estamos apenas verificando identidade, não assinando mensagens), SHA256 é suficiente e mais simples.
