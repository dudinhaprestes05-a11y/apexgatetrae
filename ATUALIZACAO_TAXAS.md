# Atualização do Sistema de Taxas

## Correções Implementadas

### 1. Problema com Valores Decimais
**Problema:** Taxas com vírgula (0,99) eram salvas como números inteiros (99)

**Solução:**
- Criado método `parseDecimal()` que aceita valores com vírgula
- Campos alterados de `type="number"` para `type="text"`
- Conversão automática de vírgula para ponto
- Valores percentuais são divididos por 100 antes de salvar

**Formato Aceito:**
- `0,99` → salva como `0.0099` (0.99%)
- `2,50` → salva como `0.0250` (2.50%)
- `1.5` → salva como `0.0150` (1.50%)

### 2. Remoção do Limite de 15%
**Problema:** Taxa máxima limitada em 15%

**Solução:**
- Removido `max="15"` de todos os campos de taxa
- Validação alterada para aceitar apenas valores não negativos
- Admin tem controle total sobre as taxas

### 3. Nova Página de Configurações
**Local:** `/admin/settings`

**Funcionalidades:**
- Definir taxas padrão para novos sellers
- Taxa percentual e fixa para Cash-in
- Taxa percentual e fixa para Cash-out
- As taxas são aplicadas automaticamente a novos cadastros
- Sellers existentes mantêm suas taxas atuais

## Migração do Banco de Dados

Execute o script de migração:

```bash
php run_settings_migration.php
```

Isso criará:
- Tabela `system_settings` com as configurações padrão
- Registro inicial com taxas zeradas

## Como Usar

### 1. Configurar Taxas Padrão
1. Acesse `/admin/settings`
2. Configure as taxas que deseja aplicar a novos sellers
3. Salve as configurações

### 2. Taxas por Seller
- Cada seller pode ter taxas customizadas
- Edite em `/admin/sellers/view/{id}`
- Use vírgula para decimais (ex: 2,50)

### 3. Formato dos Valores
**Taxas Percentuais:**
- Digite: `0,99` para 0,99%
- Digite: `2,50` para 2,50%
- Digite: `15` para 15%

**Taxas Fixas:**
- Digite: `0,50` para R$ 0,50
- Digite: `2,00` para R$ 2,00
- Digite: `10` para R$ 10,00

## Arquivos Alterados

### Backend
- `app/controllers/web/AdminController.php` - Método parseDecimal() e configurações
- `app/controllers/web/AuthController.php` - Aplica taxas padrão em novos sellers
- `app/models/SystemSettings.php` - Novo modelo
- `index.php` - Rotas de configurações

### Frontend
- `app/views/admin/seller-details.php` - Campos de taxa aceitam vírgula
- `app/views/admin/settings.php` - Nova página de configurações

### Banco de Dados
- `sql/create_settings.sql` - Migração da tabela de configurações

## Observações Importantes

1. **Novos Sellers:** Recebem automaticamente as taxas configuradas em `/admin/settings`

2. **Sellers Existentes:** Mantêm suas taxas atuais, não são afetados pelas configurações padrão

3. **Formato de Entrada:** O sistema aceita tanto vírgula quanto ponto como separador decimal

4. **Sem Limite:** Não há mais limite de 15% nas taxas, você define o valor que quiser

5. **Retenção:** O campo de percentual de retenção também aceita vírgula
