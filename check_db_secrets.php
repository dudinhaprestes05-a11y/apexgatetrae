<?php
require_once __DIR__ . '/app/config/database.php';

echo "=== VERIFICAÇÃO DE SECRETS NO BANCO ===\n\n";

$db = db();

// Busca todos os sellers
$stmt = $db->query("SELECT id, name, api_key, api_secret, created_at FROM sellers ORDER BY id");
$sellers = $stmt->fetchAll();

if (empty($sellers)) {
    echo "Nenhum seller encontrado no banco!\n";
    exit(1);
}

echo "Total de sellers: " . count($sellers) . "\n\n";

foreach ($sellers as $seller) {
    echo "──────────────────────────────────────────\n";
    echo "ID: {$seller['id']}\n";
    echo "Nome: {$seller['name']}\n";
    echo "API Key: {$seller['api_key']}\n";
    echo "Criado em: {$seller['created_at']}\n";
    echo "\n";

    $secret = $seller['api_secret'];
    
    echo "API Secret no banco:\n";
    echo "  Valor: {$secret}\n";
    echo "  Tamanho: " . strlen($secret) . " caracteres\n";
    echo "  Primeiros 16 chars: " . substr($secret, 0, 16) . "...\n";
    echo "  Últimos 16 chars: ..." . substr($secret, -16) . "\n";
    echo "  É hexadecimal: " . (ctype_xdigit($secret) ? '✅ SIM' : '❌ NÃO') . "\n";
    echo "  É SHA256 válido: " . (strlen($secret) === 64 && ctype_xdigit($secret) ? '✅ SIM' : '❌ NÃO') . "\n";
    
    // Testa se o secret parece estar correto
    if (strlen($secret) === 64 && ctype_xdigit($secret)) {
        echo "  Status: ✅ Formato correto (SHA256)\n";
    } else {
        echo "  Status: ❌ Formato incorreto!\n";
        
        if (strlen($secret) === 0) {
            echo "  Problema: Secret vazio\n";
        } else if (strlen($secret) !== 64) {
            echo "  Problema: Tamanho incorreto (esperado 64 chars)\n";
        } else if (!ctype_xdigit($secret)) {
            echo "  Problema: Contém caracteres não hexadecimais\n";
        }
    }
    
    echo "\n";
}

echo "──────────────────────────────────────────\n";
echo "\n=== RESUMO ===\n";
echo "Sellers com secrets corretos: " . array_reduce($sellers, function($count, $s) {
    return $count + (strlen($s['api_secret']) === 64 && ctype_xdigit($s['api_secret']) ? 1 : 0);
}, 0) . " / " . count($sellers) . "\n";
