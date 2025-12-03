<?php
$pageTitle = 'Documentos';
require_once __DIR__ . '/../layouts/header.php';
$status = $_GET['status'] ?? 'pending';

$docLabels = [
    'rg_front' => 'RG/Doc Representante - Frente',
    'rg_back' => 'RG/Doc Representante - Verso',
    'cnh_front' => 'CNH - Frente',
    'cnh_back' => 'CNH - Verso',
    'cpf' => 'CPF',
    'selfie' => 'Selfie com Documento',
    'proof_address' => 'Comprovante de Endereço',
    'social_contract' => 'Contrato Social',
    'cnpj' => 'Cartão CNPJ',
    'partner_docs' => 'Documentos dos Sócios'
];
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Documentos</h1>
        <p class="text-gray-600 mt-2">Analise e aprove documentos dos sellers</p>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
        <div class="flex items-center gap-3">
            <a href="/admin/documents?status=pending" class="px-4 py-2 rounded-lg text-sm font-medium transition <?= $status === 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">
                <i class="fas fa-clock mr-2"></i>Pendentes
            </a>
            <a href="/admin/documents?status=under_review" class="px-4 py-2 rounded-lg text-sm font-medium transition <?= $status === 'under_review' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">
                <i class="fas fa-eye mr-2"></i>Em Análise
            </a>
            <a href="/admin/documents?status=approved" class="px-4 py-2 rounded-lg text-sm font-medium transition <?= $status === 'approved' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">
                <i class="fas fa-check mr-2"></i>Aprovados
            </a>
            <a href="/admin/documents?status=rejected" class="px-4 py-2 rounded-lg text-sm font-medium transition <?= $status === 'rejected' ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">
                <i class="fas fa-times mr-2"></i>Rejeitados
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php if (empty($documentsWithSeller)): ?>
        <div class="col-span-full bg-white rounded-xl shadow-sm border border-gray-200 p-12 text-center">
            <i class="fas fa-file-alt text-gray-400 text-5xl mb-4"></i>
            <p class="text-gray-500">Nenhum documento encontrado</p>
        </div>
        <?php else: ?>
        <?php foreach ($documentsWithSeller as $doc): ?>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover-lift">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-semibold text-gray-900"><?= $docLabels[$doc['document_type']] ?? ucfirst(str_replace('_', ' ', $doc['document_type'])) ?></h3>
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

            <div class="mb-4 p-3 bg-gray-50 rounded-lg">
                <p class="text-sm font-medium text-gray-900"><?= htmlspecialchars($doc['seller']['name']) ?></p>
                <p class="text-xs text-gray-600 mt-1"><?= htmlspecialchars($doc['seller']['email']) ?></p>
                <p class="text-xs text-gray-500 mt-1">ID: <?= $doc['seller_id'] ?></p>
            </div>

            <div class="text-center py-4 mb-4 bg-gray-100 rounded-lg">
                <i class="fas fa-file-alt text-gray-400 text-4xl"></i>
                <p class="text-xs text-gray-600 mt-2"><?= number_format($doc['file_size'] / 1024, 0) ?> KB</p>
            </div>

            <p class="text-xs text-gray-500 mb-4">
                <i class="fas fa-calendar mr-1"></i>
                <?= date('d/m/Y H:i', strtotime($doc['created_at'])) ?>
            </p>

            <div class="flex items-center gap-2">
                <a href="/admin/documents/view/<?= $doc['id'] ?>" class="flex-1 text-center px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 transition">
                    Analisar
                </a>
                <a href="/admin/sellers/view/<?= $doc['seller_id'] ?>" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-300 transition">
                    <i class="fas fa-user"></i>
                </a>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
