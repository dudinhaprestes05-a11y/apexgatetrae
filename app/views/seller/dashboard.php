<?php
$pageTitle = 'Dashboard';
require_once __DIR__ . '/../layouts/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Dashboard</h1>
        <p class="text-gray-600 mt-2">Bem-vindo ao seu painel de controle</p>
    </div>

    <?php if ($seller['status'] === 'pending'): ?>
    <div class="mb-6 bg-yellow-50 border border-yellow-200 rounded-lg p-4">
        <div class="flex items-center">
            <i class="fas fa-exclamation-triangle text-yellow-600 text-xl mr-3"></i>
            <div>
                <h3 class="font-semibold text-yellow-900">Conta Pendente de Aprovação</h3>
                <p class="text-yellow-700 text-sm mt-1">Por favor, envie todos os documentos necessários para análise.</p>
                <a href="/seller/documents" class="text-yellow-800 font-medium hover:underline text-sm mt-2 inline-block">
                    Ver documentos <i class="fas fa-arrow-right ml-1"></i>
                </a>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover-lift">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm font-medium">Saldo Disponível</p>
                    <p class="text-2xl font-bold text-gray-900 mt-2">R$ <?= number_format($stats['balance'], 2, ',', '.') ?></p>
                </div>
                <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-green-600 rounded-lg flex items-center justify-center">
                    <i class="fas fa-wallet text-white text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover-lift">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm font-medium">Total Recebido</p>
                    <p class="text-2xl font-bold text-gray-900 mt-2">R$ <?= number_format($stats['total_cashin'], 2, ',', '.') ?></p>
                </div>
                <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg flex items-center justify-center">
                    <i class="fas fa-arrow-down text-white text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover-lift">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm font-medium">Total Sacado</p>
                    <p class="text-2xl font-bold text-gray-900 mt-2">R$ <?= number_format($stats['total_cashout'], 2, ',', '.') ?></p>
                </div>
                <div class="w-12 h-12 bg-gradient-to-br from-orange-500 to-orange-600 rounded-lg flex items-center justify-center">
                    <i class="fas fa-arrow-up text-white text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover-lift">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm font-medium">Pendentes</p>
                    <p class="text-2xl font-bold text-gray-900 mt-2"><?= $stats['pending_cashin'] ?></p>
                </div>
                <div class="w-12 h-12 bg-gradient-to-br from-yellow-500 to-yellow-600 rounded-lg flex items-center justify-center">
                    <i class="fas fa-clock text-white text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h2 class="text-lg font-bold text-gray-900 mb-4 flex items-center justify-between">
                <span><i class="fas fa-arrow-down text-blue-600 mr-2"></i>Recebimentos Recentes</span>
                <a href="/seller/transactions?type=cashin" class="text-blue-600 text-sm font-normal hover:underline">Ver todos</a>
            </h2>
            <div class="space-y-3">
                <?php if (empty($recentCashin)): ?>
                    <p class="text-gray-500 text-center py-8">Nenhum recebimento ainda</p>
                <?php else: ?>
                    <?php foreach (array_slice($recentCashin, 0, 5) as $tx): ?>
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                        <div>
                            <p class="font-medium text-gray-900">R$ <?= number_format($tx['amount'], 2, ',', '.') ?></p>
                            <p class="text-xs text-gray-600"><?= date('d/m/Y H:i', strtotime($tx['created_at'])) ?></p>
                        </div>
                        <span class="px-3 py-1 rounded-full text-xs font-medium
                            <?= $tx['status'] === 'approved' || $tx['status'] === 'paid' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' ?>">
                            <?= ucfirst($tx['status']) ?>
                        </span>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h2 class="text-lg font-bold text-gray-900 mb-4 flex items-center justify-between">
                <span><i class="fas fa-bell text-blue-600 mr-2"></i>Notificações</span>
                <a href="/seller/notifications" class="text-blue-600 text-sm font-normal hover:underline">Ver todas</a>
            </h2>
            <div class="space-y-3">
                <?php if (empty($notifications)): ?>
                    <p class="text-gray-500 text-center py-8">Nenhuma notificação</p>
                <?php else: ?>
                    <?php foreach ($notifications as $notif): ?>
                    <div class="flex items-start space-x-3 p-3 bg-gray-50 rounded-lg <?= $notif['is_read'] ? 'opacity-60' : '' ?>">
                        <div class="flex-shrink-0 w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-info text-blue-600 text-xs"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="font-medium text-gray-900 text-sm"><?= htmlspecialchars($notif['title']) ?></p>
                            <p class="text-xs text-gray-600 mt-1"><?= htmlspecialchars($notif['message']) ?></p>
                            <p class="text-xs text-gray-500 mt-1"><?= date('d/m/Y H:i', strtotime($notif['created_at'])) ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
