<?php
require_once __DIR__ . '/app/config/config.php';
require_once __DIR__ . '/app/config/database.php';

header('Content-Type: application/json');

try {
    $db = db();

    echo "Testando query de contas disponíveis...\n\n";

    $sql = "
        SELECT aa.id, aa.name as account_name, aa.merchant_id as account_identifier,
               a.name as acquirer_name, a.code as acquirer_code
        FROM acquirer_accounts aa
        JOIN acquirers a ON a.id = aa.acquirer_id
        WHERE aa.is_active = 1 AND a.status = 'active'
        ORDER BY a.name, aa.name
    ";

    $stmt = $db->prepare($sql);
    $stmt->execute();
    $accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'accounts' => $accounts,
        'count' => count($accounts)
    ], JSON_PRETTY_PRINT);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'code' => $e->getCode()
    ], JSON_PRETTY_PRINT);
}
