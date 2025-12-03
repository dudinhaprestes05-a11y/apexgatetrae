<?php
$pageTitle = 'Detalhes do Seller';
require_once __DIR__ . '/../layouts/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-8 flex items-center justify-between">
        <div>
            <a href="/admin/sellers" class="text-blue-600 hover:text-blue-800 text-sm mb-2 inline-block">
                <i class="fas fa-arrow-left mr-1"></i>Voltar
            </a>
            <h1 class="text-3xl font-bold text-gray-900"><?= htmlspecialchars($seller['name']) ?></h1>
            <p class="text-gray-600 mt-2"><?= htmlspecialchars($seller['email']) ?></p>
        </div>
        <div>
            <span class="px-4 py-2 rounded-lg text-sm font-medium
                <?php
                echo match($seller['status']) {
                    'active' => 'bg-green-100 text-green-800',
                    'pending' => 'bg-yellow-100 text-yellow-800',
                    'inactive' => 'bg-gray-100 text-gray-800',
                    'blocked' => 'bg-red-100 text-red-800',
                    'rejected' => 'bg-red-100 text-red-800',
                    default => 'bg-gray-100 text-gray-800'
                };
                ?>">
                <?= ucfirst($seller['status']) ?>
            </span>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <p class="text-gray-600 text-sm font-medium">Saldo</p>
            <p class="text-3xl font-bold text-gray-900 mt-2">R$ <?= number_format($seller['balance'], 2, ',', '.') ?></p>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <p class="text-gray-600 text-sm font-medium">Total Recebido</p>
            <p class="text-3xl font-bold text-gray-900 mt-2">R$ <?= number_format($cashinStats['total'] ?? 0, 2, ',', '.') ?></p>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <p class="text-gray-600 text-sm font-medium">Total Sacado</p>
            <p class="text-3xl font-bold text-gray-900 mt-2">R$ <?= number_format($cashoutStats['total'] ?? 0, 2, ',', '.') ?></p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h2 class="text-lg font-bold text-gray-900 mb-4">Informações</h2>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="text-sm font-medium text-gray-600">ID</label>
                        <p class="text-gray-900 mt-1">#<?= $seller['id'] ?></p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-600">Tipo de Pessoa</label>
                        <p class="text-gray-900 mt-1"><?= $seller['person_type'] === 'individual' ? 'Pessoa Física' : 'Pessoa Jurídica' ?></p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-600">Documento</label>
                        <p class="text-gray-900 mt-1"><?= htmlspecialchars($seller['document']) ?></p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-600">Telefone</label>
                        <p class="text-gray-900 mt-1"><?= htmlspecialchars($seller['phone'] ?? 'Não informado') ?></p>
                    </div>
                    <?php if ($seller['person_type'] === 'business'): ?>
                    <div class="col-span-2">
                        <label class="text-sm font-medium text-gray-600">Razão Social</label>
                        <p class="text-gray-900 mt-1"><?= htmlspecialchars($seller['company_name'] ?? 'Não informado') ?></p>
                    </div>
                    <?php if ($seller['trading_name']): ?>
                    <div class="col-span-2">
                        <label class="text-sm font-medium text-gray-600">Nome Fantasia</label>
                        <p class="text-gray-900 mt-1"><?= htmlspecialchars($seller['trading_name']) ?></p>
                    </div>
                    <?php endif; ?>
                    <?php endif; ?>
                    <div>
                        <label class="text-sm font-medium text-gray-600">Cadastro</label>
                        <p class="text-gray-900 mt-1"><?= date('d/m/Y H:i', strtotime($seller['created_at'])) ?></p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-600">Última Atualização</label>
                        <p class="text-gray-900 mt-1"><?= date('d/m/Y H:i', strtotime($seller['updated_at'])) ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h2 class="text-lg font-bold text-gray-900 mb-4">Documentos</h2>
                <div class="grid grid-cols-2 gap-4">
                    <?php if (empty($documents)): ?>
                        <p class="col-span-2 text-gray-500 text-center py-8">Nenhum documento enviado</p>
                    <?php else: ?>
                        <?php foreach ($documents as $doc): ?>
                        <div class="p-4 border border-gray-200 rounded-lg">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm font-medium text-gray-900"><?= ucfirst(str_replace('_', ' ', $doc['document_type'])) ?></span>
                                <span class="px-2 py-1 text-xs font-medium rounded-full
                                    <?php
                                    echo match($doc['status']) {
                                        'approved' => 'bg-green-100 text-green-800',
                                        'pending' => 'bg-yellow-100 text-yellow-800',
                                        'under_review' => 'bg-blue-100 text-blue-800',
                                        'rejected' => 'bg-red-100 text-red-800',
                                        default => 'bg-gray-100 text-gray-800'
                                    };
                                    ?>">
                                    <?= ucfirst($doc['status']) ?>
                                </span>
                            </div>
                            <p class="text-xs text-gray-500"><?= date('d/m/Y H:i', strtotime($doc['created_at'])) ?></p>
                            <a href="/admin/documents/view/<?= $doc['id'] ?>" class="text-blue-600 hover:text-blue-800 text-xs font-medium mt-2 inline-block">
                                Ver documento <i class="fas fa-arrow-right ml-1"></i>
                            </a>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (!empty($recentTransactions)): ?>
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h2 class="text-lg font-bold text-gray-900 mb-4">Transações Recentes</h2>
                <div class="space-y-3">
                    <?php foreach ($recentTransactions as $tx): ?>
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                        <div>
                            <p class="font-medium text-gray-900">R$ <?= number_format($tx['amount'], 2, ',', '.') ?></p>
                            <p class="text-xs text-gray-600"><?= date('d/m/Y H:i', strtotime($tx['created_at'])) ?></p>
                        </div>
                        <span class="px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            <?= ucfirst($tx['status']) ?>
                        </span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <div class="space-y-6">
            <?php if ($seller['status'] === 'pending'): ?>
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="font-bold text-gray-900 mb-4">Ações</h3>
                <div class="space-y-3">
                    <form method="POST" action="/admin/sellers/<?= $seller['id'] ?>/approve" onsubmit="return confirm('Tem certeza que deseja aprovar este seller?')">
                        <button type="submit" class="w-full bg-green-600 text-white py-3 rounded-lg font-medium hover:bg-green-700 transition">
                            <i class="fas fa-check mr-2"></i>Aprovar Seller
                        </button>
                    </form>
                    <button onclick="showRejectModal()" class="w-full bg-red-600 text-white py-3 rounded-lg font-medium hover:bg-red-700 transition">
                        <i class="fas fa-times mr-2"></i>Rejeitar Seller
                    </button>
                </div>
            </div>
            <?php endif; ?>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="font-bold text-gray-900 mb-4">Taxas</h3>
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600">Cash-in</span>
                        <span class="text-sm font-medium text-gray-900"><?= number_format($seller['fee_percentage_cashin'] * 100, 2) ?>%</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600">Cash-out</span>
                        <span class="text-sm font-medium text-gray-900"><?= number_format($seller['fee_percentage_cashout'] * 100, 2) ?>%</span>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="font-bold text-gray-900 mb-4">Limites</h3>
                <div class="space-y-3">
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-sm text-gray-600">Limite Diário</span>
                            <span class="text-sm font-medium text-gray-900">R$ <?= number_format($seller['daily_limit'], 2, ',', '.') ?></span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <?php $percentage = ($seller['daily_used'] / $seller['daily_limit']) * 100; ?>
                            <div class="bg-blue-600 h-2 rounded-full" style="width: <?= min($percentage, 100) ?>%"></div>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">Usado: R$ <?= number_format($seller['daily_used'], 2, ',', '.') ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="rejectModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-xl p-6 max-w-md w-full mx-4">
        <h3 class="text-lg font-bold text-gray-900 mb-4">Rejeitar Seller</h3>
        <form method="POST" action="/admin/sellers/<?= $seller['id'] ?>/reject">
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Motivo da Rejeição</label>
                <textarea name="reason" required rows="4"
                          class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                          placeholder="Explique o motivo da rejeição..."></textarea>
            </div>
            <div class="flex items-center gap-3">
                <button type="button" onclick="hideRejectModal()" class="flex-1 bg-gray-200 text-gray-700 py-3 rounded-lg font-medium hover:bg-gray-300 transition">
                    Cancelar
                </button>
                <button type="submit" class="flex-1 bg-red-600 text-white py-3 rounded-lg font-medium hover:bg-red-700 transition">
                    Rejeitar
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function showRejectModal() {
    document.getElementById('rejectModal').classList.remove('hidden');
    document.getElementById('rejectModal').classList.add('flex');
}

function hideRejectModal() {
    document.getElementById('rejectModal').classList.add('hidden');
    document.getElementById('rejectModal').classList.remove('flex');
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
