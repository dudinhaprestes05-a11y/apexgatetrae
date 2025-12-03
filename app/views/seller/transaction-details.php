<?php
$pageTitle = 'Detalhes da Transação';
require_once __DIR__ . '/../layouts/header.php';
?>

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-8">
        <a href="/seller/transactions" class="text-blue-600 hover:text-blue-800 text-sm mb-2 inline-block">
            <i class="fas fa-arrow-left mr-1"></i>Voltar
        </a>
        <h1 class="text-3xl font-bold text-gray-900">Detalhes da Transação</h1>
        <p class="text-gray-600 mt-2"><?= $type === 'cashin' ? 'Recebimento PIX' : 'Saque PIX' ?></p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h2 class="text-lg font-bold text-gray-900 mb-4">Informações Gerais</h2>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="text-sm font-medium text-gray-600">ID da Transação</label>
                        <p class="text-gray-900 mt-1 font-mono text-sm"><?= htmlspecialchars($transaction['transaction_id']) ?></p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-600">Status</label>
                        <div class="mt-1">
                            <span class="px-3 py-1 text-xs font-medium rounded-full
                                <?php
                                echo match($transaction['status']) {
                                    'approved', 'paid' => 'bg-green-100 text-green-800',
                                    'waiting_payment', 'pending' => 'bg-yellow-100 text-yellow-800',
                                    'cancelled', 'failed' => 'bg-red-100 text-red-800',
                                    default => 'bg-gray-100 text-gray-800'
                                };
                                ?>">
                                <?= ucfirst($transaction['status']) ?>
                            </span>
                        </div>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-600">Valor</label>
                        <p class="text-gray-900 mt-1 font-semibold">R$ <?= number_format($transaction['amount'], 2, ',', '.') ?></p>
                    </div>
                    <?php if (isset($transaction['fee_amount'])): ?>
                    <div>
                        <label class="text-sm font-medium text-gray-600">Taxa</label>
                        <p class="text-gray-900 mt-1">R$ <?= number_format($transaction['fee_amount'], 2, ',', '.') ?></p>
                    </div>
                    <?php endif; ?>
                    <?php if (isset($transaction['net_amount'])): ?>
                    <div>
                        <label class="text-sm font-medium text-gray-600">Valor Líquido</label>
                        <p class="text-gray-900 mt-1 font-semibold">R$ <?= number_format($transaction['net_amount'], 2, ',', '.') ?></p>
                    </div>
                    <?php endif; ?>
                    <div>
                        <label class="text-sm font-medium text-gray-600">Criado em</label>
                        <p class="text-gray-900 mt-1"><?= date('d/m/Y H:i:s', strtotime($transaction['created_at'])) ?></p>
                    </div>
                    <?php if ($transaction['paid_at']): ?>
                    <div>
                        <label class="text-sm font-medium text-gray-600">Pago em</label>
                        <p class="text-gray-900 mt-1"><?= date('d/m/Y H:i:s', strtotime($transaction['paid_at'])) ?></p>
                    </div>
                    <?php endif; ?>
                    <?php if ($transaction['external_id']): ?>
                    <div class="col-span-2">
                        <label class="text-sm font-medium text-gray-600">ID Externo</label>
                        <p class="text-gray-900 mt-1 font-mono text-sm"><?= htmlspecialchars($transaction['external_id']) ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($type === 'cashin' && isset($transaction['customer_name'])): ?>
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h2 class="text-lg font-bold text-gray-900 mb-4">Dados do Cliente</h2>
                <div class="grid grid-cols-2 gap-4">
                    <?php if ($transaction['customer_name']): ?>
                    <div>
                        <label class="text-sm font-medium text-gray-600">Nome</label>
                        <p class="text-gray-900 mt-1"><?= htmlspecialchars($transaction['customer_name']) ?></p>
                    </div>
                    <?php endif; ?>
                    <?php if ($transaction['customer_document']): ?>
                    <div>
                        <label class="text-sm font-medium text-gray-600">Documento</label>
                        <p class="text-gray-900 mt-1"><?= htmlspecialchars($transaction['customer_document']) ?></p>
                    </div>
                    <?php endif; ?>
                    <?php if ($transaction['customer_email']): ?>
                    <div class="col-span-2">
                        <label class="text-sm font-medium text-gray-600">Email</label>
                        <p class="text-gray-900 mt-1"><?= htmlspecialchars($transaction['customer_email']) ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($type === 'cashin' && isset($transaction['payer_name']) && $transaction['payer_name']): ?>
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h2 class="text-lg font-bold text-gray-900 mb-4">Dados do Pagador</h2>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="text-sm font-medium text-gray-600">Nome</label>
                        <p class="text-gray-900 mt-1"><?= htmlspecialchars($transaction['payer_name']) ?></p>
                    </div>
                    <?php if ($transaction['payer_document']): ?>
                    <div>
                        <label class="text-sm font-medium text-gray-600">Documento</label>
                        <p class="text-gray-900 mt-1"><?= htmlspecialchars($transaction['payer_document']) ?></p>
                    </div>
                    <?php endif; ?>
                    <?php if ($transaction['payer_bank']): ?>
                    <div class="col-span-2">
                        <label class="text-sm font-medium text-gray-600">Banco</label>
                        <p class="text-gray-900 mt-1"><?= htmlspecialchars($transaction['payer_bank']) ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <div class="space-y-6">
            <?php if ($type === 'cashin' && $transaction['qrcode']): ?>
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="font-bold text-gray-900 mb-4">QR Code PIX</h3>
                <?php if ($transaction['qrcode_base64']): ?>
                <div class="mb-4">
                    <img src="<?= htmlspecialchars($transaction['qrcode_base64']) ?>" alt="QR Code" class="w-full">
                </div>
                <?php endif; ?>
                <div>
                    <label class="text-sm font-medium text-gray-600 mb-2 block">Código Copia e Cola</label>
                    <div class="bg-gray-50 p-3 rounded-lg break-all text-xs font-mono text-gray-700">
                        <?= htmlspecialchars($transaction['qrcode']) ?>
                    </div>
                    <button onclick="copyToClipboard('<?= htmlspecialchars($transaction['qrcode']) ?>', this)"
                            class="w-full mt-3 px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 transition">
                        <i class="fas fa-copy mr-2"></i>Copiar Código
                    </button>
                </div>
            </div>
            <?php endif; ?>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="font-bold text-gray-900 mb-4">Identificadores</h3>
                <div class="space-y-3">
                    <?php if ($transaction['end_to_end_id']): ?>
                    <div>
                        <label class="text-sm font-medium text-gray-600">End-to-End ID</label>
                        <p class="text-gray-900 mt-1 font-mono text-xs break-all"><?= htmlspecialchars($transaction['end_to_end_id']) ?></p>
                    </div>
                    <?php endif; ?>
                    <?php if ($transaction['acquirer_transaction_id']): ?>
                    <div>
                        <label class="text-sm font-medium text-gray-600">ID Adquirente</label>
                        <p class="text-gray-900 mt-1 font-mono text-xs break-all"><?= htmlspecialchars($transaction['acquirer_transaction_id']) ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
