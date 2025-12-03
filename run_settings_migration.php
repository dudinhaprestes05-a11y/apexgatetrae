<?php

require_once __DIR__ . '/app/config/database.php';

echo "====================================\n";
echo "   Migrando Tabela de Configurações\n";
echo "====================================\n\n";

try {
    $db = Database::getInstance()->getConnection();

    // Lê o arquivo SQL
    $sql = file_get_contents(__DIR__ . '/sql/create_settings.sql');

    if ($sql === false) {
        throw new Exception("Erro ao ler o arquivo de migração");
    }

    // Remove comentários SQL
    $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);

    // Separa e executa cada statement
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        function($stmt) { return !empty($stmt); }
    );

    foreach ($statements as $statement) {
        if (!empty($statement)) {
            echo "Executando: " . substr($statement, 0, 50) . "...\n";
            $db->exec($statement);
        }
    }

    echo "\n✓ Migração executada com sucesso!\n";
    echo "✓ Tabela system_settings criada\n";
    echo "✓ Configurações padrão inseridas\n\n";
    echo "Acesse /admin/settings para configurar as taxas padrão do sistema.\n";

} catch (Exception $e) {
    echo "\n✗ Erro na migração: " . $e->getMessage() . "\n";
    exit(1);
}
