<?php

require_once __DIR__ . '/app/config/database.php';

echo "=== REGENERAÇÃO DE CREDENCIAIS sk_live_ ===\n\n";

// Buscar o seller com sk_live_
$db = db();
$stmt = $db->query("SELECT id, name, email, api_key, api_secret FROM sellers WHERE api_key LIKE 'sk_live_%' LIMIT 1");
$seller = $stmt->fetch();

if (!$seller) {
    echo "❌ Nenhum seller com sk_live_ encontrado!\n";
    exit;
}

echo "Seller encontrado:\n";
echo "  ID: {$seller['id']}\n";
echo "  Nome: {$seller['name']}\n";
echo "  Email: {$seller['email']}\n";
echo "  API Key atual: {$seller['api_key']}\n\n";

// Gerar novo secret
$newSecret = 'live_secret_' . bin2hex(random_bytes(20));
$newHash = hash('sha256', $newSecret);

echo "✅ Novas credenciais geradas:\n\n";
echo "┌─────────────────────────────────────────────────────────────┐\n";
echo "│ ENVIE ESTAS CREDENCIAIS PARA O CLIENTE:                    │\n";
echo "├─────────────────────────────────────────────────────────────┤\n";
echo "│ API Key:    {$seller['api_key']}\n";
echo "│ API Secret: $newSecret\n";
echo "└─────────────────────────────────────────────────────────────┘\n\n";

echo "⚠️  IMPORTANTE:\n";
echo "   - Envie o API SECRET acima (texto plano) para o cliente\n";
echo "   - NÃO envie o hash que está no banco\n";
echo "   - O cliente deve usar o secret em texto plano nas requisições\n\n";

// Atualizar no banco
$stmt = $db->prepare("UPDATE sellers SET api_secret = ? WHERE id = ?");
$stmt->execute([$newHash, $seller['id']]);

echo "✅ Hash atualizado no banco de dados!\n\n";

echo "Hash armazenado (interno, não compartilhar): $newHash\n\n";

echo "=== EXEMPLOS DE USO ===\n\n";

$basicAuth = base64_encode("{$seller['api_key']}:$newSecret");

echo "1. Basic Authentication:\n";
echo "curl -X POST 'https://seu-dominio.com/api/pix/create' \\\n";
echo "  -H 'Authorization: Basic $basicAuth' \\\n";
echo "  -H 'Content-Type: application/json' \\\n";
echo "  -d '{\n";
echo "    \"amount\": 100.00,\n";
echo "    \"cpf_cnpj\": \"12345678901\",\n";
echo "    \"name\": \"João Silva\"\n";
echo "  }'\n\n";

echo "2. Headers Separados:\n";
echo "curl -X POST 'https://seu-dominio.com/api/pix/create' \\\n";
echo "  -H 'X-API-Key: {$seller['api_key']}' \\\n";
echo "  -H 'X-API-Secret: $newSecret' \\\n";
echo "  -H 'Content-Type: application/json' \\\n";
echo "  -d '{\n";
echo "    \"amount\": 100.00,\n";
echo "    \"cpf_cnpj\": \"12345678901\",\n";
echo "    \"name\": \"João Silva\"\n";
echo "  }'\n\n";

echo "3. PHP:\n";
echo "<?php\n";
echo "\$ch = curl_init('https://seu-dominio.com/api/pix/create');\n";
echo "curl_setopt(\$ch, CURLOPT_HTTPHEADER, [\n";
echo "    'X-API-Key: {$seller['api_key']}',\n";
echo "    'X-API-Secret: $newSecret',\n";
echo "    'Content-Type: application/json'\n";
echo "]);\n";
echo "curl_setopt(\$ch, CURLOPT_POST, true);\n";
echo "curl_setopt(\$ch, CURLOPT_POSTFIELDS, json_encode([\n";
echo "    'amount' => 100.00,\n";
echo "    'cpf_cnpj' => '12345678901',\n";
echo "    'name' => 'João Silva'\n";
echo "]));\n";
echo "curl_setopt(\$ch, CURLOPT_RETURNTRANSFER, true);\n";
echo "\$response = curl_exec(\$ch);\n";
echo "?>\n\n";

echo "✅ Pronto! Envie as credenciais acima para o cliente.\n";
