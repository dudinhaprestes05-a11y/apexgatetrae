<?php

// Script para simular uma requisição de autenticação e debugar o processo

require_once __DIR__ . '/app/config/config.php';
require_once __DIR__ . '/app/config/database.php';
require_once __DIR__ . '/app/config/helpers.php';
require_once __DIR__ . '/app/models/Seller.php';

echo "=== DEBUG DE AUTENTICAÇÃO ===\n\n";

// Buscar seller do banco
$db = db();
$stmt = $db->query("SELECT id, name, api_key, api_secret, status FROM sellers WHERE email = 'seller@demo.com' LIMIT 1");
$seller = $stmt->fetch();

if (!$seller) {
    echo "❌ Seller não encontrado!\n";
    exit(1);
}

echo "✅ Seller encontrado:\n";
echo "   ID: {$seller['id']}\n";
echo "   API Key: {$seller['api_key']}\n";
echo "   API Secret (hash no banco): {$seller['api_secret']}\n";
echo "   Hash length: " . strlen($seller['api_secret']) . "\n\n";

// Credenciais que você está tentando usar
echo "=== CREDENCIAIS PARA TESTE ===\n";
echo "Qual API Secret você está usando na requisição?\n";
echo "Digite aqui: ";
$handle = fopen("php://stdin", "r");
$inputSecret = trim(fgets($handle));
fclose($handle);

if (empty($inputSecret)) {
    echo "❌ Nenhum secret informado!\n";
    exit(1);
}

echo "\n=== PROCESSO DE AUTENTICAÇÃO ===\n";
echo "1. Secret recebido: $inputSecret\n";
echo "2. Gerando hash SHA256...\n";

$hashedInput = hash('sha256', $inputSecret);
echo "3. Hash gerado: $hashedInput\n";
echo "4. Hash no banco: {$seller['api_secret']}\n";

if ($hashedInput === $seller['api_secret']) {
    echo "\n✅ SUCESSO! Os hashes coincidem!\n";
    echo "A autenticação deveria funcionar.\n\n";

    $basicAuth = base64_encode("{$seller['api_key']}:$inputSecret");
    echo "Use este comando para testar:\n";
    echo "curl -X GET http://localhost:8000/api/pix/list \\\n";
    echo "  -H \"Authorization: Basic $basicAuth\" \\\n";
    echo "  -H \"Content-Type: application/json\"\n";
} else {
    echo "\n❌ FALHA! Os hashes NÃO coincidem!\n";
    echo "Este é o motivo do erro 'Invalid credentials'.\n\n";

    echo "Possíveis soluções:\n";
    echo "1. Verifique se você está usando o API Secret correto\n";
    echo "2. O secret correto do demo é: demo_secret_key_987654321\n";
    echo "3. Ou regenere suas credenciais na interface web\n\n";

    // Testar com o secret padrão
    $defaultSecret = 'demo_secret_key_987654321';
    $defaultHash = hash('sha256', $defaultSecret);

    echo "Testando com secret padrão do schema.sql...\n";
    echo "Secret: $defaultSecret\n";
    echo "Hash: $defaultHash\n";

    if ($defaultHash === $seller['api_secret']) {
        echo "✅ O secret padrão funciona!\n";
        $basicAuth = base64_encode("{$seller['api_key']}:$defaultSecret");
        echo "\nUse este comando:\n";
        echo "curl -X GET http://localhost:8000/api/pix/list \\\n";
        echo "  -H \"Authorization: Basic $basicAuth\" \\\n";
        echo "  -H \"Content-Type: application/json\"\n";
    } else {
        echo "❌ Nem o secret padrão funciona. O banco pode ter sido modificado.\n";
    }
}
