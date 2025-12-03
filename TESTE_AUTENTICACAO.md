# 🔍 Como Testar a Autenticação da API

## Problema Atual

Você está recebendo erro 401 (Unauthorized) ao tentar usar a API, mesmo com as credenciais corretas.

## Passo 1: Verificar os Secrets no Banco

Acesse no navegador:

```
https://seu-dominio.com/debug_api_credentials.php
```

Isso vai listar todos os sellers. Anote o ID do seller que você quer testar.

## Passo 2: Verificar um Seller Específico

```
https://seu-dominio.com/debug_api_credentials.php?seller_id=1
```

Isso vai mostrar:
- API Key
- API Secret (hash no banco)
- Status do formato (se é SHA256 válido)

## Passo 3: Testar um Secret

```
https://seu-dominio.com/debug_api_credentials.php?seller_id=1&test_secret=SEU_SECRET_AQUI
```

Substitua `SEU_SECRET_AQUI` pelo secret que você copiou quando regenerou as credenciais.

O sistema vai:
1. Fazer o hash SHA256 do secret que você enviou
2. Comparar com o hash armazenado no banco
3. Te dizer se batem ou não
4. Diagnosticar o problema se não baterem

## Exemplo Completo

1. Vá para o painel do seller
2. Clique em "Regenerar Credenciais"
3. Copie o "Novo API Secret" (algo como: `9a46873aa5f87f095113d72d1abf7bd05a50f3df6029c9bc6f856b5761c33923`)
4. Acesse: `https://seu-dominio.com/debug_api_credentials.php?seller_id=1&test_secret=9a46873aa5f87f095113d72d1abf7bd05a50f3df6029c9bc6f856b5761c33923`
5. Veja o resultado

## Possíveis Problemas e Soluções

### ❌ "Você está enviando o HASH ao invés do secret em texto plano"

**Problema**: Você está tentando usar o hash SHA256 ao invés do secret original.

**Solução**: Use o secret exatamente como foi mostrado no painel após regenerar.

### ❌ "O banco tem o secret em texto plano ao invés do hash"

**Problema**: Houve um bug ao salvar as credenciais no banco.

**Solução**: 
1. Acesse o painel como admin
2. Vá em "Sellers" > "Detalhes do Seller"
3. Force uma regeneração de credenciais

### ❌ "Os secrets não batem"

**Possíveis causas**:
1. Você está usando credenciais antigas (já foram regeneradas)
2. Copiou o secret errado (com espaços ou quebras de linha)
3. Não copiou o secret completo

**Solução**: Regenere as credenciais e copie cuidadosamente o novo secret.

## Logs Detalhados

Após testar com o debug_api_credentials.php, faça uma requisição real para a API e verifique os logs em:

```
/var/log/apache2/error.log
```

ou

```
/var/log/php/error.log
```

Os logs vão mostrar:
- Secret recebido (texto plano)
- Hash do secret recebido
- Hash armazenado no banco
- Se batem ou não
- Diferenças byte-a-byte se não baterem

## Teste Via cURL

Depois de confirmar que o secret está correto, teste via cURL:

```bash
curl -v -X POST 'https://seu-dominio.com/api/pix/create' \
  -u 'SEU_API_KEY:SEU_API_SECRET' \
  -H 'Content-Type: application/json' \
  -d '{
    "amount": 10.00,
    "cpf_cnpj": "12345678901",
    "name": "Teste"
  }'
```

Se você ver `HTTP/1.1 401 Unauthorized`, verifique os logs.

Se você ver `HTTP/1.1 200 OK` ou outro código diferente de 401, a autenticação funcionou!

## ⚠️ IMPORTANTE

**REMOVA O ARQUIVO `debug_api_credentials.php` QUANDO TERMINAR O DEBUG!**

Ele expõe informações sensíveis e não deve estar disponível em produção.

```bash
rm debug_api_credentials.php
```
