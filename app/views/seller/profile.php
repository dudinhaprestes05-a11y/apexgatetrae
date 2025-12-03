<?php
$pageTitle = 'Perfil';
require_once __DIR__ . '/../layouts/header.php';
?>

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Meu Perfil</h1>
        <p class="text-gray-600 mt-2">Gerencie as informações da sua conta</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h2 class="text-lg font-bold text-gray-900 mb-4">Informações Básicas</h2>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="text-sm font-medium text-gray-600">Nome</label>
                        <p class="text-gray-900 mt-1"><?= htmlspecialchars($seller['name']) ?></p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-600">Email</label>
                        <p class="text-gray-900 mt-1"><?= htmlspecialchars($seller['email']) ?></p>
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
                    <?php endif; ?>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h2 class="text-lg font-bold text-gray-900 mb-4">Configurações de Webhook</h2>
                <form method="POST" action="/seller/profile/update" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">URL do Webhook</label>
                        <input type="url" name="webhook_url"
                               value="<?= htmlspecialchars($seller['webhook_url'] ?? '') ?>"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="https://seu-site.com/webhook">
                        <p class="text-xs text-gray-500 mt-1">URL que receberá notificações sobre transações</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Webhook Secret</label>
                        <input type="text" name="webhook_secret"
                               value="<?= htmlspecialchars($seller['webhook_secret'] ?? '') ?>"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="seu_secret_key">
                        <p class="text-xs text-gray-500 mt-1">Chave secreta para validar webhooks (HMAC)</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Telefone</label>
                        <input type="text" name="phone"
                               value="<?= htmlspecialchars($seller['phone'] ?? '') ?>"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="(11) 99999-9999">
                    </div>

                    <button type="submit" class="w-full bg-blue-600 text-white py-3 rounded-lg font-medium hover:bg-blue-700 transition">
                        <i class="fas fa-save mr-2"></i>Salvar Alterações
                    </button>
                </form>
            </div>
        </div>

        <div class="space-y-6">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="font-bold text-gray-900 mb-4">Status da Conta</h3>
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600">Status</span>
                        <span class="px-3 py-1 rounded-full text-xs font-medium
                            <?php
                            echo match($seller['status']) {
                                'active' => 'bg-green-100 text-green-800',
                                'pending' => 'bg-yellow-100 text-yellow-800',
                                'inactive' => 'bg-gray-100 text-gray-800',
                                'blocked' => 'bg-red-100 text-red-800',
                                default => 'bg-gray-100 text-gray-800'
                            };
                            ?>">
                            <?= ucfirst($seller['status']) ?>
                        </span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600">Documentos</span>
                        <span class="px-3 py-1 rounded-full text-xs font-medium
                            <?php
                            echo match($seller['document_status']) {
                                'approved' => 'bg-green-100 text-green-800',
                                'pending' => 'bg-yellow-100 text-yellow-800',
                                'under_review' => 'bg-blue-100 text-blue-800',
                                'rejected' => 'bg-red-100 text-red-800',
                                default => 'bg-gray-100 text-gray-800'
                            };
                            ?>">
                            <?= ucfirst(str_replace('_', ' ', $seller['document_status'])) ?>
                        </span>
                    </div>
                </div>
            </div>

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
                            <?php
                            $percentage = ($seller['daily_used'] / $seller['daily_limit']) * 100;
                            ?>
                            <div class="bg-blue-600 h-2 rounded-full" style="width: <?= min($percentage, 100) ?>%"></div>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">Usado: R$ <?= number_format($seller['daily_used'], 2, ',', '.') ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
