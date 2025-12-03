<?php
$pageTitle = 'Transações';
require_once __DIR__ . '/../layouts/header.php';
$type = $_GET['type'] ?? 'all';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Transações</h1>
        <p class="text-gray-600 mt-2">Todas as transações do sistema</p>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
        <form method="GET" action="/admin/transactions" class="flex items-end gap-4">
            <div class="flex-1">
                <label class="block text-sm font-medium text-gray-700 mb-2">Tipo</label>
                <select name="type" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                    <option value="all" <?= $type === 'all' ? 'selected' : '' ?>>Todas</option>
                    <option value="cashin" <?= $type === 'cashin' ? 'selected' : '' ?>>Recebimentos</option>
                    <option value="cashout" <?= $type === 'cashout' ? 'selected' : '' ?>>Saques</option>
                </select>
            </div>
            <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                <i class="fas fa-filter mr-2"></i>Filtrar
            </button>
        </form>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Data</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Seller</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipo</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Valor</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php
                $allTransactions = [];
                foreach ($cashin as $tx) {
                    $allTransactions[] = array_merge($tx, ['type' => 'cashin']);
                }
                foreach ($cashout as $tx) {
                    $allTransactions[] = array_merge($tx, ['type' => 'cashout']);
                }
                usort($allTransactions, fn($a, $b) => strtotime($b['created_at']) - strtotime($a['created_at']));
                ?>
                <?php if (empty($allTransactions)): ?>
                <tr>
                    <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                        Nenhuma transação encontrada
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($allTransactions as $tx): ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        <?= date('d/m/Y H:i', strtotime($tx['created_at'])) ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                        Seller #<?= $tx['seller_id'] ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 py-1 text-xs font-medium rounded-full <?= $tx['type'] === 'cashin' ? 'bg-blue-100 text-blue-800' : 'bg-orange-100 text-orange-800' ?>">
                            <?= $tx['type'] === 'cashin' ? 'Recebimento' : 'Saque' ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-mono text-gray-700">
                        <?= htmlspecialchars(substr($tx['transaction_id'], 0, 20)) ?>...
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                        R$ <?= number_format($tx['amount'], 2, ',', '.') ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 py-1 text-xs font-medium rounded-full
                            <?php
                            echo match($tx['status']) {
                                'approved', 'paid' => 'bg-green-100 text-green-800',
                                'waiting_payment', 'pending' => 'bg-yellow-100 text-yellow-800',
                                'cancelled', 'failed' => 'bg-red-100 text-red-800',
                                default => 'bg-gray-100 text-gray-800'
                            };
                            ?>">
                            <?= ucfirst($tx['status']) ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
