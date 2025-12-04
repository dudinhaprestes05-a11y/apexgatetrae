<?php
$pageTitle = 'Informações Pessoais';
require_once __DIR__ . '/../layouts/header.php';
?>

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Informações Pessoais</h1>
        <p class="text-gray-600 mt-2">Complete seus dados pessoais antes de enviar os documentos</p>
    </div>

    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
        <div class="flex items-start">
            <i class="fas fa-info-circle text-blue-600 text-xl mr-3 mt-1"></i>
            <div>
                <h3 class="font-semibold text-blue-900">Por que precisamos dessas informações?</h3>
                <p class="text-blue-700 text-sm mt-1">
                    Precisamos validar sua identidade e endereço para garantir a segurança das transações.
                    Todos os dados são protegidos e utilizados apenas para fins de verificação.
                </p>
            </div>
        </div>
    </div>

    <form method="POST" action="/seller/personal-info/save" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 space-y-6">
        <!-- Tipo de Documento -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Tipo de Documento Pessoal *</label>
            <div class="grid grid-cols-2 gap-4">
                <label class="relative flex items-center justify-center p-4 border-2 rounded-lg cursor-pointer transition hover:border-blue-500 <?= (!isset($seller['personal_document_type']) || $seller['personal_document_type'] === 'rg') ? 'border-blue-600 bg-blue-50' : 'border-gray-200' ?>">
                    <input type="radio" name="personal_document_type" value="rg" class="sr-only" <?= (!isset($seller['personal_document_type']) || $seller['personal_document_type'] === 'rg') ? 'checked' : '' ?> onchange="toggleDocumentFields()">
                    <div class="text-center">
                        <i class="fas fa-id-card text-3xl text-blue-600 mb-2"></i>
                        <div class="font-medium text-gray-900">RG</div>
                        <div class="text-xs text-gray-500">Registro Geral</div>
                    </div>
                </label>
                <label class="relative flex items-center justify-center p-4 border-2 rounded-lg cursor-pointer transition hover:border-blue-500 <?= (isset($seller['personal_document_type']) && $seller['personal_document_type'] === 'cnh') ? 'border-blue-600 bg-blue-50' : 'border-gray-200' ?>">
                    <input type="radio" name="personal_document_type" value="cnh" class="sr-only" <?= (isset($seller['personal_document_type']) && $seller['personal_document_type'] === 'cnh') ? 'checked' : '' ?> onchange="toggleDocumentFields()">
                    <div class="text-center">
                        <i class="fas fa-id-card-alt text-3xl text-blue-600 mb-2"></i>
                        <div class="font-medium text-gray-900">CNH</div>
                        <div class="text-xs text-gray-500">Carteira de Habilitação</div>
                    </div>
                </label>
            </div>
        </div>

        <!-- Campos RG -->
        <div id="rg-fields" class="space-y-4 <?= (isset($seller['personal_document_type']) && $seller['personal_document_type'] === 'cnh') ? 'hidden' : '' ?>">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Número do RG *</label>
                    <input type="text" name="rg_number" value="<?= htmlspecialchars($seller['rg_number'] ?? '') ?>"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                           placeholder="Ex: 12.345.678-9">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Órgão Emissor *</label>
                    <input type="text" name="rg_issuer" value="<?= htmlspecialchars($seller['rg_issuer'] ?? '') ?>"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                           placeholder="Ex: SSP/SP">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Data de Emissão do RG *</label>
                <input type="date" name="rg_issue_date" value="<?= $seller['rg_issue_date'] ?? '' ?>"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>
        </div>

        <!-- Campos CNH -->
        <div id="cnh-fields" class="space-y-4 <?= (!isset($seller['personal_document_type']) || $seller['personal_document_type'] === 'rg') ? 'hidden' : '' ?>">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Número da CNH *</label>
                    <input type="text" name="cnh_number" value="<?= htmlspecialchars($seller['cnh_number'] ?? '') ?>"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                           placeholder="Ex: 12345678901">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Categoria da CNH *</label>
                    <select name="cnh_category" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">Selecione</option>
                        <option value="A" <?= (isset($seller['cnh_category']) && $seller['cnh_category'] === 'A') ? 'selected' : '' ?>>A</option>
                        <option value="B" <?= (isset($seller['cnh_category']) && $seller['cnh_category'] === 'B') ? 'selected' : '' ?>>B</option>
                        <option value="AB" <?= (isset($seller['cnh_category']) && $seller['cnh_category'] === 'AB') ? 'selected' : '' ?>>AB</option>
                        <option value="C" <?= (isset($seller['cnh_category']) && $seller['cnh_category'] === 'C') ? 'selected' : '' ?>>C</option>
                        <option value="D" <?= (isset($seller['cnh_category']) && $seller['cnh_category'] === 'D') ? 'selected' : '' ?>>D</option>
                        <option value="E" <?= (isset($seller['cnh_category']) && $seller['cnh_category'] === 'E') ? 'selected' : '' ?>>E</option>
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Data de Validade da CNH *</label>
                <input type="date" name="cnh_expiry_date" value="<?= $seller['cnh_expiry_date'] ?? '' ?>"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>
        </div>

        <!-- Data de Nascimento -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Data de Nascimento *</label>
            <input type="date" name="birth_date" value="<?= $seller['birth_date'] ?? '' ?>" required
                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
        </div>

        <!-- Endereço -->
        <div class="border-t pt-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Endereço Residencial</h3>

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">CEP *</label>
                    <input type="text" name="address_zipcode" id="zipcode" value="<?= htmlspecialchars($seller['address_zipcode'] ?? '') ?>" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                           placeholder="00000-000" maxlength="9" onblur="fetchAddress()">
                    <p class="text-xs text-gray-500 mt-1">Digite o CEP para preencher automaticamente</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Rua/Avenida *</label>
                        <input type="text" name="address_street" id="street" value="<?= htmlspecialchars($seller['address_street'] ?? '') ?>" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Número *</label>
                        <input type="text" name="address_number" value="<?= htmlspecialchars($seller['address_number'] ?? '') ?>" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Complemento</label>
                    <input type="text" name="address_complement" value="<?= htmlspecialchars($seller['address_complement'] ?? '') ?>"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                           placeholder="Apto, Bloco, Sala, etc (opcional)">
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Bairro *</label>
                        <input type="text" name="address_neighborhood" id="neighborhood" value="<?= htmlspecialchars($seller['address_neighborhood'] ?? '') ?>" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Cidade *</label>
                        <input type="text" name="address_city" id="city" value="<?= htmlspecialchars($seller['address_city'] ?? '') ?>" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Estado *</label>
                        <input type="text" name="address_state" id="state" value="<?= htmlspecialchars($seller['address_state'] ?? '') ?>" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="UF" maxlength="2">
                    </div>
                </div>
            </div>
        </div>

        <div class="flex gap-4 pt-6 border-t">
            <button type="submit" class="flex-1 bg-blue-600 text-white py-3 rounded-lg font-medium hover:bg-blue-700 transition">
                <i class="fas fa-save mr-2"></i>Salvar e Continuar
            </button>
            <a href="/seller/dashboard" class="px-6 py-3 border border-gray-300 rounded-lg font-medium text-gray-700 hover:bg-gray-50 transition">
                Voltar
            </a>
        </div>
    </form>
</div>

<script>
function toggleDocumentFields() {
    const rgFields = document.getElementById('rg-fields');
    const cnhFields = document.getElementById('cnh-fields');
    const selectedType = document.querySelector('input[name="personal_document_type"]:checked').value;

    if (selectedType === 'rg') {
        rgFields.classList.remove('hidden');
        cnhFields.classList.add('hidden');

        // Tornar campos RG obrigatórios
        rgFields.querySelectorAll('input').forEach(input => {
            if (input.name !== 'rg_issue_date') {
                input.required = true;
            }
        });

        // Remover obrigatoriedade dos campos CNH
        cnhFields.querySelectorAll('input, select').forEach(input => {
            input.required = false;
        });
    } else {
        rgFields.classList.add('hidden');
        cnhFields.classList.remove('hidden');

        // Tornar campos CNH obrigatórios
        cnhFields.querySelectorAll('input, select').forEach(input => {
            input.required = true;
        });

        // Remover obrigatoriedade dos campos RG
        rgFields.querySelectorAll('input').forEach(input => {
            input.required = false;
        });
    }
}

// Inicializar campos ao carregar
toggleDocumentFields();

// Buscar endereço por CEP
async function fetchAddress() {
    const zipcode = document.getElementById('zipcode').value.replace(/\D/g, '');

    if (zipcode.length !== 8) {
        return;
    }

    try {
        const response = await fetch(`https://viacep.com.br/ws/${zipcode}/json/`);
        const data = await response.json();

        if (!data.erro) {
            document.getElementById('street').value = data.logradouro || '';
            document.getElementById('neighborhood').value = data.bairro || '';
            document.getElementById('city').value = data.localidade || '';
            document.getElementById('state').value = data.uf || '';
        }
    } catch (error) {
        console.error('Erro ao buscar CEP:', error);
    }
}

// Formatar CEP automaticamente
document.getElementById('zipcode').addEventListener('input', function(e) {
    let value = e.target.value.replace(/\D/g, '');
    if (value.length > 5) {
        value = value.substring(0, 5) + '-' + value.substring(5, 8);
    }
    e.target.value = value;
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
