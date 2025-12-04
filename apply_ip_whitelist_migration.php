<?php

require_once __DIR__ . '/app/config/config.php';
require_once __DIR__ . '/app/config/database.php';

try {
    $db = db();

    echo "Applying IP Whitelist Migration...\n";
    echo "Note: Whitelist will be ENABLED by default for all sellers\n\n";

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

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
