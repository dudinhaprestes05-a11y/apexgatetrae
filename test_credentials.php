<?php
require_once __DIR__ . '/app/config/database.php';

echo "=== TESTE DE CREDENCIAIS ===\n\n";

// Pega o seller_id da linha de comando
$sellerId = $argv[1] ?? null;

if (!$sellerId) {
    echo "Uso: php test_credentials.php [seller_id]\n";
    exit(1);
}

$db = db();

// Busca o seller
$stmt = $db->prepare("SELECT id, name, api_key, api_secret FROM sellers WHERE id = ?");
$stmt->execute([$sellerId]);
$seller = $stmt->fetch();

if (!$seller) {
    echo "Seller não encontrado!\n";
    exit(1);
}

echo "Seller ID: {$seller['id']}\n";
echo "Nome: {$seller['name']}\n";
echo "API Key: {$seller['api_key']}\n";
echo "\n";

// Informações sobre o secret armazenado
$storedSecret = $seller['api_secret'];
echo "=== SECRET NO BANCO ===\n";
echo "Valor: {$storedSecret}\n";
echo "Tamanho: " . strlen($storedSecret) . " caracteres\n";
echo "Primeiros 8 chars: " . substr($storedSecret, 0, 8) . "\n";
echo "Últimos 8 chars: " . substr($storedSecret, -8) . "\n";
echo "É hexadecimal: " . (ctype_xdigit($storedSecret) ? 'SIM' : 'NÃO') . "\n";
echo "É SHA256 válido: " . (strlen($storedSecret) === 64 && ctype_xdigit($storedSecret) ? 'SIM' : 'NÃO') . "\n";
echo "\n";

// Teste com secret exemplo
echo "=== TESTE DE VALIDAÇÃO ===\n";
echo "Digite o API Secret que você está tentando usar:\n";
$testSecret = trim(fgets(STDIN));

if (empty($testSecret)) {
    echo "Secret vazio!\n";
    exit(1);
}

echo "\n=== SECRET ENVIADO ===\n";
echo "Valor: {$testSecret}\n";
echo "Tamanho: " . strlen($testSecret) . " caracteres\n";
echo "Primeiros 8 chars: " . substr($testSecret, 0, 8) . "\n";
echo "Últimos 8 chars: " . substr($testSecret, -8) . "\n";
echo "É hexadecimal: " . (ctype_xdigit($testSecret) ? 'SIM' : 'NÃO') . "\n";
echo "\n";

// Gera o hash do secret enviado
$hashedSecret = hash('sha256', $testSecret);

echo "=== HASH DO SECRET ENVIADO ===\n";
echo "Valor: {$hashedSecret}\n";
echo "Tamanho: " . strlen($hashedSecret) . " caracteres\n";
echo "Primeiros 8 chars: " . substr($hashedSecret, 0, 8) . "\n";
echo "Últimos 8 chars: " . substr($hashedSecret, -8) . "\n";
echo "\n";

// Comparação
echo "=== COMPARAÇÃO ===\n";
echo "Secret no banco:      {$storedSecret}\n";
echo "Hash do enviado:      {$hashedSecret}\n";
echo "São iguais:           " . ($storedSecret === $hashedSecret ? '✅ SIM' : '❌ NÃO') . "\n";
echo "Trim são iguais:      " . (trim($storedSecret) === trim($hashedSecret) ? '✅ SIM' : '❌ NÃO') . "\n";
echo "\n";

if ($storedSecret !== $hashedSecret) {
    echo "=== ANÁLISE DO PROBLEMA ===\n";

    // Verifica se o secret no banco é o texto plano (erro comum)
    $hashOfStoredSecret = hash('sha256', $storedSecret);
    echo "Hash do secret armazenado: {$hashOfStoredSecret}\n";
    echo "Secret enviado:            {$testSecret}\n";
    echo "\nO secret no banco é texto plano?: " . ($storedSecret === $testSecret ? '✅ SIM (PROBLEMA!)' : '❌ NÃO') . "\n";

    // Verifica se estão enviando o hash ao invés do texto plano
    echo "Está enviando o hash ao invés do texto plano?: " . ($testSecret === $storedSecret ? '✅ SIM (PROBLEMA!)' : '❌ NÃO') . "\n";

    // Diferença de caracteres
    echo "\n=== DIFERENÇAS BYTE-A-BYTE ===\n";
    $minLen = min(strlen($storedSecret), strlen($hashedSecret));
    $diffCount = 0;
    for ($i = 0; $i < $minLen; $i++) {
        if ($storedSecret[$i] !== $hashedSecret[$i]) {
            $diffCount++;
            if ($diffCount <= 5) {
                echo "Posição {$i}: banco='{$storedSecret[$i]}' (ASCII " . ord($storedSecret[$i]) . ") vs hash='{$hashedSecret[$i]}' (ASCII " . ord($hashedSecret[$i]) . ")\n";
            }
        }
    }
    echo "Total de diferenças: {$diffCount}\n";
} else {
    echo "✅ ✅ ✅ CREDENCIAIS CORRETAS! ✅ ✅ ✅\n";
}
