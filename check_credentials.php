<?php

require_once __DIR__ . '/app/config/database.php';

echo "=== DIAGNÓSTICO DE CREDENCIAIS ===\n\n";

$db = db();

// Buscar todos os sellers ativos
$stmt = $db->query("SELECT id, name, email, api_key, api_secret, status FROM sellers WHERE status = 'active' ORDER BY id");
$sellers = $stmt->fetchAll();

if (empty($sellers)) {
    echo "❌ Nenhum seller ativo encontrado!\n";
    exit;
}

echo "Sellers ativos encontrados: " . count($sellers) . "\n\n";

foreach ($sellers as $seller) {
    echo "========================================\n";
    echo "Seller ID: {$seller['id']}\n";
    echo "Nome: {$seller['name']}\n";
    echo "Email: {$seller['email']}\n";
    echo "Status: {$seller['status']}\n\n";

    echo "API Key: {$seller['api_key']}\n";
    echo "API Secret (hash no banco): {$seller['api_secret']}\n";
    echo "Tamanho do hash: " . strlen($seller['api_secret']) . " caracteres\n\n";

    // Verificar se começa com sk_live
    if (strpos($seller['api_key'], 'sk_live_') === 0) {
        echo "⚠️  ATENÇÃO: Este é um seller de PRODUÇÃO (sk_live_)\n\n";

        // O hash atual no banco
        $currentHash = $seller['api_secret'];

        echo "⚠️  PROBLEMA IDENTIFICADO:\n";
        echo "   O cliente está enviando: $currentHash (64 chars - é um HASH)\n";
        echo "   O sistema espera: O SECRET EM TEXTO PLANO (não o hash)\n\n";

        echo "📋 SOLUÇÃO:\n";
        echo "   Você precisa fornecer ao cliente o SECRET ORIGINAL em texto plano,\n";
        echo "   NÃO o hash que está no banco.\n\n";

        echo "   Se você não tem o secret original, você precisa:\n";
        echo "   1. Gerar um novo secret\n";
        echo "   2. Fazer o hash dele\n";
        echo "   3. Atualizar no banco\n";
        echo "   4. Enviar o secret ORIGINAL (não o hash) para o cliente\n\n";

        // Gerar um novo secret como exemplo
        $newSecret = 'live_secret_' . bin2hex(random_bytes(20));
        $newHash = hash('sha256', $newSecret);

        echo "💡 EXEMPLO DE NOVO SECRET:\n";
        echo "   Secret (enviar para o cliente): $newSecret\n";
        echo "   Hash (guardar no banco): $newHash\n\n";

        echo "🔧 Para atualizar este seller com novo secret:\n";
        echo "   UPDATE sellers SET api_secret = '$newHash' WHERE id = {$seller['id']};\n\n";

        echo "📝 Credenciais para enviar ao cliente:\n";
        echo "   API Key:    {$seller['api_key']}\n";
        echo "   API Secret: $newSecret\n\n";

        echo "✅ Comando curl de teste:\n";
        $basicAuth = base64_encode("{$seller['api_key']}:$newSecret");
        echo "curl -X GET 'http://localhost:8000/api/pix/list' \\\n";
        echo "  -H 'Authorization: Basic $basicAuth'\n\n";

        echo "ou com headers separados:\n";
        echo "curl -X GET 'http://localhost:8000/api/pix/list' \\\n";
        echo "  -H 'X-API-Key: {$seller['api_key']}' \\\n";
        echo "  -H 'X-API-Secret: $newSecret'\n\n";
    } else {
        echo "ℹ️  Este é um seller de teste/demo\n\n";
    }

    echo "========================================\n\n";
}

echo "=== RESUMO DO PROBLEMA ===\n";
echo "O cliente está enviando o HASH ao invés do SECRET em texto plano.\n";
echo "O sistema funciona assim:\n";
echo "  1. Cliente envia: api_key + api_secret (TEXTO PLANO)\n";
echo "  2. Sistema faz: hash('sha256', api_secret)\n";
echo "  3. Sistema compara: hash gerado == hash do banco\n\n";
echo "Se o cliente enviar o hash, o sistema vai fazer hash(hash) e não vai bater!\n\n";
