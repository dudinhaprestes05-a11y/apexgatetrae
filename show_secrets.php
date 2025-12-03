<?php

require_once __DIR__ . '/app/config/config.php';
require_once __DIR__ . '/app/config/database.php';

header('Content-Type: application/json');

$db = getDbConnection();

$email = $_GET['email'] ?? null;

if ($email) {
    $stmt = $db->prepare("
        SELECT
            id,
            email,
            business_name,
            api_key,
            api_secret,
            status
        FROM sellers
        WHERE email = ?
    ");
    $stmt->execute([$email]);
    $seller = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$seller) {
        echo json_encode([
            'success' => false,
            'error' => 'Seller not found with email: ' . $email
        ], JSON_PRETTY_PRINT);
        exit;
    }

    echo json_encode([
        'success' => true,
        'seller' => $seller,
        'note' => 'USE EXACTLY THESE VALUES'
    ], JSON_PRETTY_PRINT);
} else {
    $stmt = $db->query("
        SELECT
            id,
            email,
            business_name,
            api_key,
            api_secret,
            status
        FROM sellers
        WHERE status = 'active'
        ORDER BY created_at DESC
    ");
    $sellers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'count' => count($sellers),
        'sellers' => $sellers,
        'note' => 'To see a specific seller: ?email=seller@email.com'
    ], JSON_PRETTY_PRINT);
}
