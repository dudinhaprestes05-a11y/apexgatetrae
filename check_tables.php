<?php
require_once __DIR__ . '/app/config/config.php';
require_once __DIR__ . '/app/config/database.php';

header('Content-Type: application/json');

try {
    $db = db();

    // Check acquirer_accounts table
    $result1 = $db->query("SHOW TABLES LIKE 'acquirer_accounts'")->fetch();
    $hasAcquirerAccounts = !empty($result1);

    // Check seller_acquirer_accounts table
    $result2 = $db->query("SHOW TABLES LIKE 'seller_acquirer_accounts'")->fetch();
    $hasSellerAccounts = !empty($result2);

    $response = [
        'success' => true,
        'tables' => [
            'acquirer_accounts' => $hasAcquirerAccounts,
            'seller_acquirer_accounts' => $hasSellerAccounts
        ]
    ];

    // If tables exist, count records
    if ($hasAcquirerAccounts) {
        $count = $db->query("SELECT COUNT(*) as total FROM acquirer_accounts")->fetch();
        $response['acquirer_accounts_count'] = $count['total'];
    }

    if ($hasSellerAccounts) {
        $count = $db->query("SELECT COUNT(*) as total FROM seller_acquirer_accounts")->fetch();
        $response['seller_accounts_count'] = $count['total'];
    }

    echo json_encode($response, JSON_PRETTY_PRINT);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
