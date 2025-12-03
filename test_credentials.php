<?php

require_once __DIR__ . '/app/config/config.php';
require_once __DIR__ . '/app/config/database.php';

header('Content-Type: application/json');

$db = getDbConnection();

$stmt = $db->query("
    SELECT
        id,
        email,
        business_name,
        api_key,
        api_secret,
        status,
        LENGTH(api_key) as key_length,
        LENGTH(api_secret) as secret_length,
        SUBSTRING(api_key, 1, 8) as key_preview,
        SUBSTRING(api_secret, 1, 4) as secret_preview
    FROM sellers
    WHERE status = 'active'
    ORDER BY created_at DESC
    LIMIT 5
");

$sellers = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'success' => true,
    'count' => count($sellers),
    'sellers' => $sellers
], JSON_PRETTY_PRINT);
