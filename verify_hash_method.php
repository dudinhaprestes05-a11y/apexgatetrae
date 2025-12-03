<?php

require_once __DIR__ . '/app/config/database.php';

echo "=== VERIFICAÇÃO DO MÉTODO DE HASH ===\n\n";

$db = db();
$stmt = $db->query("SELECT id, email, api_secret FROM sellers WHERE email = 'seller@demo.com' LIMIT 1");
$seller = $stmt->fetch();

if (!$seller) {
    echo "❌ Seller não encontrado!\n";
    exit(1);
}

echo "✅ Seller encontrado\n";
echo "Hash no banco: {$seller['api_secret']}\n";
echo "Comprimento: " . strlen($seller['api_secret']) . " caracteres\n\n";

$plainSecret = 'demo_secret_key_987654321';

echo "=== TESTANDO DIFERENTES MÉTODOS ===\n\n";

// Teste 1: SHA256 simples (hash)
$sha256 = hash('sha256', $plainSecret);
echo "1. SHA256 (hash):\n";
echo "   Resultado: $sha256\n";
echo "   Match: " . ($seller['api_secret'] === $sha256 ? '✅ SIM' : '❌ NÃO') . "\n\n";

// Teste 2: HMAC-SHA256 com chave vazia
$hmac_empty = hash_hmac('sha256', $plainSecret, '');
echo "2. HMAC-SHA256 (chave vazia):\n";
echo "   Resultado: $hmac_empty\n";
echo "   Match: " . ($seller['api_secret'] === $hmac_empty ? '✅ SIM' : '❌ NÃO') . "\n\n";

// Teste 3: HMAC-SHA256 com o próprio secret como chave
$hmac_self = hash_hmac('sha256', $plainSecret, $plainSecret);
echo "3. HMAC-SHA256 (secret como chave):\n";
echo "   Resultado: $hmac_self\n";
echo "   Match: " . ($seller['api_secret'] === $hmac_self ? '✅ SIM' : '❌ NÃO') . "\n\n";

// Teste 4: HMAC-SHA256 com chave fixa comum
$commonKeys = ['secret', 'key', 'app_secret', APP_NAME ?? 'gateway'];
foreach ($commonKeys as $key) {
    $hmac = hash_hmac('sha256', $plainSecret, $key);
    echo "4. HMAC-SHA256 (chave: '$key'):\n";
    echo "   Resultado: $hmac\n";
    echo "   Match: " . ($seller['api_secret'] === $hmac ? '✅ SIM' : '❌ NÃO') . "\n\n";
}

// Verificar onde o secret foi criado/atualizado
echo "=== CÓDIGO ATUAL DE CRIAÇÃO ===\n\n";

echo "Verificando Seller.php linha 81:\n";
$testSecret = bin2hex(random_bytes(32));
$sellerModelHash = hash('sha256', $testSecret);
echo "Método usado: hash('sha256', \$secret)\n";
echo "Exemplo: $sellerModelHash\n\n";

echo "=== QUAL MÉTODO ESTÁ SENDO USADO? ===\n\n";

if ($seller['api_secret'] === $sha256) {
    echo "✅ CONFIRMADO: Usando SHA256 simples (hash)\n";
    echo "O código de autenticação está CORRETO.\n";
} elseif ($seller['api_secret'] === $hmac_empty) {
    echo "✅ CONFIRMADO: Usando HMAC-SHA256 com chave vazia\n";
    echo "Preciso ajustar o código para usar hash_hmac com chave vazia.\n";
} else {
    echo "⚠️  NENHUM MÉTODO COMUM BATEU!\n\n";
    echo "Por favor, informe:\n";
    echo "1. Como você criou o seller? (via interface web ou SQL direto?)\n";
    echo "2. Qual é a chave HMAC que deveria ser usada?\n";
    echo "3. Ou execute este comando para ver como o secret foi gerado:\n";
    echo "   SELECT api_secret FROM sellers WHERE id = {$seller['id']};\n\n";

    echo "Hash esperado (SHA256): $sha256\n";
    echo "Hash no banco:          {$seller['api_secret']}\n";
}
