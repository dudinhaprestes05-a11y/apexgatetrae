# Guia de Instalação - Gateway PIX

## Requisitos

- PHP 7.4 ou superior
- MySQL 5.7 ou superior
- Extensões PHP: PDO, PDO_MySQL, mbstring, json, openssl

## Passo a Passo

### 1. Configurar o arquivo .env

Edite o arquivo `.env` na raiz do projeto com suas credenciais MySQL:

```env
# Ambiente
APP_ENV=production
APP_NAME="Gateway PIX"
BASE_URL=https://gate.apisafe.fun

# Banco de Dados MySQL
DB_HOST=localhost
DB_NAME=gate
DB_USER=gate
DB_PASS=eH&fvQxd2$r3sdC0

# Outras configurações...
```

### 2. Executar o script de instalação

Execute o script de setup para criar o banco de dados e tabelas:

```bash
php setup.php
```

O script irá:
- ✓ Criar o banco de dados (se não existir)
- ✓ Criar todas as tabelas
- ✓ Criar usuário admin padrão
- ✓ Criar seller demo
- ✓ Configurar adquirente PodPay

### 3. Configurar permissões

Certifique-se de que o diretório `uploads/` tenha permissão de escrita:

```bash
chmod -R 755 uploads/
chown -R www-data:www-data uploads/
```

### 4. Configurar o servidor web

#### Apache

Certifique-se de que o módulo `mod_rewrite` está habilitado e que o arquivo `.htaccess` está presente na raiz.

O `.htaccess` já está configurado e deve redirecionar todas as requisições para o `index.php`.

#### Nginx

Configure seu virtual host:

```nginx
server {
    listen 80;
    server_name gate.apisafe.fun;
    root /var/www/vhosts/gate.apisafe.fun/httpdocs;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.ht {
        deny all;
    }
}
```

### 5. Acessar o sistema

Acesse o sistema pelo navegador:

```
https://gate.apisafe.fun
```

## Credenciais Padrão

### Administrador
- **Email:** admin@gateway.com
- **Senha:** admin123

### Seller Demo
- **Email:** seller@demo.com
- **Senha:** seller123

⚠️ **IMPORTANTE:** Altere as senhas padrão após o primeiro acesso!

## Estrutura de Diretórios

```
project/
├── app/
│   ├── config/         # Configurações
│   ├── controllers/    # Controllers (API e Web)
│   ├── middleware/     # Middlewares de autenticação
│   ├── models/         # Models de dados
│   ├── services/       # Serviços (PodPay, Notificações, etc)
│   ├── views/          # Views HTML/PHP
│   └── workers/        # Workers para processamento assíncrono
├── sql/
│   └── schema.sql      # Schema do banco de dados
├── uploads/            # Upload de documentos
├── .env                # Variáveis de ambiente
├── index.php           # Front controller
└── setup.php           # Script de instalação

```

## Funcionalidades

### Painel Seller
- ✓ Dashboard com estatísticas
- ✓ Gerenciamento de transações (Cash-in e Cash-out)
- ✓ Upload de documentos para aprovação
- ✓ Configuração de perfil e webhooks
- ✓ Credenciais de API
- ✓ Sistema de notificações

### Painel Admin
- ✓ Dashboard com métricas globais
- ✓ Gerenciamento de sellers
- ✓ Aprovação/rejeição de cadastros
- ✓ Análise de documentos
- ✓ Visualização de todas as transações
- ✓ Gerenciamento de adquirentes
- ✓ Logs do sistema

### API REST
- ✓ Criar cobranças PIX
- ✓ Consultar transações
- ✓ Realizar saques PIX
- ✓ Webhook de callbacks

## Solução de Problemas

### Erro: "Database connection failed"

1. Verifique se o MySQL está rodando:
   ```bash
   systemctl status mysql
   ```

2. Teste a conexão manualmente:
   ```bash
   mysql -h localhost -u gate -p gate
   ```

3. Verifique as credenciais no arquivo `.env`

4. Execute novamente o script de setup:
   ```bash
   php setup.php
   ```

### Erro: "Permission denied" ao fazer upload

Configure as permissões do diretório uploads:
```bash
chmod -R 755 uploads/
chown -R www-data:www-data uploads/
```

### Erro 404 nas rotas

Verifique se o módulo `mod_rewrite` está habilitado (Apache):
```bash
a2enmod rewrite
systemctl restart apache2
```

## Suporte

Para dúvidas ou problemas, consulte a documentação completa em:
- API_DOCUMENTATION.md
- INTEGRACAO_PODPAY.md
- EXEMPLOS_API.md

## Segurança

- ✓ Senhas são hasheadas com bcrypt
- ✓ API usa autenticação HMAC-SHA256
- ✓ Documentos são validados antes do upload
- ✓ Rate limiting nas APIs
- ✓ Logs de auditoria completos
- ✓ Middleware de autenticação em todas as rotas protegidas
