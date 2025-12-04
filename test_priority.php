<?php
require_once __DIR__ . '/app/config/config.php';
require_once __DIR__ . '/app/config/database.php';
require_once __DIR__ . '/app/models/SellerAcquirerAccount.php';
require_once __DIR__ . '/app/services/AcquirerService.php';

try {
    echo "=== TESTE DE PRIORIDADE DAS CONTAS ===\n\n";

    // Get seller ID from command line or use default
    $sellerId = $argv[1] ?? 1;
    echo "Testando para Seller ID: {$sellerId}\n\n";

    $sellerAccountModel = new SellerAcquirerAccount();
    $acquirerService = new AcquirerService();

    // Get all seller accounts with details
    echo "1. CONTAS CONFIGURADAS (ordenadas por prioridade):\n";
    echo str_repeat("-", 80) . "\n";

    $accounts = $sellerAccountModel->getBySellerWithDetails($sellerId);

    if (empty($accounts)) {
        echo "⚠ Nenhuma conta configurada para este seller\n";
        echo "\nPara configurar contas, use o painel administrativo em:\n";
        echo "Admin > Sellers > Ver Detalhes > Gerenciar Contas\n";
        exit;
    }

    foreach ($accounts as $account) {
        $status = $account['is_active'] ? '✓ Ativa' : '✗ Inativa';
        $accountStatus = $account['account_active'] ? '✓' : '✗';

        echo sprintf(
            "Prioridade %d: [%s] %s - %s (%s)\n",
            $account['priority'],
            $status,
            $account['acquirer_name'],
            $account['account_name'],
            $account['account_identifier'] ?? 'N/A'
        );
        echo "  - ID da Conta: {$account['acquirer_account_id']}\n";
        echo "  - Status da Conta: {$accountStatus}\n";
        echo "  - Saldo: R$ " . number_format($account['account_balance'], 2, ',', '.') . "\n\n";
    }

    // Test account selection order
    echo "\n2. TESTE DE SELEÇÃO (simulando transação de R$ 100,00):\n";
    echo str_repeat("-", 80) . "\n";

    $amount = 100.00;
    $excludeIds = [];

    for ($i = 1; $i <= 5; $i++) {
        echo "\nTentativa {$i}:\n";

        $account = $acquirerService->selectAccountForSeller($sellerId, $amount, 'cashin', $excludeIds);

        if (!$account) {
            echo "  ❌ Nenhuma conta disponível\n";
            break;
        }

        echo "  ✓ Conta selecionada: {$account['name']} (ID: {$account['id']})\n";
        echo "    - Adquirente: {$account['acquirer_name']}\n";
        echo "    - Identificador: " . ($account['account_identifier'] ?? 'N/A') . "\n";

        // Exclude this account for next iteration
        $excludeIds[] = $account['id'];
    }

    // Test with different amounts
    echo "\n\n3. TESTE COM DIFERENTES VALORES:\n";
    echo str_repeat("-", 80) . "\n";

    $testAmounts = [10, 50, 100, 500, 1000, 5000];

    foreach ($testAmounts as $testAmount) {
        $account = $acquirerService->selectAccountForSeller($sellerId, $testAmount, 'cashin', []);

        if ($account) {
            echo sprintf(
                "R$ %s: %s (ID: %d)\n",
                number_format($testAmount, 2, ',', '.'),
                $account['name'],
                $account['id']
            );
        } else {
            echo sprintf(
                "R$ %s: ❌ Nenhuma conta disponível\n",
                number_format($testAmount, 2, ',', '.')
            );
        }
    }

    echo "\n\n4. VERIFICAÇÃO DA ORDEM DOS IDs:\n";
    echo str_repeat("-", 80) . "\n";

    $accountIds = $sellerAccountModel->getAccountsBySeller($sellerId);
    echo "IDs na ordem de prioridade: [" . implode(', ', $accountIds) . "]\n";

    echo "\nSe os IDs estão em ordem crescente de prioridade (1, 2, 3...), o sistema está funcionando!\n";

    echo "\n\n✅ Teste concluído!\n\n";

    echo "DICAS:\n";
    echo "- A conta com menor número de prioridade (1) é usada primeiro\n";
    echo "- Se uma conta falhar, a próxima na ordem de prioridade é usada\n";
    echo "- Contas inativas não são selecionadas\n";
    echo "- Para alterar a ordem, use o painel admin: Admin > Sellers > Ver Detalhes > Gerenciar Contas\n";

} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
}
