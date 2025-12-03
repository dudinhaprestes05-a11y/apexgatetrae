<?php
$pageTitle = 'Documentos';
require_once __DIR__ . '/../layouts/header.php';

$docLabels = [
    'rg_front' => 'RG - Frente',
    'rg_back' => 'RG - Verso',
    'cnh_front' => 'CNH - Frente',
    'cnh_back' => 'CNH - Verso',
    'cpf' => 'CPF',
    'selfie' => 'Selfie com Documento',
    'proof_address' => 'Comprovante de Endereço',
    'social_contract' => 'Contrato Social',
    'cnpj' => 'Cartão CNPJ',
    'partner_docs' => 'Documentos dos Sócios'
];

$uploadedDocs = [];
foreach ($documents as $doc) {
    $uploadedDocs[$doc['document_type']] = $doc;
}
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Documentos</h1>
        <p class="text-gray-600 mt-2">Envie os documentos necessários para aprovação da sua conta</p>
    </div>

    <?php if ($seller['document_status'] === 'pending'): ?>
    <div class="mb-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
        <div class="flex items-center">
            <i class="fas fa-info-circle text-blue-600 text-xl mr-3"></i>
            <div>
                <h3 class="font-semibold text-blue-900">Envie seus documentos</h3>
                <p class="text-blue-700 text-sm mt-1">Para ativar sua conta, precisamos validar seus documentos. Envie fotos claras e legíveis.</p>
            </div>
        </div>
    </div>
    <?php elseif ($seller['document_status'] === 'under_review'): ?>
    <div class="mb-6 bg-yellow-50 border border-yellow-200 rounded-lg p-4">
        <div class="flex items-center">
            <i class="fas fa-clock text-yellow-600 text-xl mr-3"></i>
            <div>
                <h3 class="font-semibold text-yellow-900">Documentos em Análise</h3>
                <p class="text-yellow-700 text-sm mt-1">Seus documentos estão sendo analisados. Em breve você receberá um retorno.</p>
            </div>
        </div>
    </div>
    <?php elseif ($seller['document_status'] === 'approved'): ?>
    <div class="mb-6 bg-green-50 border border-green-200 rounded-lg p-4">
        <div class="flex items-center">
            <i class="fas fa-check-circle text-green-600 text-xl mr-3"></i>
            <div>
                <h3 class="font-semibold text-green-900">Documentos Aprovados!</h3>
                <p class="text-green-700 text-sm mt-1">Todos os seus documentos foram aprovados. Sua conta está ativa!</p>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($requiredDocs as $docType): ?>
        <?php
        $uploaded = $uploadedDocs[$docType] ?? null;
        $status = $uploaded ? $uploaded['status'] : 'not_uploaded';
        ?>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-semibold text-gray-900"><?= $docLabels[$docType] ?></h3>
                <?php if ($status === 'approved'): ?>
                    <span class="px-2 py-1 bg-green-100 text-green-800 text-xs font-medium rounded-full">
                        <i class="fas fa-check mr-1"></i>Aprovado
                    </span>
                <?php elseif ($status === 'rejected'): ?>
                    <span class="px-2 py-1 bg-red-100 text-red-800 text-xs font-medium rounded-full">
                        <i class="fas fa-times mr-1"></i>Rejeitado
                    </span>
                <?php elseif ($status === 'pending' || $status === 'under_review'): ?>
                    <span class="px-2 py-1 bg-yellow-100 text-yellow-800 text-xs font-medium rounded-full">
                        <i class="fas fa-clock mr-1"></i>Análise
                    </span>
                <?php endif; ?>
            </div>

            <?php if ($uploaded && $status === 'rejected'): ?>
            <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg">
                <p class="text-xs text-red-800"><strong>Motivo:</strong> <?= htmlspecialchars($uploaded['rejection_reason']) ?></p>
            </div>
            <?php endif; ?>

            <?php if (!$uploaded || $status === 'rejected'): ?>
            <form method="POST" action="/seller/documents/upload" enctype="multipart/form-data" class="space-y-3">
                <input type="hidden" name="document_type" value="<?= $docType ?>">
                <input type="file" name="document" accept=".jpg,.jpeg,.png,.pdf" required
                       class="w-full text-sm text-gray-600 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded-lg text-sm font-medium hover:bg-blue-700 transition">
                    <i class="fas fa-upload mr-2"></i>Enviar
                </button>
            </form>
            <?php else: ?>
            <div class="text-center py-4">
                <i class="fas fa-file-alt text-gray-400 text-4xl mb-2"></i>
                <p class="text-sm text-gray-600">Documento enviado</p>
                <p class="text-xs text-gray-500 mt-1"><?= date('d/m/Y H:i', strtotime($uploaded['created_at'])) ?></p>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
