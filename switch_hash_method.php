<?php

/**
 * Script para alternar entre SHA256 e HMAC-SHA256
 */

require_once __DIR__ . '/app/config/database.php';

echo "=== ALTERNAR MÉTODO DE HASH ===\n\n";
echo "Escolha o método de hash para API Secrets:\n";
echo "1. SHA256 simples (hash) - Recomendado e padrão\n";
echo "2. HMAC-SHA256 (hash_hmac) - Requer chave secreta\n\n";
echo "Digite sua escolha (1 ou 2): ";

$handle = fopen("php://stdin", "r");
$choice = trim(fgets($handle));

if ($choice === '1') {
    echo "\n✅ Usando SHA256 simples\n\n";

    $plainSecret = 'demo_secret_key_987654321';
    $hashedSecret = hash('sha256', $plainSecret);

    echo "=== ATUALIZAR SELLER DEMO ===\n";
    echo "Secret (texto): $plainSecret\n";
    echo "Hash (SHA256):  $hashedSecret\n\n";
    echo "Deseja atualizar o seller demo no banco? (y/n): ";

    $update = trim(fgets($handle));

    if ($update === 'y' || $update === 'Y') {
        $db = db();
        $stmt = $db->prepare("UPDATE sellers SET api_secret = ? WHERE email = 'seller@demo.com'");
        $stmt->execute([$hashedSecret]);

        echo "✅ Seller demo atualizado!\n\n";

        $basicAuth = base64_encode("sk_test_demo_key_123456789:$plainSecret");

        echo "=== TESTE ===\n";
        echo "curl -X GET http://localhost:8000/api/pix/list \\\n";
        echo "  -H \"Authorization: Basic $basicAuth\" \\\n";
        echo "  -H \"Content-Type: application/json\"\n";
    }

} elseif ($choice === '2') {
    echo "\n⚠️  HMAC-SHA256 requer uma chave secreta!\n";
    echo "Digite a chave HMAC (ou deixe em branco para usar chave vazia): ";

    $hmacKey = trim(fgets($handle));

    echo "\n✅ Usando HMAC-SHA256\n";
    echo "Chave HMAC: " . ($hmacKey ?: '(vazia)') . "\n\n";

    $plainSecret = 'demo_secret_key_987654321';
    $hashedSecret = hash_hmac('sha256', $plainSecret, $hmacKey);

    echo "=== ATUALIZAR SELLER DEMO ===\n";
    echo "Secret (texto):  $plainSecret\n";
    echo "Hash (HMAC):     $hashedSecret\n";
    echo "Chave HMAC:      " . ($hmacKey ?: '(vazia)') . "\n\n";
    echo "Deseja atualizar o seller demo no banco? (y/n): ";

    $update = trim(fgets($handle));

    if ($update === 'y' || $update === 'Y') {
        $db = db();
        $stmt = $db->prepare("UPDATE sellers SET api_secret = ? WHERE email = 'seller@demo.com'");
        $stmt->execute([$hashedSecret]);

        echo "✅ Seller demo atualizado!\n\n";

        echo "⚠️  IMPORTANTE: Você precisa ajustar o código em AuthService.php\n";
        echo "Linha 77, altere de:\n";
        echo "  \$hashedApiSecret = hash('sha256', \$apiSecret);\n\n";
        echo "Para:\n";
        echo "  \$hashedApiSecret = hash_hmac('sha256', \$apiSecret, '$hmacKey');\n\n";

        echo "Ou use a função em HMAC_CONFIG.php\n";
    }

} else {
    echo "❌ Escolha inválida!\n";
}

fclose($handle);
