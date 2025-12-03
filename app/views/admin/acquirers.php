<?php
$pageTitle = 'Adquirentes';
require_once __DIR__ . '/../layouts/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-8 flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Adquirentes</h1>
            <p class="text-gray-600 mt-2">Gerenciar adquirentes/PSPs integrados</p>
        </div>
        <button onclick="openCreateModal()" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-medium transition flex items-center gap-2">
            <i class="fas fa-plus"></i>
            Nova Adquirente
        </button>
    </div>

    <div class="mb-6 flex gap-3">
        <button onclick="filterByStatus('all')" class="filter-btn px-4 py-2 rounded-lg text-sm font-medium transition bg-blue-100 text-blue-800">
            Todas
        </button>
        <button onclick="filterByStatus('active')" class="filter-btn px-4 py-2 rounded-lg text-sm font-medium transition bg-gray-100 text-gray-700 hover:bg-gray-200">
            Ativas
        </button>
        <button onclick="filterByStatus('inactive')" class="filter-btn px-4 py-2 rounded-lg text-sm font-medium transition bg-gray-100 text-gray-700 hover:bg-gray-200">
            Inativas
        </button>
        <button onclick="filterByStatus('maintenance')" class="filter-btn px-4 py-2 rounded-lg text-sm font-medium transition bg-gray-100 text-gray-700 hover:bg-gray-200">
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

                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
                        <div>
                            <label class="text-sm font-medium text-gray-600">Código</label>
                            <p class="text-gray-900 mt-1 font-mono text-sm"><?= htmlspecialchars($acquirer['code']) ?></p>
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

                    <div class="mb-3">
                        <label class="text-sm font-medium text-gray-600 mb-1 block">Limite Diário</label>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <?php $percentage = ($acquirer['daily_limit'] > 0) ? ($acquirer['daily_used'] / $acquirer['daily_limit']) * 100 : 0; ?>
                            <div class="bg-blue-600 h-2 rounded-full" style="width: <?= min($percentage, 100) ?>%"></div>
                        </div>
                        <div class="flex justify-between text-xs text-gray-600 mt-1">
                            <span>Usado: R$ <?= number_format($acquirer['daily_used'], 2, ',', '.') ?></span>
                            <span>Limite: R$ <?= number_format($acquirer['daily_limit'], 2, ',', '.') ?></span>
                        </div>
                    </div>

                    <div>
                        <label class="text-sm font-medium text-gray-600">URL da API</label>
                        <p class="text-gray-700 mt-1 text-sm font-mono break-all"><?= htmlspecialchars($acquirer['api_url']) ?></p>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-2 pt-4 border-t border-gray-200">
                <button onclick="editAcquirer(<?= $acquirer['id'] ?>)" class="flex-1 bg-blue-50 hover:bg-blue-100 text-blue-700 px-4 py-2 rounded-lg font-medium transition flex items-center justify-center gap-2">
                    <i class="fas fa-edit"></i>
                    Editar
                </button>
                <button onclick="toggleStatus(<?= $acquirer['id'] ?>, '<?= $acquirer['status'] ?>')" class="flex-1 bg-gray-50 hover:bg-gray-100 text-gray-700 px-4 py-2 rounded-lg font-medium transition flex items-center justify-center gap-2">
                    <i class="fas fa-power-off"></i>
                    <?= $acquirer['status'] === 'active' ? 'Desativar' : 'Ativar' ?>
                </button>
                <button onclick="resetDailyLimit(<?= $acquirer['id'] ?>)" class="bg-yellow-50 hover:bg-yellow-100 text-yellow-700 px-4 py-2 rounded-lg font-medium transition flex items-center gap-2">
                    <i class="fas fa-redo"></i>
                    Reset Diário
                </button>
                <button onclick="deleteAcquirer(<?= $acquirer['id'] ?>, '<?= htmlspecialchars($acquirer['name']) ?>')" class="bg-red-50 hover:bg-red-100 text-red-700 px-4 py-2 rounded-lg font-medium transition flex items-center gap-2">
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

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Limite Diário (R$) *</label>
                    <input type="number" id="acquirer_daily_limit" name="daily_limit" required min="0" step="0.01" value="100000.00" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
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
                document.getElementById('acquirer_daily_limit').value = data.acquirer.daily_limit;

                const config = data.acquirer.config ? JSON.parse(data.acquirer.config) : {};
                document.getElementById('acquirer_withdraw_key').value = config.withdraw_key || '';

                document.getElementById('acquirerModal').classList.remove('hidden');
            }
        })
        .catch(err => alert('Erro ao carregar dados da adquirente'));
}

function handleSubmit(event) {
    event.preventDefault();
    const formData = new FormData(event.target);
    const id = formData.get('id');
    const url = id ? `/admin/acquirers/update/${id}` : '/admin/acquirers/create';

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
            alert(data.error || 'Erro ao salvar adquirente');
        }
    })
    .catch(err => alert('Erro na requisição'));
}

function toggleStatus(id, currentStatus) {
    const newStatus = currentStatus === 'active' ? 'inactive' : 'active';
    const action = newStatus === 'active' ? 'ativar' : 'desativar';

    if (!confirm(`Deseja ${action} esta adquirente?`)) return;

    fetch(`/admin/acquirers/toggle/${id}`, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({status: newStatus})
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
    if (!confirm('Deseja resetar o limite diário desta adquirente?')) return;

    fetch(`/admin/acquirers/reset-limit/${id}`, {method: 'POST'})
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

function deleteAcquirer(id, name) {
    if (!confirm(`Tem certeza que deseja excluir a adquirente "${name}"?\n\nEsta ação não pode ser desfeita.`)) return;

    fetch(`/admin/acquirers/delete/${id}`, {method: 'POST'})
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert('Adquirente excluída com sucesso!');
                location.reload();
            } else {
                alert(data.error || 'Erro ao excluir adquirente');
            }
        })
        .catch(err => alert('Erro na requisição'));
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
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
