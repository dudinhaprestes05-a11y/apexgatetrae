<?php
$pageTitle = 'Contas - ' . htmlspecialchars($acquirer['name']);
require_once __DIR__ . '/../layouts/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-8">
        <a href="/admin/acquirers" class="text-blue-600 hover:text-blue-800 text-sm mb-2 inline-block">
            <i class="fas fa-arrow-left mr-1"></i>Voltar para Adquirentes
        </a>
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Contas - <?= htmlspecialchars($acquirer['name']) ?></h1>
                <p class="text-gray-600 mt-2">Gerenciar múltiplas contas para distribuição de transações</p>
            </div>
            <button onclick="openCreateModal()" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-medium transition flex items-center gap-2">
                <i class="fas fa-plus"></i>
                Nova Conta
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-sm font-medium text-gray-600">Total de Contas</h3>
                <i class="fas fa-wallet text-blue-600"></i>
            </div>
            <p class="text-3xl font-bold text-gray-900"><?= count($accounts) ?></p>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-sm font-medium text-gray-600">Contas Ativas</h3>
                <i class="fas fa-check-circle text-green-600"></i>
            </div>
            <p class="text-3xl font-bold text-green-600">
                <?= count(array_filter($accounts, fn($a) => $a['is_active'])) ?>
            </p>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-sm font-medium text-gray-600">Saldo Total</h3>
                <i class="fas fa-wallet text-purple-600"></i>
            </div>
            <p class="text-3xl font-bold text-gray-900">
                R$ <?= number_format(array_sum(array_column($accounts, 'balance')), 2, ',', '.') ?>
            </p>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-sm font-medium text-gray-600">Volume Total</h3>
                <i class="fas fa-chart-bar text-orange-600"></i>
            </div>
            <p class="text-3xl font-bold text-orange-600">
                R$ <?= number_format(array_sum(array_column($accounts, 'total_volume')), 2, ',', '.') ?>
            </p>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6">
        <?php if (empty($accounts)): ?>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-12 text-center">
            <i class="fas fa-wallet text-gray-400 text-5xl mb-4"></i>
            <p class="text-gray-500 mb-4">Nenhuma conta cadastrada para esta adquirente</p>
            <button onclick="openCreateModal()" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-medium transition">
                Cadastrar Primeira Conta
            </button>
        </div>
        <?php else: ?>
        <?php foreach ($accounts as $account): ?>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover-lift">
            <div class="flex items-start justify-between mb-4">
                <div class="flex-1">
                    <div class="flex items-center gap-3 mb-3">
                        <h3 class="text-lg font-bold text-gray-900"><?= htmlspecialchars($account['name']) ?></h3>
                        <span class="px-3 py-1 text-xs font-medium rounded-full <?= $account['is_active'] ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' ?>">
                            <?= $account['is_active'] ? 'Ativa' : 'Inativa' ?>
                        </span>
                        <?php if ($account['is_default']): ?>
                        <span class="px-3 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800">
                            <i class="fas fa-star mr-1"></i>Padrão
                        </span>
                        <?php endif; ?>
                    </div>

                    <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-4">
                        <div>
                            <label class="text-sm font-medium text-gray-600">Merchant ID</label>
                            <p class="text-gray-900 mt-1 text-xs font-mono"><?= htmlspecialchars(substr($account['merchant_id'], 0, 20)) ?>...</p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-600">Status</label>
                            <p class="text-gray-900 mt-1">
                                <span class="px-2 py-1 text-xs rounded-full <?= $account['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                    <?= $account['is_active'] ? 'Ativa' : 'Inativa' ?>
                                </span>
                            </p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-600">Conta Padrão</label>
                            <p class="text-gray-900 mt-1">
                                <span class="px-2 py-1 text-xs rounded-full <?= $account['is_default'] ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800' ?>">
                                    <?= $account['is_default'] ? 'Sim' : 'Não' ?>
                                </span>
                            </p>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="text-sm font-medium text-gray-600 mb-1 block">Saldo da Conta</label>
                        <div class="flex justify-between items-center">
                            <span class="text-2xl font-bold text-gray-900">R$ <?= number_format($account['balance'] ?? 0, 2, ',', '.') ?></span>
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-4 text-center py-3 border-t border-b border-gray-200">
                        <div>
                            <p class="text-2xl font-bold text-gray-900"><?= $account['total_transactions'] ?? 0 ?></p>
                            <p class="text-xs text-gray-600 mt-1">Transações</p>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-green-600"><?= number_format($account['success_rate'] ?? 0, 1) ?>%</p>
                            <p class="text-xs text-gray-600 mt-1">Taxa Sucesso</p>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-blue-600">R$ <?= number_format($account['total_volume'] ?? 0, 2, ',', '.') ?></p>
                            <p class="text-xs text-gray-600 mt-1">Volume Total</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-2 pt-4 border-t border-gray-200">
                <button onclick="viewAccountDetails(<?= $account['id'] ?>)" class="flex-1 bg-blue-50 hover:bg-blue-100 text-blue-700 px-4 py-2 rounded-lg font-medium transition flex items-center justify-center gap-2">
                    <i class="fas fa-eye"></i>
                    Detalhes
                </button>
                <button onclick="editAccount(<?= $account['id'] ?>)" class="flex-1 bg-gray-50 hover:bg-gray-100 text-gray-700 px-4 py-2 rounded-lg font-medium transition flex items-center justify-center gap-2">
                    <i class="fas fa-edit"></i>
                    Editar
                </button>
                <button onclick="toggleAccount(<?= $account['id'] ?>, <?= $account['is_active'] ? 'false' : 'true' ?>)" class="flex-1 <?= $account['is_active'] ? 'bg-yellow-50 hover:bg-yellow-100 text-yellow-700' : 'bg-green-50 hover:bg-green-100 text-green-700' ?> px-4 py-2 rounded-lg font-medium transition flex items-center justify-center gap-2">
                    <i class="fas fa-power-off"></i>
                    <?= $account['is_active'] ? 'Desativar' : 'Ativar' ?>
                </button>
                <button onclick="resetDailyLimit(<?= $account['id'] ?>)" class="bg-orange-50 hover:bg-orange-100 text-orange-700 px-4 py-2 rounded-lg font-medium transition flex items-center gap-2">
                    <i class="fas fa-redo"></i>
                    Reset
                </button>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<div id="accountModal" class="modal hidden">
    <div class="modal-content max-w-2xl bg-white">
        <div class="p-6 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h2 id="modalTitle" class="text-2xl font-bold text-gray-900">Nova Conta</h2>
                <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600 transition">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
        </div>

        <form id="accountForm" onsubmit="handleSubmit(event)" class="p-6">
            <input type="hidden" id="account_id" name="id">
            <input type="hidden" name="acquirer_id" value="<?= $acquirer['id'] ?>">

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nome da Conta *</label>
                    <input type="text" id="account_name" name="name" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Ex: Conta Principal">
                </div>

                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Client ID *</label>
                        <input type="text" id="account_client_id" name="client_id" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Client Secret <span id="secret_required_indicator">*</span></label>
                        <input type="password" id="account_client_secret" name="client_secret" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <p id="secret_help_text" class="text-xs text-gray-500 mt-1 hidden">Deixe vazio para manter o atual</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Merchant ID *</label>
                        <input type="text" id="account_merchant_id" name="merchant_id" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>

                <!-- Transaction Limits -->
                <div class="border-t pt-4 mt-4">
                    <h4 class="text-sm font-semibold text-gray-900 mb-3">Limites por Transação</h4>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Max Cash-in (R$)</label>
                            <input type="text" id="account_max_cashin" name="max_cashin_per_transaction" placeholder="Sem limite" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <p class="text-xs text-gray-500 mt-1">Deixe vazio para sem limite</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Max Cash-out (R$)</label>
                            <input type="text" id="account_max_cashout" name="max_cashout_per_transaction" placeholder="Sem limite" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <p class="text-xs text-gray-500 mt-1">Deixe vazio para sem limite</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Min Cash-in (R$)</label>
                            <input type="text" id="account_min_cashin" name="min_cashin_per_transaction" value="0.01" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Min Cash-out (R$)</label>
                            <input type="text" id="account_min_cashout" name="min_cashout_per_transaction" value="0.01" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" id="account_is_active" name="is_active" checked class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                        <span class="text-sm font-medium text-gray-700">Conta Ativa</span>
                    </label>

                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" id="account_is_default" name="is_default" class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                        <span class="text-sm font-medium text-gray-700">Conta Padrão</span>
                    </label>
                </div>
            </div>

            <div class="mt-6 flex gap-3">
                <button type="button" onclick="closeModal()" class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 px-6 py-3 rounded-lg font-medium transition">
                    Cancelar
                </button>
                <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-medium transition">
                    Salvar
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openCreateModal() {
    document.getElementById('modalTitle').textContent = 'Nova Conta';
    document.getElementById('accountForm').reset();
    document.getElementById('account_id').value = '';
    document.getElementById('account_is_active').checked = true;
    document.getElementById('account_client_secret').required = true;
    document.getElementById('secret_required_indicator').classList.remove('hidden');
    document.getElementById('secret_help_text').classList.add('hidden');
    document.getElementById('accountModal').classList.remove('hidden');
}

function closeModal() {
    document.getElementById('accountModal').classList.add('hidden');
}

function editAccount(id) {
    fetch(`/admin/acquirers/accounts/get/${id}`)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const account = data.account;
                document.getElementById('modalTitle').textContent = 'Editar Conta';
                document.getElementById('account_id').value = account.id;
                document.getElementById('account_name').value = account.name;
                document.getElementById('account_client_id').value = account.client_id || '';
                document.getElementById('account_client_secret').value = '';
                document.getElementById('account_client_secret').required = false;
                document.getElementById('secret_required_indicator').classList.add('hidden');
                document.getElementById('secret_help_text').classList.remove('hidden');
                document.getElementById('account_merchant_id').value = account.merchant_id || '';
                document.getElementById('account_max_cashin').value = account.max_cashin_per_transaction || '';
                document.getElementById('account_max_cashout').value = account.max_cashout_per_transaction || '';
                document.getElementById('account_min_cashin').value = account.min_cashin_per_transaction || '0.01';
                document.getElementById('account_min_cashout').value = account.min_cashout_per_transaction || '0.01';
                document.getElementById('account_is_active').checked = account.is_active;
                document.getElementById('account_is_default').checked = account.is_default;
                document.getElementById('accountModal').classList.remove('hidden');
            }
        })
        .catch(err => alert('Erro ao carregar dados da conta'));
}

function handleSubmit(event) {
    event.preventDefault();
    const formData = new FormData(event.target);
    const id = formData.get('id');
    const url = id ? `/admin/acquirers/accounts/update/${id}` : '/admin/acquirers/accounts/create';

    fetch(url, {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            closeModal();
            location.reload();
        } else {
            alert(data.error || 'Erro ao salvar conta');
        }
    })
    .catch(err => alert('Erro na requisição'));
}

function toggleAccount(id, activate) {
    const action = activate ? 'ativar' : 'desativar';
    if (!confirm(`Deseja ${action} esta conta?`)) return;

    fetch(`/admin/acquirers/accounts/toggle/${id}`, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({is_active: activate})
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.error || 'Erro ao alterar status');
        }
    })
    .catch(err => alert('Erro na requisição'));
}

function resetDailyLimit(id) {
    if (!confirm('Deseja resetar o limite diário desta conta?')) return;

    fetch(`/admin/acquirers/accounts/reset-limit/${id}`, {method: 'POST'})
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert('Limite diário resetado com sucesso!');
                location.reload();
            } else {
                alert(data.error || 'Erro ao resetar limite');
            }
        })
        .catch(err => alert('Erro na requisição'));
}

function viewAccountDetails(id) {
    window.location.href = `/admin/acquirers/accounts/${id}/details`;
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
