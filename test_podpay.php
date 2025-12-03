<?php

require_once __DIR__ . '/app/config/database.php';
require_once __DIR__ . '/app/models/Acquirer.php';
require_once __DIR__ . '/app/services/PodPayService.php';

header('Content-Type: application/json');

try {
    $acquirerModel = new Acquirer();
    $acquirer = $acquirerModel->findByCode('podpay');

    if (!$acquirer) {
        echo json_encode([
            'error' => 'Acquirer PodPay não encontrado no banco de dados',
            'solution' => 'Execute o SQL de configuração primeiro'
        ], JSON_PRETTY_PRINT);
        exit;
    }

    echo "=== CONFIGURAÇÃO DO PODPAY ===\n\n";
    echo "Nome: {$acquirer['name']}\n";
    echo "Status: {$acquirer['status']}\n";
    echo "API URL: {$acquirer['api_url']}\n";
    echo "API Key configurada: " . (!empty($acquirer['api_key']) ? 'Sim' : 'Não') . "\n";
    echo "API Secret configurada: " . (!empty($acquirer['api_secret']) ? 'Sim' : 'Não') . "\n\n";

    if (empty($acquirer['api_key']) || empty($acquirer['api_secret'])) {
        echo "❌ ERRO: Credenciais não configuradas!\n";
        echo "Execute o SQL de configuração com suas credenciais.\n";
        exit;
    }

    echo "=== TESTANDO CRIAÇÃO DE TRANSAÇÃO ===\n\n";

    $podpay = new PodPayService($acquirer);

    $testData = [
        'transaction_id' => 'TEST_' . time(),
        'amount' => 10.00,
        'title' => 'Teste de Integração',
        'customer' => [
            'name' => 'Teste Cliente',
            'email' => 'teste@example.com',
            'document' => '12345678900'
        ]
    ];

    echo "Enviando requisição para PodPay...\n";
    echo "Endpoint: {$acquirer['api_url']}/v1/transactions\n\n";

    $result = $podpay->createPixTransaction($testData);

    echo "=== RESULTADO ===\n\n";
    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    echo "\n\n";

    if ($result['success']) {
        echo "✅ SUCESSO! A integração está funcionando.\n";
    } else {
        echo "❌ ERRO: {$result['error']}\n\n";
        echo "Possíveis causas:\n";
        echo "1. URL da API incorreta\n";
        echo "2. Credenciais inválidas\n";
        echo "3. IP bloqueado no PodPay\n";
        echo "4. Endpoint mudou ou está em manutenção\n\n";
        echo "Teste manualmente:\n";
        echo "curl -X POST {$acquirer['api_url']}/v1/transactions \\\n";
        echo "  -u '{$acquirer['api_key']}:SEU_SECRET' \\\n";
        echo "  -H 'Content-Type: application/json' \\\n";
        echo "  -d '{\n";
        echo '    "amount": 1000,' . "\n";
        echo '    "currency": "BRL",' . "\n";
        echo '    "paymentMethod": "pix",' . "\n";
        echo '    "items": [{"title": "Teste", "unitPrice": 1000, "quantity": 1, "tangible": false}],' . "\n";
        echo '    "customer": {' . "\n";
        echo '      "name": "Teste",' . "\n";
        echo '      "email": "teste@example.com",' . "\n";
        echo '      "document": {"number": "12345678900", "type": "cpf"}' . "\n";
        echo '    },' . "\n";
        echo '    "postbackUrl": "' . BASE_URL . '/api/webhook/acquirer?acquirer=podpay"' . "\n";
        echo "  }'\n";
    }

} catch (Exception $e) {
    echo json_encode([
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ], JSON_PRETTY_PRINT);
}
