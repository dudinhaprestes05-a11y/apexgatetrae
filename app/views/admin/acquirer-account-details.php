<?php
$pageTitle = 'Detalhes da Conta - ' . htmlspecialchars($account['name']);
require_once __DIR__ . '/../layouts/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-8">
        <a href="/admin/acquirers/<?= $acquirer['id'] ?>/accounts" class="text-blue-600 hover:text-blue-800 text-sm mb-2 inline-block">
            <i class="fas fa-arrow-left mr-1"></i>Voltar para Contas
        </a>
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900"><?= htmlspecialchars($account['name']) ?></h1>
                <p class="text-gray-600 mt-2"><?= htmlspecialchars($acquirer['name']) ?> - Detalhes e Estatísticas</p>
            </div>
            <div class="flex items-center gap-3">
                <span class="px-4 py-2 text-sm font-medium rounded-lg <?= $account['is_active'] ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' ?>">
                    <?= $account['is_active'] ? 'Ativa' : 'Inativa' ?>
                </span>
                <?php if ($account['is_default']): ?>
                <span class="px-4 py-2 text-sm font-medium rounded-lg bg-blue-100 text-blue-800">
                    <i class="fas fa-star mr-1"></i>Conta Padrão
                </span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-sm font-medium text-gray-600">Saldo Atual</h3>
                <i class="fas fa-wallet text-blue-600"></i>
            </div>
            <p class="text-3xl font-bold text-gray-900">R$ <?= number_format($account['balance'] ?? 0, 2, ',', '.') ?></p>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-sm font-medium text-gray-600">Total de Transações</h3>
                <i class="fas fa-exchange-alt text-purple-600"></i>
            </div>
            <p class="text-3xl font-bold text-gray-900"><?= number_format($stats['total_transactions'] ?? 0) ?></p>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-sm font-medium text-gray-600">Volume Total</h3>
                <i class="fas fa-chart-line text-green-600"></i>
            </div>
            <p class="text-3xl font-bold text-green-600">R$ <?= number_format($stats['total_volume'] ?? 0, 2, ',', '.') ?></p>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-sm font-medium text-gray-600">Taxa de Sucesso</h3>
                <i class="fas fa-check-circle text-orange-600"></i>
            </div>
            <p class="text-3xl font-bold text-orange-600">
                <?php
                $successRate = $stats['total_transactions'] > 0
                    ? ($stats['successful_transactions'] / $stats['total_transactions']) * 100
                    : 0;
                echo number_format($successRate, 1) . '%';
                ?>
            </p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4">Informações da Conta</h3>
            <div class="space-y-3">
                <div>
                    <label class="text-sm font-medium text-gray-600">Merchant ID</label>
                    <p class="text-gray-900 mt-1 font-mono text-xs break-all"><?= htmlspecialchars($account['merchant_id']) ?></p>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-600">Client ID</label>
                    <p class="text-gray-900 mt-1 font-mono text-xs break-all"><?= htmlspecialchars($account['client_id']) ?></p>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-600">Criada em</label>
                    <p class="text-gray-900 mt-1"><?= date('d/m/Y H:i', strtotime($account['created_at'])) ?></p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4">Limites por Transação</h3>
            <div class="space-y-3">
                <div>
                    <label class="text-sm font-medium text-gray-600">Cash-in Mínimo</label>
                    <p class="text-gray-900 mt-1">R$ <?= number_format($account['min_cashin_per_transaction'] ?? 0.01, 2, ',', '.') ?></p>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-600">Cash-in Máximo</label>
                    <p class="text-gray-900 mt-1">
                        <?= $account['max_cashin_per_transaction'] ? 'R$ ' . number_format($account['max_cashin_per_transaction'], 2, ',', '.') : 'Sem limite' ?>
                    </p>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-600">Cash-out Mínimo</label>
                    <p class="text-gray-900 mt-1">R$ <?= number_format($account['min_cashout_per_transaction'] ?? 0.01, 2, ',', '.') ?></p>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-600">Cash-out Máximo</label>
                    <p class="text-gray-900 mt-1">
                        <?= $account['max_cashout_per_transaction'] ? 'R$ ' . number_format($account['max_cashout_per_transaction'], 2, ',', '.') : 'Sem limite' ?>
                    </p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4">Estatísticas Gerais</h3>
            <div class="space-y-3">
                <div>
                    <label class="text-sm font-medium text-gray-600">Transações com Sucesso</label>
                    <p class="text-gray-900 mt-1 text-xl font-semibold text-green-600"><?= number_format($stats['successful_transactions'] ?? 0) ?></p>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-600">Transações Falhadas</label>
                    <p class="text-gray-900 mt-1 text-xl font-semibold text-red-600"><?= number_format($stats['failed_transactions'] ?? 0) ?></p>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-600">Ticket Médio</label>
                    <p class="text-gray-900 mt-1">R$ <?= number_format($stats['avg_transaction_value'] ?? 0, 2, ',', '.') ?></p>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-8">
        <h3 class="text-lg font-bold text-gray-900 mb-4">Desempenho Diário (Últimos 30 dias)</h3>
        <div class="overflow-x-auto">
            <?php if (empty($dailyStats)): ?>
            <p class="text-gray-500 text-center py-8">Nenhuma transação nos últimos 30 dias</p>
            <?php else: ?>
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-gray-200">
                        <th class="text-left py-3 px-4 text-sm font-semibold text-gray-700">Data</th>
                        <th class="text-right py-3 px-4 text-sm font-semibold text-gray-700">Transações</th>
                        <th class="text-right py-3 px-4 text-sm font-semibold text-gray-700">Sucesso</th>
                        <th class="text-right py-3 px-4 text-sm font-semibold text-gray-700">Taxa</th>
                        <th class="text-right py-3 px-4 text-sm font-semibold text-gray-700">Volume</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($dailyStats as $day): ?>
                    <tr class="border-b border-gray-100 hover:bg-gray-50">
                        <td class="py-3 px-4 text-sm text-gray-900"><?= date('d/m/Y', strtotime($day['date'])) ?></td>
                        <td class="py-3 px-4 text-sm text-gray-900 text-right"><?= $day['total_transactions'] ?></td>
                        <td class="py-3 px-4 text-sm text-green-600 text-right"><?= $day['successful_transactions'] ?></td>
                        <td class="py-3 px-4 text-sm text-gray-900 text-right">
                            <?= $day['total_transactions'] > 0 ? number_format(($day['successful_transactions'] / $day['total_transactions']) * 100, 1) : 0 ?>%
                        </td>
                        <td class="py-3 px-4 text-sm text-gray-900 text-right">R$ <?= number_format($day['total_volume'], 2, ',', '.') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <h3 class="text-lg font-bold text-gray-900 mb-4">Transações Recentes</h3>
        <div class="overflow-x-auto">
            <?php if (empty($recentTransactions)): ?>
            <p class="text-gray-500 text-center py-8">Nenhuma transação encontrada</p>
            <?php else: ?>
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-gray-200">
                        <th class="text-left py-3 px-4 text-sm font-semibold text-gray-700">ID</th>
                        <th class="text-left py-3 px-4 text-sm font-semibold text-gray-700">Vendedor</th>
                        <th class="text-left py-3 px-4 text-sm font-semibold text-gray-700">Data</th>
                        <th class="text-right py-3 px-4 text-sm font-semibold text-gray-700">Valor</th>
                        <th class="text-center py-3 px-4 text-sm font-semibold text-gray-700">Status</th>
                        <th class="text-center py-3 px-4 text-sm font-semibold text-gray-700">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentTransactions as $tx): ?>
                    <tr class="border-b border-gray-100 hover:bg-gray-50">
                        <td class="py-3 px-4 text-sm font-mono text-gray-900"><?= htmlspecialchars(substr($tx['id'], 0, 8)) ?>...</td>
                        <td class="py-3 px-4 text-sm text-gray-900">
                            <?= htmlspecialchars($tx['seller_name'] ?? 'N/A') ?>
                            <span class="text-gray-500 text-xs block"><?= htmlspecialchars($tx['seller_email'] ?? '') ?></span>
                        </td>
                        <td class="py-3 px-4 text-sm text-gray-900"><?= date('d/m/Y H:i', strtotime($tx['created_at'])) ?></td>
                        <td class="py-3 px-4 text-sm text-gray-900 text-right font-semibold">R$ <?= number_format($tx['amount'], 2, ',', '.') ?></td>
                        <td class="py-3 px-4 text-center">
                            <?php
                            $statusColors = [
                                'pending' => 'bg-yellow-100 text-yellow-800',
                                'approved' => 'bg-green-100 text-green-800',
                                'paid' => 'bg-blue-100 text-blue-800',
                                'failed' => 'bg-red-100 text-red-800',
                                'cancelled' => 'bg-gray-100 text-gray-800',
                                'expired' => 'bg-orange-100 text-orange-800'
                            ];
                            $statusLabels = [
                                'pending' => 'Pendente',
                                'approved' => 'Aprovado',
                                'paid' => 'Pago',
                                'failed' => 'Falhado',
                                'cancelled' => 'Cancelado',
                                'expired' => 'Expirado'
                            ];
                            ?>
                            <span class="px-2 py-1 text-xs font-medium rounded-full <?= $statusColors[$tx['status']] ?? 'bg-gray-100 text-gray-800' ?>">
                                <?= $statusLabels[$tx['status']] ?? $tx['status'] ?>
                            </span>
                        </td>
                        <td class="py-3 px-4 text-center">
                            <a href="/admin/transactions/<?= $tx['id'] ?>" class="text-blue-600 hover:text-blue-800 text-sm">
                                <i class="fas fa-eye"></i> Ver
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
