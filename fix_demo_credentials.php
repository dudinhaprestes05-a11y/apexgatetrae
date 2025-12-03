<?php

/**
 * Script para corrigir as credenciais do seller demo
 * Garante que o hash no banco está correto
 */

require_once __DIR__ . '/app/config/database.php';

echo "=== CORREÇÃO DE CREDENCIAIS DEMO ===\n\n";

$db = db();

// Buscar seller demo
$stmt = $db->query("SELECT id, name, email, api_key, api_secret, status FROM sellers WHERE email = 'seller@demo.com' LIMIT 1");
$seller = $stmt->fetch();

if (!$seller) {
    echo "❌ Seller demo não encontrado!\n";
    echo "Criando novo seller demo...\n\n";

    // Criar seller demo
    $apiKey = 'sk_test_demo_key_123456789';
    $plainSecret = 'demo_secret_key_987654321';
    $hashedSecret = hash('sha256', $plainSecret);

    $stmt = $db->prepare("
        INSERT INTO sellers (
            name, email, document, phone, person_type, company_name,
            api_key, api_secret, status, document_status, approved_at,
            balance, daily_limit, daily_reset_at, fee_percentage_cashin, fee_percentage_cashout
        ) VALUES (
            'Seller Demo', 'seller@demo.com', '12345678000190', '11999999999',
            'business', 'Empresa Demo LTDA', ?, ?, 'active', 'approved', NOW(),
            0.00, 50000.00, CURDATE(), 0.0099, 0.0199
        )
    ");

    $stmt->execute([$apiKey, $hashedSecret]);
    $sellerId = $db->lastInsertId();

    echo "✅ Seller demo criado com sucesso!\n";
    echo "   ID: $sellerId\n";
    echo "   API Key: $apiKey\n";
    echo "   API Secret: $plainSecret\n";
    echo "   Hash: $hashedSecret\n\n";

    // Criar usuário para o seller
    $stmt = $db->prepare("
        INSERT INTO users (seller_id, name, email, password, role, status)
        VALUES (?, 'Seller Demo User', 'seller@demo.com', ?, 'seller', 'active')
    ");

    // Senha: password
    $password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';
    $stmt->execute([$sellerId, $password]);

    echo "✅ Usuário criado (senha: password)\n\n";

    exit(0);
}

echo "✅ Seller encontrado:\n";
echo "   ID: {$seller['id']}\n";
echo "   Nome: {$seller['name']}\n";
echo "   Email: {$seller['email']}\n";
echo "   Status: {$seller['status']}\n";
echo "   API Key: {$seller['api_key']}\n";
echo "   API Secret (hash): {$seller['api_secret']}\n";
echo "   Hash length: " . strlen($seller['api_secret']) . "\n\n";

// Verificar se o hash está correto
$plainSecret = 'demo_secret_key_987654321';
$correctHash = hash('sha256', $plainSecret);

echo "=== VERIFICAÇÃO DO HASH ===\n";
echo "Secret esperado: $plainSecret\n";
echo "Hash esperado:   $correctHash\n";
echo "Hash no banco:   {$seller['api_secret']}\n";

if ($seller['api_secret'] === $correctHash) {
    echo "\n✅ Hash está CORRETO! Nenhuma correção necessária.\n\n";

    echo "=== CREDENCIAIS PARA USAR ===\n";
    echo "API Key:    {$seller['api_key']}\n";
    echo "API Secret: $plainSecret\n\n";

    $basicAuth = base64_encode("{$seller['api_key']}:$plainSecret");

    echo "=== TESTE DE API ===\n";
    echo "curl -X GET http://localhost:8000/api/pix/list \\\n";
    echo "  -H \"Authorization: Basic $basicAuth\" \\\n";
    echo "  -H \"Content-Type: application/json\"\n\n";

    echo "Ou:\n";
    echo "curl -X GET http://localhost:8000/api/pix/list \\\n";
    echo "  -H \"X-API-Key: {$seller['api_key']}\" \\\n";
    echo "  -H \"X-API-Secret: $plainSecret\" \\\n";
    echo "  -H \"Content-Type: application/json\"\n";

} else {
    echo "\n⚠️  Hash está INCORRETO!\n";
    echo "Deseja corrigir? (y/n): ";

    $handle = fopen("php://stdin", "r");
    $line = trim(fgets($handle));
    fclose($handle);

    if ($line === 'y' || $line === 'Y') {
        $stmt = $db->prepare("UPDATE sellers SET api_secret = ? WHERE id = ?");
        $stmt->execute([$correctHash, $seller['id']]);

        echo "\n✅ Hash corrigido com sucesso!\n\n";

        echo "=== CREDENCIAIS ATUALIZADAS ===\n";
        echo "API Key:    {$seller['api_key']}\n";
        echo "API Secret: $plainSecret\n";
        echo "Hash:       $correctHash\n\n";

        $basicAuth = base64_encode("{$seller['api_key']}:$plainSecret");

        echo "=== TESTE DE API ===\n";
        echo "curl -X GET http://localhost:8000/api/pix/list \\\n";
        echo "  -H \"Authorization: Basic $basicAuth\" \\\n";
        echo "  -H \"Content-Type: application/json\"\n";
    } else {
        echo "\n❌ Correção cancelada.\n";
    }
}
