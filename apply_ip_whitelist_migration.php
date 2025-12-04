<?php

require_once __DIR__ . '/app/config/config.php';
require_once __DIR__ . '/app/config/database.php';

try {
    $db = db();

    echo "=============================================================\n";
    echo "        IP Whitelist Migration\n";
    echo "=============================================================\n\n";
    echo "⚠️  IMPORTANT:\n";
    echo "   - Whitelist will be ENABLED by default\n";
    echo "   - ALL API access will be BLOCKED until IPs are added\n";
    echo "   - Sellers must add IPs OR disable whitelist to allow access\n";
    echo "   - Web panel access is NOT affected\n\n";
    echo "=============================================================\n\n";

    $migration = file_get_contents(__DIR__ . '/sql/add_ip_whitelist.sql');

    $statements = array_filter(
        array_map('trim', explode(';', $migration)),
        function($stmt) {
            return !empty($stmt) &&
                   !preg_match('/^\/\*.*\*\/$/s', $stmt) &&
                   strpos(trim($stmt), '/*') !== 0;
        }
    );

    foreach ($statements as $statement) {
        if (empty(trim($statement))) {
            continue;
        }

        echo "Executing statement...\n";
        $db->exec($statement);
        echo "Success!\n\n";
    }

    echo "Migration completed successfully!\n";

    $stmt = $db->query("SHOW COLUMNS FROM sellers LIKE 'ip_whitelist%'");
    $columns = $stmt->fetchAll();

    echo "\nAdded columns:\n";
    foreach ($columns as $column) {
        echo "  - " . $column['Field'] . " (" . $column['Type'] . ")\n";
    }

    echo "\n=============================================================\n";
    echo "⚠️  NEXT STEPS:\n";
    echo "=============================================================\n";
    echo "1. All sellers now have IP whitelist ENABLED by default\n";
    echo "2. API access is BLOCKED until sellers configure IPs\n";
    echo "3. Sellers should:\n";
    echo "   - Go to: /seller/ip-whitelist\n";
    echo "   - Add authorized IPs\n";
    echo "   - OR disable whitelist to allow all IPs\n";
    echo "4. Web panel access continues working normally\n";
    echo "=============================================================\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
