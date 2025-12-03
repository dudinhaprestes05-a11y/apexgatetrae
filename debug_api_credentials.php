<?php
// REMOVER ESTE ARQUIVO EM PRODUÇÃO!
require_once __DIR__ . '/app/config/database.php';

header('Content-Type: text/plain; charset=utf-8');

echo "=== DEBUG DE CREDENCIAIS API ===\n\n";

// Verifica se foi passado um seller_id
$sellerId = $_GET['seller_id'] ?? null;

if (!$sellerId) {
    echo "Uso: debug_api_credentials.php?seller_id=X\n";
    echo "ou: debug_api_credentials.php?seller_id=X&test_secret=SEU_SECRET\n\n";
    
    // Lista todos os sellers
    $db = db();
    $stmt = $db->query("SELECT id, name, api_key FROM sellers ORDER BY id");
    $sellers = $stmt->fetchAll();
    
    echo "Sellers disponíveis:\n";
    foreach ($sellers as $s) {
        echo "  ID {$s['id']}: {$s['name']} (Key: " . substr($s['api_key'], 0, 12) . "...)\n";
    }
    exit;
}

$db = db();
$stmt = $db->prepare("SELECT id, name, email, api_key, api_secret, status, created_at FROM sellers WHERE id = ?");
$stmt->execute([$sellerId]);
$seller = $stmt->fetch();

if (!$seller) {
    echo "Seller ID {$sellerId} não encontrado!\n";
    exit;
}

echo "Seller ID: {$seller['id']}\n";
echo "Nome: {$seller['name']}\n";
echo "Email: {$seller['email']}\n";
echo "Status: {$seller['status']}\n";
echo "Criado em: {$seller['created_at']}\n";
echo "\n";

echo "API Key:\n";
echo "  {$seller['api_key']}\n\n";

$dbSecret = $seller['api_secret'];
echo "API Secret no Banco (HASH SHA256):\n";
echo "  Valor: {$dbSecret}\n";
echo "  Tamanho: " . strlen($dbSecret) . " chars\n";
echo "  Primeiros 16: " . substr($dbSecret, 0, 16) . "\n";
echo "  Últimos 16: " . substr($dbSecret, -16) . "\n";
echo "  É hex válido: " . (ctype_xdigit($dbSecret) ? 'SIM' : 'NÃO') . "\n";
echo "  É SHA256 válido: " . (strlen($dbSecret) === 64 && ctype_xdigit($dbSecret) ? 'SIM' : 'NÃO') . "\n";
echo "\n";

// Se foi passado um secret para teste
$testSecret = $_GET['test_secret'] ?? null;

if ($testSecret) {
    echo "──────────────────────────────────────\n";
    echo "TESTE DE AUTENTICAÇÃO\n";
    echo "──────────────────────────────────────\n\n";
    
    echo "Secret que você está tentando usar:\n";
    echo "  Valor: {$testSecret}\n";
    echo "  Tamanho: " . strlen($testSecret) . " chars\n";
    echo "  Primeiros 16: " . substr($testSecret, 0, 16) . "\n";
    echo "  Últimos 16: " . substr($testSecret, -16) . "\n";
    echo "  É hex válido: " . (ctype_xdigit($testSecret) ? 'SIM' : 'NÃO') . "\n";
    echo "\n";
    
    $hashedTest = hash('sha256', $testSecret);
    echo "Hash SHA256 do secret enviado:\n";
    echo "  Valor: {$hashedTest}\n";
    echo "  Tamanho: " . strlen($hashedTest) . " chars\n";
    echo "  Primeiros 16: " . substr($hashedTest, 0, 16) . "\n";
    echo "  Últimos 16: " . substr($hashedTest, -16) . "\n";
    echo "\n";
    
    echo "──────────────────────────────────────\n";
    echo "COMPARAÇÃO\n";
    echo "──────────────────────────────────────\n";
    echo "Hash do banco:  {$dbSecret}\n";
    echo "Hash do teste:  {$hashedTest}\n";
    echo "São iguais?     " . ($dbSecret === $hashedTest ? '✅ SIM - AUTENTICAÇÃO OK!' : '❌ NÃO - FALHA!') . "\n";
    echo "\n";
    
    if ($dbSecret !== $hashedTest) {
        echo "DIAGNÓSTICO:\n";
        
        // Verifica se está enviando o hash ao invés do texto plano
        if ($testSecret === $dbSecret) {
            echo "❌ PROBLEMA ENCONTRADO: Você está enviando o HASH ao invés do secret em texto plano!\n";
            echo "   Solução: Use o secret exatamente como foi mostrado após regenerar.\n";
        }
        // Verifica se o banco tem texto plano
        else if ($dbSecret === $testSecret) {
            echo "❌ PROBLEMA ENCONTRADO: O banco tem o secret em texto plano ao invés do hash!\n";
            echo "   Solução: Regenere as credenciais no painel.\n";
        }
        else {
            echo "❌ Os secrets não batem. Possíveis causas:\n";
            echo "   1. Você não está usando o secret correto\n";
            echo "   2. O secret foi copiado incorretamente (espaços, quebras de linha)\n";
            echo "   3. As credenciais foram regeneradas e você está usando as antigas\n";
            echo "\n";
            echo "   Solução: Regenere as credenciais no painel e copie o novo secret.\n";
        }
    }
} else {
    echo "Para testar um secret, adicione: &test_secret=SEU_SECRET_AQUI\n";
}

echo "\n";
echo "──────────────────────────────────────\n";
echo "ATENÇÃO: REMOVA ESTE ARQUIVO EM PRODUÇÃO!\n";
echo "──────────────────────────────────────\n";
