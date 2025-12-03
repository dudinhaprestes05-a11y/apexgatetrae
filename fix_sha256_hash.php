<?php

require_once __DIR__ . '/app/config/database.php';

echo "=== CORREÇÃO PARA SHA256 SIMPLES ===\n\n";

$db = db();

// Buscar seller demo
$stmt = $db->query("SELECT id, name, email, api_key, api_secret, status FROM sellers WHERE email = 'seller@demo.com' LIMIT 1");
$seller = $stmt->fetch();

if (!$seller) {
    echo "❌ Seller demo não encontrado. Criando...\n\n";

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

    // Criar usuário
    $stmt = $db->prepare("
        INSERT INTO users (seller_id, name, email, password, role, status)
        VALUES (?, 'Seller Demo User', 'seller@demo.com', ?, 'seller', 'active')
    ");
    $password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';
    $stmt->execute([$sellerId, $password]);

    echo "✅ Seller criado com sucesso!\n";
    echo "   Email: seller@demo.com\n";
    echo "   Senha: password\n\n";

    $seller = [
        'id' => $sellerId,
        'api_key' => $apiKey,
        'api_secret' => $hashedSecret
    ];
}

$plainSecret = 'demo_secret_key_987654321';
$correctHash = hash('sha256', $plainSecret);

echo "Seller ID: {$seller['id']}\n";
echo "API Key: {$seller['api_key']}\n\n";

echo "=== VERIFICAÇÃO DE HASH ===\n";
echo "Método: SHA256 simples (hash)\n";
echo "Secret em texto: $plainSecret\n";
echo "Hash esperado:   $correctHash\n";
echo "Hash no banco:   {$seller['api_secret']}\n";
echo "Match: " . ($seller['api_secret'] === $correctHash ? '✅ SIM' : '❌ NÃO') . "\n\n";

if ($seller['api_secret'] !== $correctHash) {
    echo "⚠️  Hash incorreto! Corrigindo...\n";

    $stmt = $db->prepare("UPDATE sellers SET api_secret = ? WHERE id = ?");
    $stmt->execute([$correctHash, $seller['id']]);

    echo "✅ Hash corrigido com sucesso!\n\n";
}

echo "=== CREDENCIAIS FINAIS ===\n";
echo "API Key:    {$seller['api_key']}\n";
echo "API Secret: $plainSecret\n";
echo "Hash:       $correctHash\n\n";

$basicAuth = base64_encode("{$seller['api_key']}:$plainSecret");

echo "=== TESTE DE API ===\n\n";
echo "Método 1 - Basic Authentication:\n";
echo "curl -X GET 'http://localhost:8000/api/pix/list' \\\n";
echo "  -H 'Authorization: Basic $basicAuth' \\\n";
echo "  -H 'Content-Type: application/json'\n\n";

echo "Método 2 - Headers Separados:\n";
echo "curl -X GET 'http://localhost:8000/api/pix/list' \\\n";
echo "  -H 'X-API-Key: {$seller['api_key']}' \\\n";
echo "  -H 'X-API-Secret: $plainSecret' \\\n";
echo "  -H 'Content-Type: application/json'\n\n";

echo "✅ Configuração completa! Sistema usando SHA256 simples.\n";
