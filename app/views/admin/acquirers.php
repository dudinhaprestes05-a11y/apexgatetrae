<?php
$pageTitle = 'Adquirentes';
require_once __DIR__ . '/../layouts/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Adquirentes</h1>
        <p class="text-gray-600 mt-2">Gerenciar adquirentes/PSPs integrados</p>
    </div>

    <div class="grid grid-cols-1 gap-6">
        <?php if (empty($acquirers)): ?>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-12 text-center">
            <i class="fas fa-building text-gray-400 text-5xl mb-4"></i>
            <p class="text-gray-500">Nenhuma adquirente cadastrada</p>
        </div>
        <?php else: ?>
        <?php foreach ($acquirers as $acquirer): ?>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover-lift">
            <div class="flex items-start justify-between">
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
                            <?php $percentage = ($acquirer['daily_used'] / $acquirer['daily_limit']) * 100; ?>
                            <div class="bg-blue-600 h-2 rounded-full" style="width: <?= min($percentage, 100) ?>%"></div>
                        </div>
                        <div class="flex justify-between text-xs text-gray-600 mt-1">
                            <span>Usado: R$ <?= number_format($acquirer['daily_used'], 2, ',', '.') ?></span>
                            <span>Limite: R$ <?= number_format($acquirer['daily_limit'], 2, ',', '.') ?></span>
                        </div>
                    </div>

                    <div>
                        <label class="text-sm font-medium text-gray-600">URL da API</label>
                        <p class="text-gray-700 mt-1 text-sm font-mono"><?= htmlspecialchars($acquirer['api_url']) ?></p>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
