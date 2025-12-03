<?php
// ARQUIVO TEMPORÁRIO - REMOVER APÓS DEBUG
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: text/plain; charset=utf-8');

echo "=== VERIFICAÇÃO DE SECRETS ===\n\n";

try {
    // Tenta carregar a configuração do banco
    if (!file_exists(__DIR__ . '/app/config/database.php')) {
        die("ERRO: Arquivo database.php não encontrado!\n");
    }
    
    require_once __DIR__ . '/app/config/database.php';
    
    $db = db();
    
    if (!$db) {
        die("ERRO: Não foi possível conectar ao banco de dados!\n");
    }
    
    echo "✅ Conectado ao banco de dados\n\n";
    
    // Busca todos os sellers
    $stmt = $db->query("SELECT id, name, email, api_key, api_secret, status, created_at FROM sellers ORDER BY id");
    $sellers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($sellers)) {
        echo "⚠️ Nenhum seller encontrado no banco!\n";
        exit;
    }
    
    echo "Total de sellers: " . count($sellers) . "\n";
    echo str_repeat("=", 70) . "\n\n";
    
    foreach ($sellers as $seller) {
        echo "SELLER ID: {$seller['id']}\n";
        echo "Nome: {$seller['name']}\n";
        echo "Email: {$seller['email']}\n";
        echo "Status: {$seller['status']}\n";
        echo "Criado em: {$seller['created_at']}\n";
        echo "\n";
        
        echo "API Key:\n";
        echo "  {$seller['api_key']}\n";
        echo "  Tamanho: " . strlen($seller['api_key']) . " chars\n";
        echo "\n";
        
        $secret = $seller['api_secret'];
        echo "API Secret (hash no banco):\n";
        echo "  Valor: {$secret}\n";
        echo "  Tamanho: " . strlen($secret) . " chars\n";
        echo "  Primeiros 16: " . substr($secret, 0, 16) . "...\n";
        echo "  Últimos 16: ..." . substr($secret, -16) . "\n";
        echo "  É hexadecimal: " . (ctype_xdigit($secret) ? '✅ SIM' : '❌ NÃO') . "\n";
        echo "  É SHA256 válido: " . (strlen($secret) === 64 && ctype_xdigit($secret) ? '✅ SIM' : '❌ NÃO') . "\n";
        
        if (strlen($secret) !== 64 || !ctype_xdigit($secret)) {
            echo "  ⚠️ PROBLEMA: Secret não está no formato SHA256!\n";
        }
        
        echo "\n";
        echo str_repeat("-", 70) . "\n\n";
    }
    
    // Se foi passado um seller_id e test_secret via GET
    if (isset($_GET['seller_id']) && isset($_GET['test_secret'])) {
        $testSellerId = $_GET['seller_id'];
        $testSecret = $_GET['test_secret'];
        
        echo str_repeat("=", 70) . "\n";
        echo "TESTE DE AUTENTICAÇÃO\n";
        echo str_repeat("=", 70) . "\n\n";
        
        $stmt = $db->prepare("SELECT id, name, api_key, api_secret FROM sellers WHERE id = ?");
        $stmt->execute([$testSellerId]);
        $seller = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$seller) {
            echo "❌ Seller ID {$testSellerId} não encontrado!\n";
        } else {
            echo "Testando seller: {$seller['name']} (ID: {$seller['id']})\n\n";
            
            echo "Secret enviado:\n";
            echo "  {$testSecret}\n";
            echo "  Tamanho: " . strlen($testSecret) . " chars\n\n";
            
            $hashedTest = hash('sha256', $testSecret);
            echo "Hash SHA256 do secret enviado:\n";
            echo "  {$hashedTest}\n\n";
            
            echo "Hash armazenado no banco:\n";
            echo "  {$seller['api_secret']}\n\n";
            
            if ($seller['api_secret'] === $hashedTest) {
                echo "✅✅✅ SUCESSO! Os hashes batem!\n";
                echo "A autenticação deveria funcionar.\n";
            } else {
                echo "❌ FALHA! Os hashes NÃO batem!\n\n";
                
                // Diagnóstico
                if ($testSecret === $seller['api_secret']) {
                    echo "DIAGNÓSTICO: Você está enviando o HASH ao invés do SECRET!\n";
                    echo "SOLUÇÃO: Use o secret em texto plano mostrado no painel.\n";
                } else if ($seller['api_secret'] === hash('sha256', $seller['api_secret'])) {
                    echo "DIAGNÓSTICO: O banco tem texto plano ao invés de hash!\n";
                    echo "SOLUÇÃO: Regenere as credenciais.\n";
                } else {
                    echo "DIAGNÓSTICO: Secret incorreto ou credenciais foram regeneradas.\n";
                    echo "SOLUÇÃO: Regenere as credenciais e copie o novo secret.\n";
                }
            }
        }
    } else if (isset($_GET['seller_id'])) {
        echo "\nPara testar um secret, adicione: &test_secret=SEU_SECRET\n";
        echo "Exemplo: ?seller_id=" . $_GET['seller_id'] . "&test_secret=abc123...\n";
    }
    
} catch (Exception $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

echo "\n\n";
echo "⚠️⚠️⚠️ REMOVA ESTE ARQUIVO EM PRODUÇÃO! ⚠️⚠️⚠️\n";
