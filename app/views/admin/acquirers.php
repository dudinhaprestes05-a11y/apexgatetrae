<?php
$pageTitle = 'Adquirentes';
require_once __DIR__ . '/../layouts/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-6 md:mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl md:text-3xl font-bold text-gray-900">Adquirentes</h1>
            <p class="text-sm md:text-base text-gray-600 mt-2">Gerenciar adquirentes/PSPs integrados</p>
        </div>
        <button onclick="openCreateModal()" class="w-full md:w-auto bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-medium transition flex items-center justify-center gap-2 text-sm md:text-base whitespace-nowrap">
            <i class="fas fa-plus"></i>
            Nova Adquirente
        </button>
    </div>

    <div class="mb-4 md:mb-6 grid grid-cols-2 md:flex gap-2 md:gap-3">
        <button onclick="filterByStatus('all')" class="filter-btn px-3 md:px-4 py-2 rounded-lg text-xs md:text-sm font-medium transition bg-blue-100 text-blue-800">
            Todas
        </button>
        <button onclick="filterByStatus('active')" class="filter-btn px-3 md:px-4 py-2 rounded-lg text-xs md:text-sm font-medium transition bg-gray-100 text-gray-700 hover:bg-gray-200">
            Ativas
        </button>
        <button onclick="filterByStatus('inactive')" class="filter-btn px-3 md:px-4 py-2 rounded-lg text-xs md:text-sm font-medium transition bg-gray-100 text-gray-700 hover:bg-gray-200">
            Inativas
        </button>
        <button onclick="filterByStatus('maintenance')" class="filter-btn px-3 md:px-4 py-2 rounded-lg text-xs md:text-sm font-medium transition bg-gray-100 text-gray-700 hover:bg-gray-200">
            Manutenção
        </button>
    </div>

    <div class="grid grid-cols-1 gap-6">
        <?php if (empty($acquirers)): ?>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-12 text-center">
            <i class="fas fa-building text-gray-400 text-5xl mb-4"></i>
            <p class="text-gray-500 mb-4">Nenhuma adquirente cadastrada</p>
            <button onclick="openCreateModal()" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-medium transition">
                Cadastrar Primeira Adquirente
            </button>
        </div>
        <?php else: ?>
        <?php foreach ($acquirers as $acquirer): ?>
        <div class="acquirer-card bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover-lift" data-status="<?= $acquirer['status'] ?>">
            <div class="flex items-start justify-between mb-4">
                <div class="flex-1">
                    <div class="flex items-center gap-3 mb-3">
                        <h3 class="text-lg font-bold text-gray-900"><?= htmlspecialchars($acquirer['name']) ?></h3>
                        <span class="px-3 py-1 text-xs font-medium rounded-full
                            <?php
                            echo match($acquirer['status']) {
                                'active' => 'bg-green-100 text-green-800',
                                'inactive' => 'bg-gray-100 text-gray-800',
                                'maintenance' => 'bg-yellow-100 text-yellow-800',
                                default => 'bg-gray-100 text-gray-800'
                            };
                            ?>">
                            <?= ucfirst($acquirer['status']) ?>
                        </span>
                    </div>

                    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-4">
                        <div>
                            <label class="text-sm font-medium text-gray-600">Código</label>
                            <p class="text-gray-900 mt-1 font-mono text-sm"><?= htmlspecialchars($acquirer['code']) ?></p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-600">Contas</label>
                            <p class="text-gray-900 mt-1 font-semibold">
                                <span class="text-green-600"><?= $acquirer['active_account_count'] ?></span> / <?= $acquirer['account_count'] ?>
                            </p>
                            <p class="text-xs text-gray-500 mt-0.5">Ativas / Total</p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-600">Prioridade</label>
                            <p class="text-gray-900 mt-1"><?= $acquirer['priority_order'] ?></p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-600">Taxa de Sucesso</label>
                            <p class="text-gray-900 mt-1"><?= number_format($acquirer['success_rate'], 2) ?>%</p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-600">Tempo Resposta</label>
                            <p class="text-gray-900 mt-1"><?= $acquirer['avg_response_time'] ?>ms</p>
                        </div>
                    </div>

                    <div>
                        <label class="text-sm font-medium text-gray-600">URL da API</label>
                        <p class="text-gray-700 mt-1 text-sm font-mono break-all"><?= htmlspecialchars($acquirer['api_url']) ?></p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-3 lg:flex lg:items-center gap-2 pt-4 border-t border-gray-200">
                <button onclick="manageAccounts(<?= $acquirer['id'] ?>, '<?= htmlspecialchars($acquirer['name']) ?>')" class="lg:flex-1 bg-green-50 hover:bg-green-100 text-green-700 px-3 md:px-4 py-2 rounded-lg font-medium transition flex items-center justify-center gap-1 md:gap-2 text-xs md:text-sm">
                    <i class="fas fa-wallet"></i>
                    <span class="hidden sm:inline">Contas (<?= $acquirer['account_count'] ?>)</span>
                    <span class="sm:hidden">(<?= $acquirer['account_count'] ?>)</span>
                </button>
                <button onclick="editAcquirer(<?= $acquirer['id'] ?>)" class="lg:flex-1 bg-blue-50 hover:bg-blue-100 text-blue-700 px-3 md:px-4 py-2 rounded-lg font-medium transition flex items-center justify-center gap-1 md:gap-2 text-xs md:text-sm">
                    <i class="fas fa-edit"></i>
                    <span class="hidden sm:inline">Editar</span>
                </button>
                <button onclick="toggleStatus(<?= $acquirer['id'] ?>, '<?= $acquirer['status'] ?>')" class="lg:flex-1 bg-gray-50 hover:bg-gray-100 text-gray-700 px-3 md:px-4 py-2 rounded-lg font-medium transition flex items-center justify-center gap-1 md:gap-2 text-xs md:text-sm">
                    <i class="fas fa-power-off"></i>
                    <span class="hidden sm:inline"><?= $acquirer['status'] === 'active' ? 'Desativar' : 'Ativar' ?></span>
                </button>
                <button onclick="resetDailyLimit(<?= $acquirer['id'] ?>)" class="bg-yellow-50 hover:bg-yellow-100 text-yellow-700 px-3 md:px-4 py-2 rounded-lg font-medium transition flex items-center justify-center gap-1 md:gap-2 text-xs md:text-sm">
                    <i class="fas fa-redo"></i>
                    <span class="hidden md:inline">Reset Diário</span>
                    <span class="md:hidden">Reset</span>
                </button>
                <button onclick="deleteAcquirer(<?= $acquirer['id'] ?>, '<?= htmlspecialchars($acquirer['name']) ?>')" class="bg-red-50 hover:bg-red-100 text-red-700 px-3 md:px-4 py-2 rounded-lg font-medium transition flex items-center justify-center gap-1 text-xs md:text-sm">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<div id="acquirerModal" class="modal hidden">
    <div class="modal-content max-w-2xl bg-white">
        <div class="p-6 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h2 id="modalTitle" class="text-2xl font-bold text-gray-900">Nova Adquirente</h2>
                <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600 transition">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
        </div>

        <form id="acquirerForm" onsubmit="handleSubmit(event)" class="p-6">
            <input type="hidden" id="acquirer_id" name="id">

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nome da Adquirente *</label>
                    <input type="text" id="acquirer_name" name="name" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Ex: PodPay">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Código Único *</label>
                    <input type="text" id="acquirer_code" name="code" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Ex: podpay">
                    <p class="text-xs text-gray-500 mt-1">Use apenas letras minúsculas e números, sem espaços</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">URL da API *</label>
                    <input type="url" id="acquirer_api_url" name="api_url" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="https://api.exemplo.com">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">API Key</label>
                        <input type="text" id="acquirer_api_key" name="api_key" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">API Secret</label>
                        <input type="password" id="acquirer_api_secret" name="api_secret" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Withdraw Key (x-withdraw-key)</label>
                    <input type="text" id="acquirer_withdraw_key" name="withdraw_key" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Chave necessária para saques (PodPay)">
                    <p class="text-xs text-gray-500 mt-1">Obrigatório para adquirentes que exigem chave específica para cash-out</p>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Prioridade *</label>
                        <input type="number" id="acquirer_priority" name="priority_order" required min="1" value="1" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <p class="text-xs text-gray-500 mt-1">Menor número = maior prioridade</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status *</label>
                        <select id="acquirer_status" name="status" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="active">Ativa</option>
                            <option value="inactive">Inativa</option>
                            <option value="maintenance">Manutenção</option>
                        </select>
                    </div>
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
    document.getElementById('modalTitle').textContent = 'Nova Adquirente';
    document.getElementById('acquirerForm').reset();
    document.getElementById('acquirer_id').value = '';
    document.getElementById('acquirerModal').classList.remove('hidden');
}

function closeModal() {
    document.getElementById('acquirerModal').classList.add('hidden');
}

function editAcquirer(id) {
    fetch(`/admin/acquirers/get/${id}`)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                document.getElementById('modalTitle').textContent = 'Editar Adquirente';
                document.getElementById('acquirer_id').value = data.acquirer.id;
                document.getElementById('acquirer_name').value = data.acquirer.name;
                document.getElementById('acquirer_code').value = data.acquirer.code;
                document.getElementById('acquirer_api_url').value = data.acquirer.api_url;
                document.getElementById('acquirer_api_key').value = data.acquirer.api_key || '';
                document.getElementById('acquirer_priority').value = data.acquirer.priority_order;
                document.getElementById('acquirer_status').value = data.acquirer.status;

                const config = data.acquirer.config ? JSON.parse(data.acquirer.config) : {};
                document.getElementById('acquirer_withdraw_key').value = config.withdraw_key || '';

                document.getElementById('acquirerModal').classList.remove('hidden');
            }
        })
        .catch(err => customAlert('Erro ao carregar dados da adquirente', 'Erro', 'error'));
}

async function handleSubmit(event) {
    event.preventDefault();
    const formData = new FormData(event.target);
    const id = formData.get('id');
    const url = id ? `/admin/acquirers/update/${id}` : '/admin/acquirers/create';

    fetch(url, {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(async data => {
        if (data.success) {
            await customAlert(data.message, 'Sucesso', 'success');
            closeModal();
            location.reload();
        } else {
            await customAlert(data.error || 'Erro ao salvar adquirente', 'Erro', 'error');
        }
    })
    .catch(err => customAlert('Erro na requisição', 'Erro', 'error'));
}

async function toggleStatus(id, currentStatus) {
    const newStatus = currentStatus === 'active' ? 'inactive' : 'active';
    const action = newStatus === 'active' ? 'ativar' : 'desativar';

    const confirmed = await customConfirm(`Deseja ${action} esta adquirente?`, 'Confirmar Ação');
    if (!confirmed) return;

    fetch(`/admin/acquirers/toggle/${id}`, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({status: newStatus})
    })
    .then(res => res.json())
    .then(async data => {
        if (data.success) {
            location.reload();
        } else {
            await customAlert(data.error || 'Erro ao alterar status', 'Erro', 'error');
        }
    })
    .catch(err => customAlert('Erro na requisição', 'Erro', 'error'));
}

async function resetDailyLimit(id) {
    const confirmed = await customConfirm('Deseja resetar o limite diário desta adquirente?', 'Confirmar Reset');
    if (!confirmed) return;

    fetch(`/admin/acquirers/reset-limit/${id}`, {method: 'POST'})
        .then(res => res.json())
        .then(async data => {
            if (data.success) {
                await customAlert('Limite diário resetado com sucesso!', 'Sucesso', 'success');
                location.reload();
            } else {
                await customAlert(data.error || 'Erro ao resetar limite', 'Erro', 'error');
            }
        })
        .catch(err => customAlert('Erro na requisição', 'Erro', 'error'));
}

async function deleteAcquirer(id, name) {
    const confirmed = await customConfirm(`Tem certeza que deseja excluir a adquirente "${name}"?\n\nEsta ação não pode ser desfeita.`, 'Confirmar Exclusão');
    if (!confirmed) return;

    fetch(`/admin/acquirers/delete/${id}`, {method: 'POST'})
        .then(res => res.json())
        .then(async data => {
            if (data.success) {
                await customAlert('Adquirente excluída com sucesso!', 'Sucesso', 'success');
                location.reload();
            } else {
                await customAlert(data.error || 'Erro ao excluir adquirente', 'Erro', 'error');
            }
        })
        .catch(err => customAlert('Erro na requisição', 'Erro', 'error'));
}

function filterByStatus(status) {
    const cards = document.querySelectorAll('.acquirer-card');
    const buttons = document.querySelectorAll('.filter-btn');

    buttons.forEach(btn => {
        btn.classList.remove('bg-blue-100', 'text-blue-800');
        btn.classList.add('bg-gray-100', 'text-gray-700');
    });

    event.target.classList.remove('bg-gray-100', 'text-gray-700');
    event.target.classList.add('bg-blue-100', 'text-blue-800');

    cards.forEach(card => {
        if (status === 'all' || card.dataset.status === status) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
}

function manageAccounts(acquirerId, acquirerName) {
    window.location.href = `/admin/acquirers/${acquirerId}/accounts`;
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
