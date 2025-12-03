<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/plain');

echo "DEBUG - Iniciando...\n\n";

try {
    // Carrega .env
    if (file_exists(__DIR__ . '/.env')) {
        $lines = file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
                list($key, $value) = explode('=', $line, 2);
                $_ENV[trim($key)] = trim($value);
            }
        }
        echo "✅ .env carregado\n";
    }

    $host = $_ENV['DB_HOST'] ?? 'localhost';
    $dbname = $_ENV['DB_NAME'] ?? '';
    $user = $_ENV['DB_USER'] ?? 'root';
    $pass = $_ENV['DB_PASS'] ?? '';

    echo "Conectando em: {$host}/{$dbname}\n\n";

    $dsn = "mysql:host={$host};dbname={$dbname};charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    echo "✅ Conectado ao banco\n\n";

    $stmt = $pdo->query("SELECT id, name, email, api_key, api_secret FROM sellers ORDER BY id");
    $sellers = $stmt->fetchAll();

    echo "Total de sellers: " . count($sellers) . "\n\n";

    foreach ($sellers as $s) {
        echo "===========================================\n";
        echo "ID: {$s['id']}\n";
        echo "Nome: {$s['name']}\n";
        echo "Email: {$s['email']}\n";
        echo "API Key: {$s['api_key']}\n";
        echo "API Secret: {$s['api_secret']}\n";
        echo "Secret Length: " . strlen($s['api_secret']) . "\n";
        echo "Is Hex: " . (ctype_xdigit($s['api_secret']) ? 'YES' : 'NO') . "\n";
        echo "Is SHA256: " . (strlen($s['api_secret']) === 64 && ctype_xdigit($s['api_secret']) ? 'YES' : 'NO') . "\n";
        echo "\n";
    }

    // Teste com GET params
    if (isset($_GET['test_seller']) && isset($_GET['test_secret'])) {
        echo "===========================================\n";
        echo "TESTE DE AUTENTICAÇÃO\n";
        echo "===========================================\n\n";

        $id = $_GET['test_seller'];
        $secret = $_GET['test_secret'];

        $stmt = $pdo->prepare("SELECT * FROM sellers WHERE id = ?");
        $stmt->execute([$id]);
        $seller = $stmt->fetch();

        if ($seller) {
            $hash = hash('sha256', $secret);
            
            echo "Seller: {$seller['name']}\n";
            echo "Secret enviado: {$secret}\n";
            echo "Hash do enviado: {$hash}\n";
            echo "Hash no banco: {$seller['api_secret']}\n";
            echo "Batem? " . ($hash === $seller['api_secret'] ? '✅ SIM' : '❌ NÃO') . "\n";
        } else {
            echo "Seller não encontrado!\n";
        }
    }

} catch (Exception $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
}

echo "\n⚠️ REMOVA ESTE ARQUIVO!\n";
