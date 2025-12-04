<?php
$pageTitle = 'Whitelist de IPs';
require_once __DIR__ . '/../layouts/header.php';
?>

<div class="max-w-4xl mx-auto">
    <div class="mb-6 md:mb-8">
        <h1 class="text-2xl md:text-3xl font-bold text-gray-900">Whitelist de IPs</h1>
        <p class="text-sm md:text-base text-gray-600 mt-2">Gerencie os endereços IP autorizados a acessar sua API</p>
    </div>

    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
        <div class="flex items-start">
            <i class="fas fa-shield-alt text-yellow-600 text-xl mr-3 mt-1"></i>
            <div class="flex-1">
                <h3 class="font-semibold text-yellow-900">Segurança Adicional - Ativa por Padrão</h3>
                <p class="text-yellow-700 text-sm mt-1">
                    A whitelist de IPs está ativa por padrão. Quando não há IPs cadastrados, todos os IPs são permitidos.
                    Ao adicionar IPs, apenas os endereços cadastrados terão acesso. Você pode desativar a qualquer momento.
                </p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-6 mb-6">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="text-base md:text-lg font-bold text-gray-900">Status da Whitelist</h2>
                <p class="text-sm text-gray-600 mt-1">Seu IP atual: <code class="text-blue-600"><?= htmlspecialchars($clientIp) ?></code></p>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" id="whitelistToggle" class="sr-only peer" <?= $seller['ip_whitelist_enabled'] ? 'checked' : '' ?>>
                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                <span class="ml-3 text-sm font-medium text-gray-900" id="toggleLabel">
                    <?= $seller['ip_whitelist_enabled'] ? 'Ativada' : 'Desativada' ?>
                </span>
            </label>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-6 mb-6">
        <h2 class="text-base md:text-lg font-bold text-gray-900 mb-4">Adicionar IP</h2>

        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Endereço IP ou CIDR
                    <span class="text-gray-500 font-normal">(Ex: 192.168.1.1 ou 192.168.1.0/24)</span>
                </label>
                <input type="text" id="ipAddress" placeholder="192.168.1.1 ou 192.168.1.0/24"
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Descrição (opcional)
                </label>
                <input type="text" id="ipDescription" placeholder="Ex: Servidor de produção"
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>

            <button onclick="addIp()" class="w-full sm:w-auto bg-blue-600 text-white px-6 py-3 rounded-lg font-medium hover:bg-blue-700 transition">
                <i class="fas fa-plus mr-2"></i>Adicionar IP
            </button>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-base md:text-lg font-bold text-gray-900">IPs Autorizados</h2>
            <span class="text-sm text-gray-600" id="ipCount"><?= count($whitelist) ?> / 50 IPs</span>
        </div>

        <div id="ipList" class="space-y-3">
            <?php if (empty($whitelist)): ?>
                <div class="text-center py-8 text-gray-500">
                    <i class="fas fa-list text-4xl mb-2"></i>
                    <p>Nenhum IP cadastrado</p>
                </div>
            <?php else: ?>
                <?php foreach ($whitelist as $entry): ?>
                    <div class="flex items-start justify-between p-4 bg-gray-50 border border-gray-200 rounded-lg hover:bg-gray-100 transition">
                        <div class="flex-1">
                            <div class="font-mono text-sm font-semibold text-gray-900">
                                <?= htmlspecialchars($entry['ip']) ?>
                            </div>
                            <?php if (!empty($entry['description'])): ?>
                                <div class="text-sm text-gray-600 mt-1">
                                    <?= htmlspecialchars($entry['description']) ?>
                                </div>
                            <?php endif; ?>
                            <div class="text-xs text-gray-500 mt-1">
                                Adicionado em: <?= date('d/m/Y H:i', strtotime($entry['added_at'])) ?>
                            </div>
                        </div>
                        <button onclick="removeIp('<?= htmlspecialchars($entry['ip']) ?>')"
                                class="ml-4 text-red-600 hover:text-red-700 transition">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="bg-blue-50 border border-blue-200 rounded-xl p-6 mt-6">
        <h2 class="text-lg font-bold text-blue-900 mb-4">
            <i class="fas fa-info-circle mr-2"></i>Informações Importantes
        </h2>
        <ul class="space-y-2 text-blue-800 text-sm">
            <li><i class="fas fa-check mr-2"></i>A whitelist está ativa por padrão para todos os sellers</li>
            <li><i class="fas fa-check mr-2"></i>Quando ativa e sem IPs cadastrados, todos os IPs são permitidos</li>
            <li><i class="fas fa-check mr-2"></i>Ao adicionar IPs, apenas os endereços cadastrados terão acesso</li>
            <li><i class="fas fa-check mr-2"></i>Você pode cadastrar até 50 endereços IP</li>
            <li><i class="fas fa-check mr-2"></i>Suporta IPs individuais (ex: 192.168.1.1) e ranges CIDR (ex: 192.168.1.0/24)</li>
            <li><i class="fas fa-info-circle mr-2"></i>Você pode desativar a whitelist para sempre permitir todos os IPs</li>
            <li><i class="fas fa-exclamation-triangle mr-2"></i>A whitelist não afeta o acesso ao painel web, apenas à API</li>
        </ul>
    </div>
</div>

<script>
async function toggleWhitelist() {
    const toggle = document.getElementById('whitelistToggle');
    const label = document.getElementById('toggleLabel');
    const enabled = toggle.checked;

    try {
        const response = await fetch('/seller/ip-whitelist/toggle', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ enabled })
        });

        const result = await response.json();

        if (result.success) {
            label.textContent = enabled ? 'Ativada' : 'Desativada';
            showNotification('Status atualizado com sucesso!', 'success');
        } else {
            toggle.checked = !enabled;
            showNotification(result.error || 'Erro ao atualizar status', 'error');
        }
    } catch (error) {
        toggle.checked = !enabled;
        showNotification('Erro ao comunicar com o servidor', 'error');
    }
}

async function addIp() {
    const ipInput = document.getElementById('ipAddress');
    const descInput = document.getElementById('ipDescription');
    const ip = ipInput.value.trim();
    const description = descInput.value.trim();

    if (!ip) {
        showNotification('Digite um endereço IP', 'error');
        return;
    }

    try {
        const response = await fetch('/seller/ip-whitelist/add', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ ip, description })
        });

        const result = await response.json();

        if (result.success) {
            showNotification('IP adicionado com sucesso!', 'success');
            ipInput.value = '';
            descInput.value = '';
            loadWhitelist();
        } else {
            showNotification(result.error || 'Erro ao adicionar IP', 'error');
        }
    } catch (error) {
        showNotification('Erro ao comunicar com o servidor', 'error');
    }
}

async function removeIp(ip) {
    if (!confirm('Tem certeza que deseja remover este IP?')) {
        return;
    }

    try {
        const response = await fetch('/seller/ip-whitelist/remove', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ ip })
        });

        const result = await response.json();

        if (result.success) {
            showNotification('IP removido com sucesso!', 'success');
            loadWhitelist();
        } else {
            showNotification(result.error || 'Erro ao remover IP', 'error');
        }
    } catch (error) {
        showNotification('Erro ao comunicar com o servidor', 'error');
    }
}

async function loadWhitelist() {
    try {
        const response = await fetch('/seller/ip-whitelist/get');
        const result = await response.json();

        if (result.success) {
            const ipList = document.getElementById('ipList');
            const ipCount = document.getElementById('ipCount');

            ipCount.textContent = `${result.whitelist.length} / 50 IPs`;

            if (result.whitelist.length === 0) {
                ipList.innerHTML = `
                    <div class="text-center py-8 text-gray-500">
                        <i class="fas fa-list text-4xl mb-2"></i>
                        <p>Nenhum IP cadastrado</p>
                    </div>
                `;
            } else {
                ipList.innerHTML = result.whitelist.map(entry => `
                    <div class="flex items-start justify-between p-4 bg-gray-50 border border-gray-200 rounded-lg hover:bg-gray-100 transition">
                        <div class="flex-1">
                            <div class="font-mono text-sm font-semibold text-gray-900">
                                ${escapeHtml(entry.ip)}
                            </div>
                            ${entry.description ? `
                                <div class="text-sm text-gray-600 mt-1">
                                    ${escapeHtml(entry.description)}
                                </div>
                            ` : ''}
                            <div class="text-xs text-gray-500 mt-1">
                                Adicionado em: ${formatDate(entry.added_at)}
                            </div>
                        </div>
                        <button onclick="removeIp('${escapeHtml(entry.ip)}')"
                                class="ml-4 text-red-600 hover:text-red-700 transition">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                `).join('');
            }
        }
    } catch (error) {
        console.error('Error loading whitelist:', error);
    }
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleString('pt-BR');
}

function showNotification(message, type) {
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 z-50 px-6 py-3 rounded-lg shadow-lg ${
        type === 'success' ? 'bg-green-500' : 'bg-red-500'
    } text-white`;
    notification.textContent = message;

    document.body.appendChild(notification);

    setTimeout(() => {
        notification.remove();
    }, 3000);
}

document.getElementById('whitelistToggle').addEventListener('change', toggleWhitelist);

document.getElementById('ipAddress').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        addIp();
    }
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
