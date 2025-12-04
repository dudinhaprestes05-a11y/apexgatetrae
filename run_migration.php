<?php
// Arquivo temporário para aplicar migração - DELETE APÓS USO
require_once __DIR__ . '/app/config/database.php';

$output = [];

try {
    $db = Database::getInstance();

    $sql = file_get_contents(__DIR__ . '/sql/add_personal_info_fields.sql');

    // Remove comments
    $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);

    // Split by semicolon and execute each statement
    $statements = array_filter(array_map('trim', explode(';', $sql)));

    foreach ($statements as $statement) {
        if (empty($statement)) continue;

        $output[] = "Executing: " . substr($statement, 0, 100) . "...";
        $db->exec($statement);
        $output[] = "✓ Success\n";
    }

    $output[] = "\n✅ Migration applied successfully!";
    $output[] = "\n⚠️ IMPORTANTE: Delete este arquivo (run_migration.php) após a execução!";

    $success = true;

} catch (Exception $e) {
    $output[] = "❌ Error: " . $e->getMessage();
    $success = false;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Migração do Banco de Dados</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-4xl mx-auto">
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h1 class="text-2xl font-bold mb-4 <?= $success ? 'text-green-600' : 'text-red-600' ?>">
                <?= $success ? '✅ Migração Concluída' : '❌ Erro na Migração' ?>
            </h1>

            <div class="bg-gray-50 rounded p-4 font-mono text-sm space-y-2">
                <?php foreach ($output as $line): ?>
                    <div><?= htmlspecialchars($line) ?></div>
                <?php endforeach; ?>
            </div>

            <?php if ($success): ?>
                <div class="mt-6 p-4 bg-yellow-50 border border-yellow-200 rounded">
                    <p class="text-yellow-800 font-semibold">⚠️ IMPORTANTE:</p>
                    <p class="text-yellow-700 mt-2">
                        Por segurança, delete o arquivo <code class="bg-yellow-100 px-2 py-1 rounded">run_migration.php</code>
                        após a execução desta migração!
                    </p>
                </div>

                <div class="mt-4">
                    <a href="/seller/dashboard" class="inline-block bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700">
                        Ir para Dashboard
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
