# 🔴 PROBLEMA IDENTIFICADO: Cliente enviando HASH ao invés de SECRET

## O que está acontecendo?

No log do Apache você viu:
```
X-API-Secret length: 64
X-API-Secret first 4 chars: ecce
```

**64 caracteres = SHA256 hash!**

O cliente está enviando o **HASH** ao invés do **SECRET em texto plano**.

## Como o sistema funciona?

```
┌─────────────────────────────────────────────────────────────────┐
│ FLUXO CORRETO                                                   │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  Cliente envia:                                                 │
│    X-API-Key: sk_live_xxxxx                                     │
│    X-API-Secret: live_secret_abc123 (TEXTO PLANO)               │
│                                                                 │
│  ↓                                                              │
│                                                                 │
│  Sistema recebe e faz:                                          │
│    hash('sha256', 'live_secret_abc123')                         │
│    = ecce1234... (64 chars)                                     │
│                                                                 │
│  ↓                                                              │
│                                                                 │
│  Sistema compara:                                               │
│    hash gerado == hash do banco ✅                              │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

```
┌─────────────────────────────────────────────────────────────────┐
│ FLUXO INCORRETO (O QUE ESTÁ ACONTECENDO)                        │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  Cliente envia:                                                 │
│    X-API-Key: sk_live_xxxxx                                     │
│    X-API-Secret: ecce1234... (JÁ É UM HASH!)                    │
│                                                                 │
│  ↓                                                              │
│                                                                 │
│  Sistema recebe e faz:                                          │
│    hash('sha256', 'ecce1234...')                                │
│    = abc9876... (HASH DIFERENTE!)                               │
│                                                                 │
│  ↓                                                              │
│                                                                 │
│  Sistema compara:                                               │
│    hash(hash) != hash do banco ❌                               │
│    AUTENTICAÇÃO FALHA!                                          │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

## 🔧 Solução

### Passo 1: Diagnóstico
```bash
php check_credentials.php
```

### Passo 2: Gerar novas credenciais
```bash
php regenerate_live_credentials.php
```

Este script vai:
1. ✅ Gerar um novo secret em texto plano
2. ✅ Fazer o hash SHA256 do secret
3. ✅ Atualizar o hash no banco de dados
4. ✅ Mostrar as credenciais corretas para enviar ao cliente

### Passo 3: Enviar credenciais ao cliente

**IMPORTANTE:** Envie ao cliente:
- ✅ API Key: `sk_live_xxxxx`
- ✅ API Secret: `live_secret_abc123` (TEXTO PLANO)
- ❌ NÃO envie o hash que está no banco!

## 📝 Documentação para o Cliente

Copie e envie isto:

---

### Como usar a API

**Suas credenciais:**
- API Key: `[inserir aqui]`
- API Secret: `[inserir aqui]` (texto plano, não o hash)

**Método 1 - Basic Authentication:**
```bash
curl -X POST 'https://api.exemplo.com/api/pix/create' \
  -u 'API_KEY:API_SECRET' \
  -H 'Content-Type: application/json' \
  -d '{
    "amount": 100.00,
    "cpf_cnpj": "12345678901",
    "name": "João Silva"
  }'
```

**Método 2 - Headers personalizados:**
```bash
curl -X POST 'https://api.exemplo.com/api/pix/create' \
  -H 'X-API-Key: SEU_API_KEY' \
  -H 'X-API-Secret: SEU_API_SECRET' \
  -H 'Content-Type: application/json' \
  -d '{
    "amount": 100.00,
    "cpf_cnpj": "12345678901",
    "name": "João Silva"
  }'
```

**Importante:** Use o API Secret exatamente como fornecido. NÃO faça hash dele.

---

## ✅ Checklist

- [ ] Executou `php check_credentials.php` para diagnosticar
- [ ] Executou `php regenerate_live_credentials.php` para gerar novas credenciais
- [ ] Copiou o API Secret em TEXTO PLANO (não o hash)
- [ ] Enviou as credenciais corretas ao cliente
- [ ] Cliente testou e autenticação funcionou

## 🎯 Resumo

**Problema:** Cliente usando hash ao invés de secret em texto plano
**Causa:** Credenciais incorretas foram enviadas ao cliente
**Solução:** Gerar novo secret, enviar TEXTO PLANO para o cliente
**Sistema:** Faz hash automaticamente, não precisa enviar hash
