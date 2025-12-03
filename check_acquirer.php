<?php
require_once __DIR__ . '/app/config/config.php';
require_once __DIR__ . '/app/config/database.php';

header('Content-Type: application/json');

try {
    $stmt = db()->prepare("SELECT id, name, code, api_url, api_key, status, priority_order FROM acquirers ORDER BY priority_order");
    $stmt->execute();
    $acquirers = $stmt->fetchAll();

    if (empty($acquirers)) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Nenhum acquirer configurado',
            'help' => 'Execute o script sql/insert_podpay_acquirer.sql para configurar o PodPay'
        ], JSON_PRETTY_PRINT);
        exit;
    }

    $results = [];

    foreach ($acquirers as $acquirer) {
        $hasApiUrl = !empty($acquirer['api_url']);
        $hasApiKey = !empty($acquirer['api_key']);

        $issues = [];
        if (!$hasApiUrl) $issues[] = 'API URL não configurada';
        if (!$hasApiKey) $issues[] = 'API Key não configurada';
        if ($acquirer['status'] !== 'active') $issues[] = 'Acquirer não está ativo';

        $results[] = [
            'id' => $acquirer['id'],
            'name' => $acquirer['name'],
            'code' => $acquirer['code'],
            'status' => $acquirer['status'],
            'priority' => $acquirer['priority_order'],
            'api_url' => $acquirer['api_url'] ?: 'NÃO CONFIGURADA',
            'api_key_configured' => $hasApiKey ? 'Sim' : 'NÃO',
            'configuration_ok' => empty($issues),
            'issues' => $issues
        ];
    }

    echo json_encode([
        'status' => 'success',
        'acquirers' => $results,
        'summary' => [
            'total' => count($acquirers),
            'active' => count(array_filter($acquirers, fn($a) => $a['status'] === 'active')),
            'configured' => count(array_filter($results, fn($r) => $r['configuration_ok']))
        ]
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
