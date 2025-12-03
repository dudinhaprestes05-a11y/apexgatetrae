#!/usr/bin/env php
<?php

echo "=== Gateway PIX - Database Setup ===\n\n";

function loadEnv($path) {
    if (!file_exists($path)) {
        die("Erro: Arquivo .env não encontrado!\n");
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }

        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);
            $value = trim($value, '"\'');

            if (!array_key_exists($name, $_ENV)) {
                putenv("$name=$value");
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
            }
        }
    }
}

loadEnv(__DIR__ . '/.env');

$dbHost = getenv('DB_HOST') ?: 'localhost';
$dbName = getenv('DB_NAME') ?: 'gateway_pix';
$dbUser = getenv('DB_USER') ?: 'root';
$dbPass = getenv('DB_PASS') ?: '';

echo "Configurações:\n";
echo "- Host: $dbHost\n";
echo "- Database: $dbName\n";
echo "- User: $dbUser\n\n";

try {
    echo "[1/4] Conectando ao MySQL...\n";
    $pdo = new PDO("mysql:host=$dbHost;charset=utf8mb4", $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✓ Conectado com sucesso!\n\n";

    echo "[2/4] Criando banco de dados '$dbName'...\n";
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `$dbName`");
    echo "✓ Banco de dados criado/selecionado!\n\n";

    echo "[3/4] Executando schema.sql...\n";
    $schema = file_get_contents(__DIR__ . '/sql/schema.sql');

    $statements = array_filter(
        array_map('trim', explode(';', $schema)),
        function($stmt) {
            return !empty($stmt) && strpos($stmt, '/*') !== 0;
        }
    );

    foreach ($statements as $statement) {
        if (!empty($statement)) {
            $pdo->exec($statement);
        }
    }
    echo "✓ Schema criado com sucesso!\n\n";

    echo "[4/4] Criando usuários padrão...\n";

    $adminExists = $pdo->query("SELECT COUNT(*) FROM users WHERE email = 'admin@gateway.com'")->fetchColumn();

    if ($adminExists == 0) {
        $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $pdo->exec("INSERT INTO users (name, email, password, role, status) VALUES ('Administrador', 'admin@gateway.com', '$adminPassword', 'admin', 'active')");
        echo "✓ Usuário admin criado!\n";
        echo "  Email: admin@gateway.com\n";
        echo "  Senha: admin123\n\n";
    } else {
        echo "⚠ Usuário admin já existe\n\n";
    }

    $sellerExists = $pdo->query("SELECT COUNT(*) FROM sellers WHERE email = 'seller@demo.com'")->fetchColumn();

    if ($sellerExists == 0) {
        $pdo->exec("INSERT INTO sellers (name, email, document, person_type, status, document_status) VALUES ('Demo Seller', 'seller@demo.com', '12345678900', 'individual', 'pending', 'pending')");
        $sellerId = $pdo->lastInsertId();

        $sellerPassword = password_hash('seller123', PASSWORD_DEFAULT);
        $pdo->exec("INSERT INTO users (seller_id, name, email, password, role, status) VALUES ($sellerId, 'Demo Seller', 'seller@demo.com', '$sellerPassword', 'seller', 'active')");

        echo "✓ Seller demo criado!\n";
        echo "  Email: seller@demo.com\n";
        echo "  Senha: seller123\n\n";
    } else {
        echo "⚠ Seller demo já existe\n\n";
    }

    $podpayExists = $pdo->query("SELECT COUNT(*) FROM acquirers WHERE code = 'podpay'")->fetchColumn();

    if ($podpayExists == 0) {
        $pdo->exec("INSERT INTO acquirers (name, code, api_url, priority_order, status, daily_reset_at) VALUES ('PodPay', 'podpay', 'https://api.podpay.com.br', 1, 'active', CURDATE())");
        echo "✓ Adquirente PodPay cadastrada!\n\n";
    } else {
        echo "⚠ Adquirente PodPay já existe\n\n";
    }

    echo "=== Setup concluído com sucesso! ===\n\n";
    echo "Você pode acessar o sistema em: " . (getenv('BASE_URL') ?: 'http://localhost') . "\n";
    echo "\nLogin Admin:\n";
    echo "  Email: admin@gateway.com\n";
    echo "  Senha: admin123\n\n";

} catch (PDOException $e) {
    echo "\n✗ ERRO: " . $e->getMessage() . "\n\n";
    echo "Verifique:\n";
    echo "1. Se o MySQL está rodando\n";
    echo "2. Se as credenciais no .env estão corretas\n";
    echo "3. Se o usuário tem permissão para criar databases\n\n";
    exit(1);
}
