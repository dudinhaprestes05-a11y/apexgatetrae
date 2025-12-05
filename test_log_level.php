<?php

require_once __DIR__ . '/app/config/config.php';
require_once __DIR__ . '/app/config/database.php';
require_once __DIR__ . '/app/models/Log.php';

echo "=== Teste do Sistema de LOG_LEVEL ===\n\n";
echo "LOG_LEVEL configurado: " . LOG_LEVEL . "\n";
echo "LOG_LEVEL do .env: " . getenv('LOG_LEVEL') . "\n\n";

$log = new Log();

echo "Testando logs em diferentes níveis:\n\n";

$testPrefix = "TEST_" . time();

$debugResult = $log->debug('test', "$testPrefix - Mensagem DEBUG");
echo "1. DEBUG log: " . ($debugResult ? "✓ Salvo" : "✗ Ignorado (esperado)") . "\n";

$infoResult = $log->info('test', "$testPrefix - Mensagem INFO");
echo "2. INFO log: " . ($infoResult ? "✓ Salvo" : "✗ Ignorado (esperado)") . "\n";

$warningResult = $log->warning('test', "$testPrefix - Mensagem WARNING");
echo "3. WARNING log: " . ($warningResult ? "✓ Salvo (esperado)" : "✗ Ignorado") . "\n";

$errorResult = $log->error('test', "$testPrefix - Mensagem ERROR");
echo "4. ERROR log: " . ($errorResult ? "✓ Salvo (esperado)" : "✗ Ignorado") . "\n";

$criticalResult = $log->critical('test', "$testPrefix - Mensagem CRITICAL");
echo "5. CRITICAL log: " . ($criticalResult ? "✓ Salvo (esperado)" : "✗ Ignorado") . "\n";

echo "\n=== Verificando logs salvos no banco ===\n\n";

$db = new Database();
$conn = $db->getConnection();

$stmt = $conn->prepare("SELECT level, message, created_at FROM logs WHERE message LIKE ? ORDER BY created_at DESC LIMIT 10");
$searchPattern = "$testPrefix%";
$stmt->execute([$searchPattern]);
$recentLogs = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($recentLogs) > 0) {
    echo "Logs salvos no banco de dados:\n";
    foreach ($recentLogs as $logEntry) {
        echo sprintf("  - [%s] %s (em %s)\n",
            strtoupper($logEntry['level']),
            $logEntry['message'],
            $logEntry['created_at']
        );
    }
} else {
    echo "Nenhum log foi salvo (verifique a conexão com o banco)\n";
}

echo "\n=== Resultado Esperado ===\n";
echo "Com LOG_LEVEL=warning, apenas WARNING, ERROR e CRITICAL devem ser salvos.\n";
echo "DEBUG e INFO devem ser ignorados.\n";
