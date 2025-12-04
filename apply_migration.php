<?php
require_once __DIR__ . '/app/config/database.php';

try {
    $db = Database::getInstance();

    $sql = file_get_contents(__DIR__ . '/sql/add_personal_info_fields.sql');

    // Remove comments
    $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);

    // Split by semicolon and execute each statement
    $statements = array_filter(array_map('trim', explode(';', $sql)));

    foreach ($statements as $statement) {
        if (empty($statement)) continue;

        echo "Executing: " . substr($statement, 0, 100) . "...\n";
        $db->exec($statement);
        echo "✓ Success\n\n";
    }

    echo "\n✅ Migration applied successfully!\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
