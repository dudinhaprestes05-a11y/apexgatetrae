<?php
$pageTitle = 'Dashboard';
require_once __DIR__ . '/../layouts/header.php';
?>

<!-- Stats Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-8">
    <!-- Sellers Ativos -->
    <div class="card p-6">
        <div class="flex items-center justify-between mb-4">
            <div class="stat-icon w-12 h-12 rounded-xl flex items-center justify-center">
                <i class="fas fa-store text-white text-xl"></i>
            </div>
        </div>
        <div>
            <p class="text-slate-400 text-sm mb-1">Sellers Ativos</p>
            <p class="text-3xl font-bold text-white"><?= $stats['total_sellers'] ?></p>
        </div>
    </div>

    <!-- Pendentes -->
    <div class="card p-6">
        <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 rounded-xl flex items-center justify-center bg-gradient-to-br from-yellow-500 to-yellow-600">
                <i class="fas fa-clock text-white text-xl"></i>
            </div>
            <?php if ($stats['pending_sellers'] > 0): ?>
            <span class="badge badge-warning">
                <i class="fas fa-exclamation-circle mr-1"></i>Atenção
            </span>
            <?php endif; ?>
        </div>
        <div>
            <p class="text-slate-400 text-sm mb-1">Sellers Pendentes</p>
            <p class="text-3xl font-bold text-white"><?= $stats['pending_sellers'] ?></p>
        </div>
    </div>

    <!-- Documentos -->
    <div class="card p-6">
        <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 rounded-xl flex items-center justify-center bg-gradient-to-br from-purple-500 to-purple-600">
                <i class="fas fa-file-alt text-white text-xl"></i>
            </div>
            <?php if ($stats['pending_documents'] > 0): ?>
            <span class="badge badge-warning">
                <i class="fas fa-clock mr-1"></i><?= $stats['pending_documents'] ?>
            </span>
            <?php endif; ?>
        </div>
        <div>
            <p class="text-slate-400 text-sm mb-1">Documentos Pendentes</p>
            <p class="text-3xl font-bold text-white"><?= $stats['pending_documents'] ?></p>
        </div>
    </div>

    <!-- Total Cash-in -->
    <div class="card p-6">
        <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 rounded-xl flex items-center justify-center bg-gradient-to-br from-green-500 to-green-600">
                <i class="fas fa-arrow-down text-white text-xl"></i>
            </div>
            <span class="badge badge-success">
                <i class="fas fa-chart-line mr-1"></i>Cash-in
            </span>
        </div>
        <div>
            <p class="text-slate-400 text-sm mb-1">Total Recebido</p>
            <p class="text-2xl font-bold text-white">R$ <?= number_format($stats['total_cashin'], 2, ',', '.') ?></p>
        </div>
    </div>

    <!-- Total Cash-out -->
    <div class="card p-6">
        <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 rounded-xl flex items-center justify-center bg-gradient-to-br from-red-500 to-red-600">
                <i class="fas fa-arrow-up text-white text-xl"></i>
            </div>
            <span class="badge badge-danger">
                <i class="fas fa-chart-line mr-1"></i>Cash-out
            </span>
        </div>
        <div>
            <p class="text-slate-400 text-sm mb-1">Total Pago</p>
            <p class="text-2xl font-bold text-white">R$ <?= number_format($stats['total_cashout'], 2, ',', '.') ?></p>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <!-- Transactions Chart -->
    <div class="card p-6">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-xl font-bold text-white">Volume de Transações</h2>
            <select class="bg-slate-700 border-slate-600 text-white text-sm rounded-lg px-3 py-1.5">
                <option>Últimos 7 dias</option>
                <option>Últimos 30 dias</option>
                <option>Últimos 90 dias</option>
            </select>
        </div>
        <div class="relative h-64">
            <canvas id="transactionsChart"></canvas>
        </div>
    </div>

    <!-- Revenue Chart -->
    <div class="card p-6">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-xl font-bold text-white">Receita (Taxas)</h2>
            <select class="bg-slate-700 border-slate-600 text-white text-sm rounded-lg px-3 py-1.5">
                <option>Últimos 7 dias</option>
                <option>Últimos 30 dias</option>
                <option>Últimos 90 dias</option>
            </select>
        </div>
        <div class="relative h-64">
            <canvas id="revenueChart"></canvas>
        </div>
    </div>
</div>

<!-- Quick Actions & Lists -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
    <!-- Sellers Pendentes -->
    <div class="card p-6">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-lg font-bold text-white flex items-center">
                <i class="fas fa-user-clock text-yellow-500 mr-2"></i>
                Sellers Pendentes
            </h2>
            <a href="/admin/sellers?status=pending" class="text-blue-400 hover:text-blue-300 text-sm">
                Ver todos <i class="fas fa-arrow-right ml-1"></i>
            </a>
        </div>
        <div class="space-y-3 max-h-96 overflow-y-auto scrollbar-thin">
            <?php if (empty($recentSellers)): ?>
                <div class="text-center py-12">
                    <i class="fas fa-check-circle text-5xl text-slate-600 mb-3"></i>
                    <p class="text-slate-400">Nenhum seller pendente</p>
                </div>
            <?php else: ?>
                <?php foreach ($recentSellers as $seller): ?>
                <div class="p-4 bg-slate-800 bg-opacity-50 rounded-lg border border-slate-700 hover:border-blue-500 transition">
                    <div class="flex items-start justify-between mb-2">
                        <div class="flex-1">
                            <p class="font-semibold text-white"><?= htmlspecialchars($seller['name']) ?></p>
                            <p class="text-sm text-slate-400"><?= htmlspecialchars($seller['email']) ?></p>
                        </div>
                        <span class="badge badge-warning">Pendente</span>
                    </div>
                    <div class="flex items-center justify-between mt-3 pt-3 border-t border-slate-700">
                        <span class="text-xs text-slate-500">
                            <i class="fas fa-clock mr-1"></i><?= date('d/m/Y H:i', strtotime($seller['created_at'])) ?>
                        </span>
                        <a href="/admin/sellers/view/<?= $seller['id'] ?>" class="text-xs px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
                            Analisar <i class="fas fa-arrow-right ml-1"></i>
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Transações Recentes -->
    <div class="card p-6">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-lg font-bold text-white flex items-center">
                <i class="fas fa-exchange-alt text-blue-500 mr-2"></i>
                Transações Recentes
            </h2>
            <a href="/admin/transactions" class="text-blue-400 hover:text-blue-300 text-sm">
                Ver todas <i class="fas fa-arrow-right ml-1"></i>
            </a>
        </div>
        <div class="space-y-3 max-h-96 overflow-y-auto scrollbar-thin">
            <?php if (empty($recentCashin)): ?>
                <div class="text-center py-12">
                    <i class="fas fa-receipt text-5xl text-slate-600 mb-3"></i>
                    <p class="text-slate-400">Nenhuma transação ainda</p>
                </div>
            <?php else: ?>
                <?php foreach ($recentCashin as $tx): ?>
                <div class="p-4 bg-slate-800 bg-opacity-50 rounded-lg border border-slate-700 hover:border-blue-500 transition">
                    <div class="flex items-center justify-between mb-2">
                        <div>
                            <p class="font-bold text-white text-lg">R$ <?= number_format($tx['amount'], 2, ',', '.') ?></p>
                            <p class="text-xs text-slate-400">ID: <?= $tx['transaction_id'] ?></p>
                        </div>
                        <?php if (in_array($tx['status'], ['approved', 'paid'])): ?>
                            <span class="badge badge-success">
                                <i class="fas fa-check-circle mr-1"></i>Pago
                            </span>
                        <?php elseif ($tx['status'] === 'waiting_payment'): ?>
                            <span class="badge badge-warning">
                                <i class="fas fa-clock mr-1"></i>Aguardando
                            </span>
                        <?php else: ?>
                            <span class="badge badge-info">
                                <?= ucfirst($tx['status']) ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    <div class="flex items-center justify-between mt-3 pt-3 border-t border-slate-700">
                        <span class="text-xs text-slate-500">
                            <i class="fas fa-store mr-1"></i>Seller <?= $tx['seller_id'] ?>
                        </span>
                        <span class="text-xs text-slate-500">
                            <?= date('d/m H:i', strtotime($tx['created_at'])) ?>
                        </span>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Logs de Erro -->
    <div class="card p-6">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-lg font-bold text-white flex items-center">
                <i class="fas fa-exclamation-triangle text-red-500 mr-2"></i>
                Erros Recentes
            </h2>
            <a href="/admin/logs?level=error" class="text-blue-400 hover:text-blue-300 text-sm">
                Ver todos <i class="fas fa-arrow-right ml-1"></i>
            </a>
        </div>
        <div class="space-y-3 max-h-96 overflow-y-auto scrollbar-thin">
            <?php if (empty($recentLogs)): ?>
                <div class="text-center py-12">
                    <i class="fas fa-shield-alt text-5xl text-green-600 mb-3"></i>
                    <p class="text-slate-400">Nenhum erro recente</p>
                </div>
            <?php else: ?>
                <?php foreach ($recentLogs as $log): ?>
                <div class="p-4 bg-red-500 bg-opacity-10 rounded-lg border border-red-500 border-opacity-30">
                    <div class="flex items-start justify-between mb-2">
                        <div class="flex-1">
                            <p class="text-sm text-red-400 font-medium"><?= htmlspecialchars($log['message']) ?></p>
                            <p class="text-xs text-red-300 mt-1">
                                <span class="badge badge-danger inline-flex items-center">
                                    <?= strtoupper($log['category']) ?>
                                </span>
                            </p>
                        </div>
                    </div>
                    <div class="text-xs text-red-300 mt-2 pt-2 border-t border-red-500 border-opacity-20">
                        <i class="fas fa-clock mr-1"></i><?= date('d/m/Y H:i:s', strtotime($log['created_at'])) ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- System Status -->
<div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
    <!-- System Health -->
    <div class="card p-6">
        <h3 class="text-lg font-bold text-white mb-4 flex items-center">
            <i class="fas fa-heartbeat text-green-500 mr-2"></i>
            Status do Sistema
        </h3>
        <div class="space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-slate-400 text-sm">API</span>
                <span class="badge badge-success"><i class="fas fa-check-circle mr-1"></i>Online</span>
            </div>
            <div class="flex items-center justify-between">
                <span class="text-slate-400 text-sm">Database</span>
                <span class="badge badge-success"><i class="fas fa-check-circle mr-1"></i>Online</span>
            </div>
            <div class="flex items-center justify-between">
                <span class="text-slate-400 text-sm">Workers</span>
                <span class="badge badge-success"><i class="fas fa-check-circle mr-1"></i>Running</span>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="card p-6 lg:col-span-3">
        <h3 class="text-lg font-bold text-white mb-4">Ações Rápidas</h3>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            <a href="/admin/sellers" class="p-4 bg-slate-800 hover:bg-slate-700 rounded-lg text-center transition group">
                <i class="fas fa-store text-3xl text-blue-500 mb-2 group-hover:scale-110 transition"></i>
                <p class="text-sm text-slate-300">Gerenciar Sellers</p>
            </a>
            <a href="/admin/acquirers" class="p-4 bg-slate-800 hover:bg-slate-700 rounded-lg text-center transition group">
                <i class="fas fa-building text-3xl text-purple-500 mb-2 group-hover:scale-110 transition"></i>
                <p class="text-sm text-slate-300">Adquirentes</p>
            </a>
            <a href="/admin/documents" class="p-4 bg-slate-800 hover:bg-slate-700 rounded-lg text-center transition group">
                <i class="fas fa-file-alt text-3xl text-yellow-500 mb-2 group-hover:scale-110 transition"></i>
                <p class="text-sm text-slate-300">Revisar Docs</p>
            </a>
            <a href="/admin/reports" class="p-4 bg-slate-800 hover:bg-slate-700 rounded-lg text-center transition group">
                <i class="fas fa-chart-bar text-3xl text-green-500 mb-2 group-hover:scale-110 transition"></i>
                <p class="text-sm text-slate-300">Relatórios</p>
            </a>
        </div>
    </div>
</div>

<script>
<?php
$dashLabels = [];
$dashCashinTransactions = [];
$dashRevenue = [];

if (!empty($stats['daily_cashin'])) {
    foreach ($stats['daily_cashin'] as $day) {
        $dashLabels[] = date('d/m', strtotime($day['date']));
        $dashCashinTransactions[] = intval($day['transactions']);
        $dashRevenue[] = floatval($day['fees']);
    }
} else {
    for ($i = 6; $i >= 0; $i--) {
        $dashLabels[] = date('d/m', strtotime("-$i days"));
        $dashCashinTransactions[] = 0;
        $dashRevenue[] = 0;
    }
}
?>

// Transactions Volume Chart
const transactionsCtx = document.getElementById('transactionsChart').getContext('2d');
new Chart(transactionsCtx, {
    type: 'line',
    data: {
        labels: <?= json_encode($dashLabels) ?>,
        datasets: [{
            label: 'Transações Cash-in',
            data: <?= json_encode($dashCashinTransactions) ?>,
            borderColor: 'rgb(34, 197, 94)',
            backgroundColor: 'rgba(34, 197, 94, 0.1)',
            tension: 0.4,
            fill: true
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                labels: {
                    color: '#cbd5e1'
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: {
                    color: 'rgba(51, 65, 85, 0.3)'
                },
                ticks: {
                    color: '#94a3b8'
                }
            },
            x: {
                grid: {
                    color: 'rgba(51, 65, 85, 0.3)'
                },
                ticks: {
                    color: '#94a3b8'
                }
            }
        }
    }
});

// Revenue Chart
const revenueCtx = document.getElementById('revenueChart').getContext('2d');
new Chart(revenueCtx, {
    type: 'bar',
    data: {
        labels: <?= json_encode($dashLabels) ?>,
        datasets: [{
            label: 'Receita (R$)',
            data: <?= json_encode($dashRevenue) ?>,
            backgroundColor: 'rgba(59, 130, 246, 0.8)',
            borderColor: 'rgb(59, 130, 246)',
            borderWidth: 1,
            borderRadius: 6
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                labels: {
                    color: '#cbd5e1'
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: {
                    color: 'rgba(51, 65, 85, 0.3)'
                },
                ticks: {
                    color: '#94a3b8',
                    callback: function(value) {
                        return 'R$ ' + value;
                    }
                }
            },
            x: {
                grid: {
                    display: false
                },
                ticks: {
                    color: '#94a3b8'
                }
            }
        }
    }
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
