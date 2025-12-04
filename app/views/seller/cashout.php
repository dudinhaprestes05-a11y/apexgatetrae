<?php
$pageTitle = 'Saque';
require_once __DIR__ . '/../layouts/header.php';
?>

<div class="max-w-7xl mx-auto">
    <div class="mb-6 md:mb-8">
        <h1 class="text-2xl md:text-3xl font-bold text-white">Saque</h1>
        <p class="text-sm md:text-base text-slate-400 mt-2">Realize saques do seu saldo disponível</p>
    </div>

    <?php if ($seller['status'] !== 'active'): ?>
    <div class="mb-6 bg-yellow-900 bg-opacity-30 border border-yellow-700 rounded-lg p-4">
        <div class="flex items-center">
            <i class="fas fa-exclamation-triangle text-yellow-500 text-xl mr-3"></i>
            <div>
                <h3 class="font-semibold text-yellow-300">Conta Não Ativa</h3>
                <p class="text-yellow-400 text-sm mt-1">Sua conta precisa estar ativa para realizar saques.</p>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!$seller['cashout_enabled']): ?>
    <div class="mb-6 bg-red-900 bg-opacity-30 border border-red-700 rounded-lg p-4">
        <div class="flex items-center">
            <i class="fas fa-times-circle text-red-500 text-xl mr-3"></i>
            <div>
                <h3 class="font-semibold text-red-300">Saque Desabilitado</h3>
                <p class="text-red-400 text-sm mt-1">A funcionalidade de saque não está habilitada para sua conta. Entre em contato com o suporte.</p>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-xl border border-slate-700 p-6 shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-slate-400 text-sm font-medium">Saldo Disponível</p>
                    <p class="text-3xl font-bold text-white mt-2">R$ <?= number_format($seller['balance'], 2, ',', '.') ?></p>
                </div>
                <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-green-600 rounded-lg flex items-center justify-center">
                    <i class="fas fa-wallet text-white text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-xl border border-slate-700 p-6 shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-slate-400 text-sm font-medium">Taxa de Saque</p>
                    <p class="text-2xl font-bold text-white mt-2"><?= number_format($feePercentage * 100, 2) ?>%</p>
                    <?php if ($feeFixed > 0): ?>
                    <p class="text-xs text-slate-400 mt-1">+ R$ <?= number_format($feeFixed, 2, ',', '.') ?></p>
                    <?php endif; ?>
                </div>
                <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg flex items-center justify-center">
                    <i class="fas fa-percentage text-white text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-xl border border-slate-700 p-6 shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-slate-400 text-sm font-medium">Saques Hoje</p>
                    <p class="text-2xl font-bold text-white mt-2"><?= count(array_filter($recentCashouts, function($c) { return date('Y-m-d', strtotime($c['created_at'])) === date('Y-m-d'); })) ?></p>
                </div>
                <div class="w-12 h-12 bg-gradient-to-br from-orange-500 to-orange-600 rounded-lg flex items-center justify-center">
                    <i class="fas fa-chart-line text-white text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="card p-6">
            <h2 class="text-xl font-bold text-white mb-6 flex items-center">
                <i class="fas fa-money-bill-wave text-green-500 mr-3"></i>
                Solicitar Saque
            </h2>

            <form method="POST" action="/seller/cashout/process" id="cashoutForm">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">Valor do Saque (R$)</label>
                        <input type="number"
                               name="amount"
                               id="amount"
                               step="0.01"
                               min="0.01"
                               max="<?= $seller['balance'] ?>"
                               class="w-full px-4 py-3 bg-slate-900 border border-slate-700 rounded-lg text-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500 focus:ring-opacity-50"
                               placeholder="0,00"
                               required
                               <?= ($seller['status'] !== 'active' || !$seller['cashout_enabled']) ? 'disabled' : '' ?>>
                        <p class="text-xs text-slate-400 mt-1">Saldo disponível: R$ <?= number_format($seller['balance'], 2, ',', '.') ?></p>
                    </div>

                    <div id="feeCalculation" class="bg-slate-900 border border-slate-700 rounded-lg p-4 hidden">
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-slate-400">Valor solicitado:</span>
                                <span class="text-white font-medium" id="requestedAmount">R$ 0,00</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-400">Taxa:</span>
                                <span class="text-orange-400 font-medium" id="feeAmount">R$ 0,00</span>
                            </div>
                            <div class="border-t border-slate-700 pt-2 mt-2 flex justify-between">
                                <span class="text-white font-semibold">Total a ser debitado:</span>
                                <span class="text-white font-bold" id="totalAmount">R$ 0,00</span>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">Tipo de Chave PIX</label>
                        <select name="pix_key_type"
                                id="pixKeyType"
                                class="w-full px-4 py-3 bg-slate-900 border border-slate-700 rounded-lg text-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500 focus:ring-opacity-50"
                                required
                                <?= ($seller['status'] !== 'active' || !$seller['cashout_enabled']) ? 'disabled' : '' ?>>
                            <option value="">Selecione...</option>
                            <option value="cpf">CPF</option>
                            <option value="cnpj">CNPJ</option>
                            <option value="email">E-mail</option>
                            <option value="phone">Telefone</option>
                            <option value="random">Chave Aleatória</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">Chave PIX</label>
                        <input type="text"
                               name="pix_key"
                               id="pixKey"
                               class="w-full px-4 py-3 bg-slate-900 border border-slate-700 rounded-lg text-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500 focus:ring-opacity-50"
                               placeholder="Insira sua chave PIX"
                               required
                               <?= ($seller['status'] !== 'active' || !$seller['cashout_enabled']) ? 'disabled' : '' ?>>
                        <p class="text-xs text-slate-400 mt-1" id="pixKeyHint">Selecione o tipo de chave acima</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">Nome do Beneficiário</label>
                        <input type="text"
                               name="beneficiary_name"
                               class="w-full px-4 py-3 bg-slate-900 border border-slate-700 rounded-lg text-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500 focus:ring-opacity-50"
                               placeholder="Nome completo"
                               value="<?= htmlspecialchars($seller['name']) ?>"
                               required
                               <?= ($seller['status'] !== 'active' || !$seller['cashout_enabled']) ? 'disabled' : '' ?>>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">CPF/CNPJ do Beneficiário</label>
                        <input type="text"
                               name="beneficiary_document"
                               id="beneficiaryDocument"
                               class="w-full px-4 py-3 bg-slate-900 border border-slate-700 rounded-lg text-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500 focus:ring-opacity-50"
                               placeholder="000.000.000-00"
                               value="<?= htmlspecialchars($seller['document']) ?>"
                               required
                               <?= ($seller['status'] !== 'active' || !$seller['cashout_enabled']) ? 'disabled' : '' ?>>
                    </div>

                    <button type="submit"
                            class="w-full btn-primary px-6 py-4 rounded-lg font-semibold text-white flex items-center justify-center space-x-2"
                            <?= ($seller['status'] !== 'active' || !$seller['cashout_enabled']) ? 'disabled' : '' ?>>
                        <i class="fas fa-paper-plane"></i>
                        <span>Solicitar Saque</span>
                    </button>
                </div>
            </form>
        </div>

        <div class="card p-6">
            <h2 class="text-xl font-bold text-white mb-6 flex items-center justify-between">
                <span class="flex items-center">
                    <i class="fas fa-history text-blue-500 mr-3"></i>
                    Saques Recentes
                </span>
                <a href="/seller/transactions?type=cashout" class="text-blue-400 text-sm hover:underline">Ver todos</a>
            </h2>

            <div class="space-y-3">
                <?php if (empty($recentCashouts)): ?>
                    <div class="text-center py-12">
                        <i class="fas fa-inbox text-slate-600 text-4xl mb-3"></i>
                        <p class="text-slate-400">Nenhum saque realizado ainda</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($recentCashouts as $cashout): ?>
                    <div class="bg-slate-900 border border-slate-700 rounded-lg p-4 hover:border-blue-500 transition">
                        <div class="flex items-start justify-between mb-2">
                            <div class="flex-1">
                                <div class="flex items-center space-x-2">
                                    <p class="font-semibold text-white">R$ <?= number_format($cashout['amount'], 2, ',', '.') ?></p>
                                    <span class="px-2 py-1 rounded-full text-xs font-medium
                                        <?php
                                        echo match($cashout['status']) {
                                            'completed' => 'bg-green-900 bg-opacity-30 text-green-400 border border-green-700',
                                            'processing' => 'bg-blue-900 bg-opacity-30 text-blue-400 border border-blue-700',
                                            'pending' => 'bg-yellow-900 bg-opacity-30 text-yellow-400 border border-yellow-700',
                                            'failed', 'cancelled' => 'bg-red-900 bg-opacity-30 text-red-400 border border-red-700',
                                            default => 'bg-slate-800 text-slate-400 border border-slate-700'
                                        };
                                        ?>">
                                        <?php
                                        echo match($cashout['status']) {
                                            'completed' => 'Concluído',
                                            'processing' => 'Processando',
                                            'pending' => 'Pendente',
                                            'failed' => 'Falhou',
                                            'cancelled' => 'Cancelado',
                                            default => ucfirst($cashout['status'])
                                        };
                                        ?>
                                    </span>
                                </div>
                                <p class="text-xs text-slate-400 mt-1">
                                    <i class="fas fa-hashtag"></i> <?= htmlspecialchars($cashout['transaction_id']) ?>
                                </p>
                                <p class="text-xs text-slate-400 mt-1">
                                    <i class="fas fa-key"></i> <?= htmlspecialchars(substr($cashout['pix_key'], 0, 20)) ?><?= strlen($cashout['pix_key']) > 20 ? '...' : '' ?>
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center justify-between text-xs text-slate-500 mt-2 pt-2 border-t border-slate-800">
                            <span>
                                <i class="fas fa-calendar"></i> <?= date('d/m/Y H:i', strtotime($cashout['created_at'])) ?>
                            </span>
                            <a href="/seller/transactions/<?= $cashout['id'] ?>/cashout" class="text-blue-400 hover:underline">
                                Ver detalhes <i class="fas fa-arrow-right ml-1"></i>
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const amountInput = document.getElementById('amount');
    const feeCalculation = document.getElementById('feeCalculation');
    const requestedAmount = document.getElementById('requestedAmount');
    const feeAmountElement = document.getElementById('feeAmount');
    const totalAmountElement = document.getElementById('totalAmount');
    const pixKeyType = document.getElementById('pixKeyType');
    const pixKey = document.getElementById('pixKey');
    const pixKeyHint = document.getElementById('pixKeyHint');

    const feePercentage = <?= $feePercentage ?>;
    const feeFixed = <?= $feeFixed ?>;

    function formatCurrency(value) {
        return 'R$ ' + value.toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }

    function calculateFee() {
        const amount = parseFloat(amountInput.value) || 0;

        if (amount > 0) {
            const fee = (amount * feePercentage) + feeFixed;
            const total = amount + fee;

            requestedAmount.textContent = formatCurrency(amount);
            feeAmountElement.textContent = formatCurrency(fee);
            totalAmountElement.textContent = formatCurrency(total);

            feeCalculation.classList.remove('hidden');
        } else {
            feeCalculation.classList.add('hidden');
        }
    }

    amountInput.addEventListener('input', calculateFee);

    pixKeyType.addEventListener('change', function() {
        const type = this.value;
        pixKey.value = '';

        switch(type) {
            case 'cpf':
                pixKeyHint.textContent = 'Formato: 000.000.000-00';
                pixKey.placeholder = '000.000.000-00';
                break;
            case 'cnpj':
                pixKeyHint.textContent = 'Formato: 00.000.000/0000-00';
                pixKey.placeholder = '00.000.000/0000-00';
                break;
            case 'email':
                pixKeyHint.textContent = 'Seu e-mail cadastrado no PIX';
                pixKey.placeholder = 'email@exemplo.com';
                break;
            case 'phone':
                pixKeyHint.textContent = 'Formato: +55 11 99999-9999';
                pixKey.placeholder = '+55 11 99999-9999';
                break;
            case 'random':
                pixKeyHint.textContent = 'Sua chave aleatória (UUID)';
                pixKey.placeholder = '00000000-0000-0000-0000-000000000000';
                break;
            default:
                pixKeyHint.textContent = 'Selecione o tipo de chave acima';
                pixKey.placeholder = 'Insira sua chave PIX';
        }
    });

    const form = document.getElementById('cashoutForm');
    form.addEventListener('submit', function(e) {
        const amount = parseFloat(amountInput.value) || 0;
        const balance = <?= $seller['balance'] ?>;
        const fee = (amount * feePercentage) + feeFixed;
        const total = amount + fee;

        if (total > balance) {
            e.preventDefault();
            alert('Saldo insuficiente! Necessário: ' + formatCurrency(total) + ', Disponível: ' + formatCurrency(balance));
            return false;
        }

        if (!confirm('Confirma o saque de ' + formatCurrency(amount) + ' com taxa de ' + formatCurrency(fee) + '?\n\nTotal a ser debitado: ' + formatCurrency(total))) {
            e.preventDefault();
            return false;
        }
    });
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
