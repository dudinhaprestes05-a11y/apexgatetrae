<?php
$pageTitle = 'Dashboard Admin';
require_once __DIR__ . '/../layouts/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Dashboard Admin</h1>
        <p class="text-gray-600 mt-2">Visão geral do sistema</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-8">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover-lift">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm font-medium">Sellers Ativos</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2"><?= $stats['total_sellers'] ?></p>
                </div>
                <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-green-600 rounded-lg flex items-center justify-center">
                    <i class="fas fa-users text-white text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover-lift">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm font-medium">Pendentes</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2"><?= $stats['pending_sellers'] ?></p>
                </div>
                <div class="w-12 h-12 bg-gradient-to-br from-yellow-500 to-yellow-600 rounded-lg flex items-center justify-center">
                    <i class="fas fa-clock text-white text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover-lift">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm font-medium">Documentos</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2"><?= $stats['pending_documents'] ?></p>
                </div>
                <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg flex items-center justify-center">
                    <i class="fas fa-file-alt text-white text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover-lift">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm font-medium">Total Cash-in</p>
                    <p class="text-2xl font-bold text-gray-900 mt-2">R$ <?= number_format($stats['total_cashin'], 0, ',', '.') ?></p>
                </div>
                <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg flex items-center justify-center">
                    <i class="fas fa-arrow-down text-white text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover-lift">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm font-medium">Total Cash-out</p>
                    <p class="text-2xl font-bold text-gray-900 mt-2">R$ <?= number_format($stats['total_cashout'], 0, ',', '.') ?></p>
                </div>
                <div class="w-12 h-12 bg-gradient-to-br from-orange-500 to-orange-600 rounded-lg flex items-center justify-center">
                    <i class="fas fa-arrow-up text-white text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h2 class="text-lg font-bold text-gray-900 mb-4 flex items-center justify-between">
                <span><i class="fas fa-user-clock text-yellow-600 mr-2"></i>Sellers Pendentes</span>
                <a href="/admin/sellers?status=pending" class="text-blue-600 text-sm font-normal hover:underline">Ver todos</a>
            </h2>
            <div class="space-y-3">
                <?php if (empty($recentSellers)): ?>
                    <p class="text-gray-500 text-center py-8">Nenhum seller pendente</p>
                <?php else: ?>
                    <?php foreach ($recentSellers as $seller): ?>
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                        <div class="flex-1">
                            <p class="font-medium text-gray-900"><?= htmlspecialchars($seller['name']) ?></p>
                            <p class="text-xs text-gray-600"><?= htmlspecialchars($seller['email']) ?></p>
                            <p class="text-xs text-gray-500 mt-1"><?= date('d/m/Y H:i', strtotime($seller['created_at'])) ?></p>
                        </div>
                        <a href="/admin/sellers/view/<?= $seller['id'] ?>" class="px-3 py-1 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700">
                            Analisar
                        </a>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h2 class="text-lg font-bold text-gray-900 mb-4 flex items-center justify-between">
                <span><i class="fas fa-arrow-down text-blue-600 mr-2"></i>Transações Recentes</span>
                <a href="/admin/transactions" class="text-blue-600 text-sm font-normal hover:underline">Ver todas</a>
            </h2>
            <div class="space-y-3">
                <?php if (empty($recentCashin)): ?>
                    <p class="text-gray-500 text-center py-8">Nenhuma transação ainda</p>
                <?php else: ?>
                    <?php foreach ($recentCashin as $tx): ?>
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                        <div>
                            <p class="font-medium text-gray-900">R$ <?= number_format($tx['amount'], 2, ',', '.') ?></p>
                            <p class="text-xs text-gray-600">Seller ID: <?= $tx['seller_id'] ?></p>
                            <p class="text-xs text-gray-500"><?= date('d/m/Y H:i', strtotime($tx['created_at'])) ?></p>
                        </div>
                        <span class="px-3 py-1 rounded-full text-xs font-medium
                            <?= in_array($tx['status'], ['approved', 'paid']) ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' ?>">
                            <?= ucfirst($tx['status']) ?>
                        </span>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php if (!empty($recentLogs)): ?>
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <h2 class="text-lg font-bold text-gray-900 mb-4 flex items-center justify-between">
            <span><i class="fas fa-exclamation-triangle text-red-600 mr-2"></i>Erros Recentes</span>
            <a href="/admin/logs?level=error" class="text-blue-600 text-sm font-normal hover:underline">Ver todos</a>
        </h2>
        <div class="space-y-2">
            <?php foreach ($recentLogs as $log): ?>
            <div class="p-3 bg-red-50 border border-red-200 rounded-lg">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="font-medium text-red-900"><?= htmlspecialchars($log['message']) ?></p>
                        <p class="text-xs text-red-700 mt-1"><?= ucfirst($log['category']) ?></p>
                    </div>
                    <span class="text-xs text-red-600"><?= date('d/m/Y H:i', strtotime($log['created_at'])) ?></span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
