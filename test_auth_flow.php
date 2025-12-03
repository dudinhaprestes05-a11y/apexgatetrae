<?php

require_once __DIR__ . '/app/config/database.php';

echo "=== TESTE DE AUTENTICAÇÃO ===\n\n";

// Buscar o seller demo
$db = db();
$stmt = $db->query("SELECT id, name, api_key, api_secret, status FROM sellers WHERE email = 'seller@demo.com' LIMIT 1");
$seller = $stmt->fetch();

if (!$seller) {
    echo "❌ Seller não encontrado no banco!\n";
    exit(1);
}

echo "✅ Seller encontrado no banco:\n";
echo "   ID: {$seller['id']}\n";
echo "   Name: {$seller['name']}\n";
echo "   Status: {$seller['status']}\n";
echo "   API Key: {$seller['api_key']}\n";
echo "   API Secret (hash): " . substr($seller['api_secret'], 0, 16) . "...\n";
echo "   API Secret length: " . strlen($seller['api_secret']) . "\n\n";

// Testar com o secret em texto plano do schema.sql
$plainSecret = 'demo_secret_key_987654321';
$hashedSecret = hash('sha256', $plainSecret);

echo "=== TESTE DE HASH ===\n";
echo "Plain Secret: $plainSecret\n";
echo "Hash gerado: $hashedSecret\n";
echo "Hash no banco: {$seller['api_secret']}\n";
echo "Match: " . ($hashedSecret === $seller['api_secret'] ? '✅ SIM' : '❌ NÃO') . "\n\n";

// Teste de SQL SHA2
$stmt = $db->prepare("SELECT SHA2(?, 256) as hash");
$stmt->execute([$plainSecret]);
$sqlHash = $stmt->fetch()['hash'];

echo "=== COMPARAÇÃO SQL vs PHP ===\n";
echo "PHP hash(): $hashedSecret\n";
echo "SQL SHA2(): $sqlHash\n";
echo "Match: " . ($hashedSecret === $sqlHash ? '✅ SIM' : '❌ NÃO') . "\n\n";

// Verificar se precisa regenerar
if ($hashedSecret !== $seller['api_secret']) {
    echo "⚠️  ATENÇÃO: O hash não está batendo!\n";
    echo "Isso pode acontecer se o secret foi regenerado.\n\n";

    // Tentar atualizar com o hash correto
    echo "Deseja atualizar o seller com o hash correto? (y/n): ";
    $handle = fopen("php://stdin", "r");
    $line = trim(fgets($handle));

    if ($line === 'y') {
        $updateStmt = $db->prepare("UPDATE sellers SET api_secret = ? WHERE id = ?");
        $updateStmt->execute([$hashedSecret, $seller['id']]);
        echo "✅ Seller atualizado com sucesso!\n";
    } else {
        echo "❌ Não atualizado.\n";
    }
} else {
    echo "✅ Hash está correto! A autenticação deveria funcionar.\n";
}

echo "\n=== TESTE DE AUTENTICAÇÃO BÁSICA ===\n";
$apiKey = $seller['api_key'];
$apiSecret = $plainSecret;
$basicAuth = base64_encode("$apiKey:$apiSecret");

echo "API Key: $apiKey\n";
echo "API Secret (plain): $apiSecret\n";
echo "Basic Auth Token: $basicAuth\n\n";

echo "Para testar a API, use:\n";
echo "curl -X GET http://localhost:8000/api/pix/list \\\n";
echo "  -H \"Authorization: Basic $basicAuth\" \\\n";
echo "  -H \"Content-Type: application/json\"\n\n";

echo "Ou com headers separados:\n";
echo "curl -X GET http://localhost:8000/api/pix/list \\\n";
echo "  -H \"X-API-Key: $apiKey\" \\\n";
echo "  -H \"X-API-Secret: $apiSecret\" \\\n";
echo "  -H \"Content-Type: application/json\"\n";
