<?php
$pageTitle = 'Visualizar Documento';
require_once __DIR__ . '/../layouts/header.php';
?>

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-8">
        <a href="/admin/documents" class="text-blue-600 hover:text-blue-800 text-sm mb-2 inline-block">
            <i class="fas fa-arrow-left mr-1"></i>Voltar
        </a>
        <h1 class="text-3xl font-bold text-gray-900">Análise de Documento</h1>
        <p class="text-gray-600 mt-2"><?= ucfirst(str_replace('_', ' ', $document['document_type'])) ?></p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
                <h2 class="text-lg font-bold text-gray-900 mb-4">Documento</h2>
                <div class="bg-gray-100 rounded-lg p-8 text-center">
                    <?php if (in_array($document['mime_type'], ['image/jpeg', 'image/jpg', 'image/png'])): ?>
                        <img src="data:<?= $document['mime_type'] ?>;base64,<?= base64_encode(file_get_contents($document['file_path'])) ?>"
                             alt="Documento" class="max-w-full mx-auto rounded-lg shadow-lg">
                    <?php elseif ($document['mime_type'] === 'application/pdf'): ?>
                        <i class="fas fa-file-pdf text-red-500 text-6xl mb-4"></i>
                        <p class="text-gray-700 font-medium">Arquivo PDF</p>
                        <a href="<?= $document['file_path'] ?>" download class="inline-block mt-4 px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                            <i class="fas fa-download mr-2"></i>Baixar PDF
                        </a>
                    <?php else: ?>
                        <i class="fas fa-file text-gray-400 text-6xl mb-4"></i>
                        <p class="text-gray-700">Tipo de arquivo não suportado para visualização</p>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($document['status'] === 'rejected' && $document['rejection_reason']): ?>
            <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                <h3 class="font-semibold text-red-900 mb-2">Motivo da Rejeição</h3>
                <p class="text-red-800 text-sm"><?= htmlspecialchars($document['rejection_reason']) ?></p>
            </div>
            <?php endif; ?>
        </div>

        <div class="space-y-6">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="font-bold text-gray-900 mb-4">Informações do Seller</h3>
                <div class="space-y-3">
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
                    <a href="/admin/sellers/view/<?= $seller['id'] ?>" class="block w-full text-center px-4 py-2 bg-gray-200 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-300 transition mt-4">
                        Ver Perfil Completo
                    </a>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="font-bold text-gray-900 mb-4">Detalhes do Arquivo</h3>
                <div class="space-y-3">
                    <div>
                        <label class="text-sm font-medium text-gray-600">Tipo</label>
                        <p class="text-gray-900 mt-1"><?= ucfirst(str_replace('_', ' ', $document['document_type'])) ?></p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-600">Status</label>
                        <div class="mt-1">
                            <span class="px-3 py-1 text-xs font-medium rounded-full
                                <?php
                                echo match($document['status']) {
                                    'approved' => 'bg-green-100 text-green-800',
                                    'pending' => 'bg-yellow-100 text-yellow-800',
                                    'under_review' => 'bg-blue-100 text-blue-800',
                                    'rejected' => 'bg-red-100 text-red-800',
                                    default => 'bg-gray-100 text-gray-800'
                                };
                                ?>">
                                <?= ucfirst($document['status']) ?>
                            </span>
                        </div>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-600">Tamanho</label>
                        <p class="text-gray-900 mt-1"><?= number_format($document['file_size'] / 1024, 2) ?> KB</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-600">Enviado em</label>
                        <p class="text-gray-900 mt-1"><?= date('d/m/Y H:i', strtotime($document['created_at'])) ?></p>
                    </div>
                </div>
            </div>

            <?php if ($document['status'] === 'pending' || $document['status'] === 'under_review'): ?>
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="font-bold text-gray-900 mb-4">Ações</h3>
                <div class="space-y-3">
                    <button onclick="approveDocument(<?= $document['id'] ?>)" class="w-full bg-green-600 text-white py-3 rounded-lg font-medium hover:bg-green-700 transition">
                        <i class="fas fa-check mr-2"></i>Aprovar Documento
                    </button>
                    <button onclick="showRejectModal()" class="w-full bg-red-600 text-white py-3 rounded-lg font-medium hover:bg-red-700 transition">
                        <i class="fas fa-times mr-2"></i>Rejeitar Documento
                    </button>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div id="rejectModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-xl p-6 max-w-md w-full mx-4">
        <h3 class="text-lg font-bold text-gray-900 mb-4">Rejeitar Documento</h3>
        <form id="rejectForm">
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Motivo da Rejeição</label>
                <textarea name="reason" required rows="4"
                          class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                          placeholder="Ex: Foto desfocada, documento ilegível, etc."></textarea>
            </div>
            <div class="flex items-center gap-3">
                <button type="button" onclick="hideRejectModal()" class="flex-1 bg-gray-200 text-gray-700 py-3 rounded-lg font-medium hover:bg-gray-300 transition">
                    Cancelar
                </button>
                <button type="submit" class="flex-1 bg-red-600 text-white py-3 rounded-lg font-medium hover:bg-red-700 transition">
                    Rejeitar
                </button>
            </div>
        </form>
    </div>
</div>

<script>
const documentId = <?= $document['id'] ?>;

function showRejectModal() {
    document.getElementById('rejectModal').classList.remove('hidden');
    document.getElementById('rejectModal').classList.add('flex');
}

function hideRejectModal() {
    document.getElementById('rejectModal').classList.add('hidden');
    document.getElementById('rejectModal').classList.remove('flex');
}

function approveDocument(docId) {
    if (!confirm('Tem certeza que deseja aprovar este documento?')) {
        return;
    }

    const form = new FormData();

    fetch(`/admin/documents/${docId}/approve`, {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: form
    })
    .then(response => {
        if (response.ok) {
            showSuccessMessage('Documento aprovado com sucesso!');
            setTimeout(() => {
                window.location.href = '/admin/documents';
            }, 500);
        } else {
            showErrorMessage('Erro ao aprovar documento');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showErrorMessage('Erro ao aprovar documento');
    });
}

document.getElementById('rejectForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const formData = new FormData(this);

    fetch(`/admin/documents/${documentId}/reject`, {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(response => {
        if (response.ok) {
            hideRejectModal();
            showSuccessMessage('Documento rejeitado');
            setTimeout(() => {
                window.location.href = '/admin/documents';
            }, 500);
        } else {
            showErrorMessage('Erro ao rejeitar documento');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showErrorMessage('Erro ao rejeitar documento');
    });
});

function showSuccessMessage(message) {
    const alert = document.createElement('div');
    alert.className = 'fixed top-4 right-4 bg-green-50 border border-green-200 text-green-800 px-6 py-4 rounded-lg shadow-lg z-50 fade-in';
    alert.innerHTML = `
        <div class="flex items-center">
            <i class="fas fa-check-circle mr-3"></i>
            <span>${message}</span>
        </div>
    `;
    document.body.appendChild(alert);

    setTimeout(() => {
        alert.remove();
    }, 3000);
}

function showErrorMessage(message) {
    const alert = document.createElement('div');
    alert.className = 'fixed top-4 right-4 bg-red-50 border border-red-200 text-red-800 px-6 py-4 rounded-lg shadow-lg z-50 fade-in';
    alert.innerHTML = `
        <div class="flex items-center">
            <i class="fas fa-exclamation-circle mr-3"></i>
            <span>${message}</span>
        </div>
    `;
    document.body.appendChild(alert);

    setTimeout(() => {
        alert.remove();
    }, 3000);
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
