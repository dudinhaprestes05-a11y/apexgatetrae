<?php
$pageTitle = 'Logs';
require_once __DIR__ . '/../layouts/header.php';
$level = $_GET['level'] ?? '';
$category = $_GET['category'] ?? '';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Logs do Sistema</h1>
        <p class="text-gray-600 mt-2">Monitore a atividade e erros do sistema</p>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
        <form method="GET" action="/admin/logs" class="flex items-end gap-4">
            <div class="flex-1">
                <label class="block text-sm font-medium text-gray-700 mb-2">Nível</label>
                <select name="level" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                    <option value="">Todos</option>
                    <option value="debug" <?= $level === 'debug' ? 'selected' : '' ?>>Debug</option>
                    <option value="info" <?= $level === 'info' ? 'selected' : '' ?>>Info</option>
                    <option value="warning" <?= $level === 'warning' ? 'selected' : '' ?>>Warning</option>
                    <option value="error" <?= $level === 'error' ? 'selected' : '' ?>>Error</option>
                    <option value="critical" <?= $level === 'critical' ? 'selected' : '' ?>>Critical</option>
                </select>
            </div>
            <div class="flex-1">
                <label class="block text-sm font-medium text-gray-700 mb-2">Categoria</label>
                <select name="category" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                    <option value="">Todas</option>
                    <option value="auth" <?= $category === 'auth' ? 'selected' : '' ?>>Autenticação</option>
                    <option value="pix" <?= $category === 'pix' ? 'selected' : '' ?>>PIX</option>
                    <option value="cashout" <?= $category === 'cashout' ? 'selected' : '' ?>>Cashout</option>
                    <option value="webhook" <?= $category === 'webhook' ? 'selected' : '' ?>>Webhook</option>
                    <option value="system" <?= $category === 'system' ? 'selected' : '' ?>>Sistema</option>
                </select>
            </div>
            <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                <i class="fas fa-filter mr-2"></i>Filtrar
            </button>
        </form>
    </div>

    <div class="space-y-3">
        <?php if (empty($logs)): ?>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-12 text-center">
            <i class="fas fa-clipboard-list text-gray-400 text-5xl mb-4"></i>
            <p class="text-gray-500">Nenhum log encontrado</p>
        </div>
        <?php else: ?>
        <?php foreach ($logs as $log): ?>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <div class="flex items-center gap-3 mb-2">
                        <span class="px-3 py-1 text-xs font-medium rounded-full
                            <?php
                            echo match($log['level']) {
                                'critical' => 'bg-red-100 text-red-800',
                                'error' => 'bg-red-100 text-red-800',
                                'warning' => 'bg-yellow-100 text-yellow-800',
                                'info' => 'bg-blue-100 text-blue-800',
                                'debug' => 'bg-gray-100 text-gray-800',
                                default => 'bg-gray-100 text-gray-800'
                            };
                            ?>">
                            <?= strtoupper($log['level']) ?>
                        </span>
                        <span class="px-2 py-1 bg-gray-100 text-gray-700 text-xs font-medium rounded">
                            <?= ucfirst($log['category']) ?>
                        </span>
                        <span class="text-xs text-gray-500">
                            <?= date('d/m/Y H:i:s', strtotime($log['created_at'])) ?>
                        </span>
                    </div>
                    <p class="text-gray-900 font-medium mb-2"><?= htmlspecialchars($log['message']) ?></p>
                    <?php if ($log['context']): ?>
                    <details class="mt-3">
                        <summary class="text-sm text-blue-600 cursor-pointer hover:text-blue-800">Ver contexto</summary>
                        <pre class="mt-2 p-3 bg-gray-50 rounded-lg text-xs overflow-x-auto"><?= htmlspecialchars(json_encode(json_decode($log['context']), JSON_PRETTY_PRINT)) ?></pre>
                    </details>
                    <?php endif; ?>
                    <?php if ($log['seller_id']): ?>
                    <div class="mt-2 text-sm text-gray-600">
                        Seller ID: <a href="/admin/sellers/view/<?= $log['seller_id'] ?>" class="text-blue-600 hover:text-blue-800">#<?= $log['seller_id'] ?></a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
