<?php

require_once __DIR__ . '/app/config/config.php';
require_once __DIR__ . '/app/config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Use POST method']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$apiKey = $input['api_key'] ?? null;
$apiSecret = $input['api_secret'] ?? null;

if (!$apiKey || !$apiSecret) {
    echo json_encode([
        'error' => 'Provide api_key and api_secret in JSON body',
        'example' => [
            'api_key' => 'your_api_key_here',
            'api_secret' => 'your_api_secret_here'
        ]
    ], JSON_PRETTY_PRINT);
    exit;
}

$db = getDbConnection();

$stmt = $db->prepare("SELECT * FROM sellers WHERE api_key = ?");
$stmt->execute([$apiKey]);
$seller = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$seller) {
    echo json_encode([
        'success' => false,
        'error' => 'API Key not found',
        'received_key_preview' => substr($apiKey, 0, 8) . '...',
        'received_key_length' => strlen($apiKey)
    ], JSON_PRETTY_PRINT);
    exit;
}

$storedSecret = $seller['api_secret'];
$receivedSecret = $apiSecret;

$match = ($storedSecret === $receivedSecret);
$matchTrimmed = (trim($storedSecret) === trim($receivedSecret));

echo json_encode([
    'success' => $match || $matchTrimmed,
    'seller_id' => $seller['id'],
    'seller_email' => $seller['email'],
    'seller_status' => $seller['status'],
    'comparison' => [
        'stored_secret_length' => strlen($storedSecret),
        'received_secret_length' => strlen($receivedSecret),
        'stored_secret_preview' => substr($storedSecret, 0, 4) . '...' . substr($storedSecret, -4),
        'received_secret_preview' => substr($receivedSecret, 0, 4) . '...' . substr($receivedSecret, -4),
        'exact_match' => $match,
        'trimmed_match' => $matchTrimmed,
        'has_whitespace_stored' => $storedSecret !== trim($storedSecret),
        'has_whitespace_received' => $receivedSecret !== trim($receivedSecret)
    ],
    'note' => $match ? 'Credentials are valid!' : 'Secrets do not match'
], JSON_PRETTY_PRINT);
