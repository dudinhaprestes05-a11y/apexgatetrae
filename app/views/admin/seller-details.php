<?php
$pageTitle = 'Detalhes do Seller';
require_once __DIR__ . '/../../models/SellerDocument.php';
require_once __DIR__ . '/../layouts/header.php';

$statusColors = [
    'active' => 'badge-success',
    'pending' => 'badge-warning',
    'inactive' => 'badge',
    'blocked' => 'badge-danger',
    'rejected' => 'badge-danger'
];
?>

<!-- Header -->
<div class="flex items-center justify-between mb-8">
    <div>
        <a href="/admin/sellers" class="text-blue-400 hover:text-blue-300 text-sm mb-3 inline-flex items-center">
            <i class="fas fa-arrow-left mr-2"></i>Voltar
        </a>
        <h2 class="text-3xl font-bold text-white mt-2 flex items-center">
            <?= htmlspecialchars($seller['name']) ?>
            <span class="badge <?= $statusColors[$seller['status']] ?? 'badge' ?> ml-3 text-sm">
                <?= ucfirst($seller['status']) ?>
            </span>
        </h2>
        <p class="text-slate-400 mt-1"><?= htmlspecialchars($seller['email']) ?></p>
    </div>
    <?php if ($seller['status'] === 'pending'): ?>
    <div class="flex space-x-3">
        <form method="POST" action="/admin/sellers/<?= $seller['id'] ?>/approve" class="inline">
            <button type="submit" class="px-6 py-2.5 bg-green-600 hover:bg-green-700 text-white rounded-lg transition">
                <i class="fas fa-check mr-2"></i>Aprovar
            </button>
        </form>
        <button onclick="openRejectModal()" class="px-6 py-2.5 bg-red-600 hover:bg-red-700 text-white rounded-lg transition">
            <i class="fas fa-times mr-2"></i>Rejeitar
        </button>
    </div>
    <?php endif; ?>
</div>

<!-- Stats Cards -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <div class="card p-6">
        <div class="flex items-center justify-between mb-4">
            <div class="stat-icon w-12 h-12 rounded-xl flex items-center justify-center">
                <i class="fas fa-wallet text-white text-xl"></i>
            </div>
        </div>
        <p class="text-slate-400 text-sm mb-1">Saldo Disponível</p>
        <p class="text-3xl font-bold text-white">R$ <?= number_format($seller['balance'], 2, ',', '.') ?></p>
    </div>

    <div class="card p-6">
        <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 rounded-xl flex items-center justify-center bg-gradient-to-br from-green-500 to-green-600">
                <i class="fas fa-arrow-down text-white text-xl"></i>
            </div>
        </div>
        <p class="text-slate-400 text-sm mb-1">Total Cash-in</p>
        <p class="text-3xl font-bold text-white">R$ <?= number_format($cashinStats['total'] ?? 0, 2, ',', '.') ?></p>
    </div>

    <div class="card p-6">
        <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 rounded-xl flex items-center justify-center bg-gradient-to-br from-red-500 to-red-600">
                <i class="fas fa-arrow-up text-white text-xl"></i>
            </div>
        </div>
        <p class="text-slate-400 text-sm mb-1">Total Cash-out</p>
        <p class="text-3xl font-bold text-white">R$ <?= number_format($cashoutStats['total'] ?? 0, 2, ',', '.') ?></p>
    </div>
</div>

<!-- Main Content Grid -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Left Column - 2/3 width -->
    <div class="lg:col-span-2 space-y-6">
        <!-- Information Card -->
        <div class="card p-6">
            <h3 class="text-xl font-bold text-white mb-6 flex items-center">
                <i class="fas fa-info-circle text-blue-500 mr-2"></i>
                Informações do Seller
            </h3>
            <div class="grid grid-cols-2 gap-6">
                <div>
                    <label class="text-sm font-medium text-slate-400">ID</label>
                    <p class="text-white mt-1 font-semibold">#<?= $seller['id'] ?></p>
                </div>
                <div>
                    <label class="text-sm font-medium text-slate-400">Tipo de Pessoa</label>
                    <p class="text-white mt-1"><?= $seller['person_type'] === 'individual' ? 'Pessoa Física' : 'Pessoa Jurídica' ?></p>
                </div>
                <div>
                    <label class="text-sm font-medium text-slate-400">Documento</label>
                    <p class="text-white mt-1 font-mono"><?= htmlspecialchars($seller['document']) ?></p>
                </div>
                <div>
                    <label class="text-sm font-medium text-slate-400">Telefone</label>
                    <p class="text-white mt-1"><?= htmlspecialchars($seller['phone'] ?? 'Não informado') ?></p>
                </div>
                <?php if ($seller['person_type'] === 'business'): ?>
                <div class="col-span-2">
                    <label class="text-sm font-medium text-slate-400">Razão Social</label>
                    <p class="text-white mt-1"><?= htmlspecialchars($seller['company_name'] ?? 'Não informado') ?></p>
                </div>
                <?php if ($seller['trading_name']): ?>
                <div class="col-span-2">
                    <label class="text-sm font-medium text-slate-400">Nome Fantasia</label>
                    <p class="text-white mt-1"><?= htmlspecialchars($seller['trading_name']) ?></p>
                </div>
                <?php endif; ?>
                <?php endif; ?>
                <div>
                    <label class="text-sm font-medium text-slate-400">Cadastro</label>
                    <p class="text-white mt-1"><?= date('d/m/Y H:i', strtotime($seller['created_at'])) ?></p>
                </div>
                <?php if ($seller['approved_at']): ?>
                <div>
                    <label class="text-sm font-medium text-slate-400">Aprovado em</label>
                    <p class="text-white mt-1"><?= date('d/m/Y H:i', strtotime($seller['approved_at'])) ?></p>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Personal Information Card -->
        <?php if ($seller['personal_info_completed']): ?>
        <div class="card p-6">
            <h3 class="text-xl font-bold text-white mb-6 flex items-center">
                <i class="fas fa-user-check text-green-500 mr-2"></i>
                Informações Pessoais
                <span class="badge badge-success ml-3 text-xs">
                    <i class="fas fa-check-circle mr-1"></i>Completo
                </span>
            </h3>
            <div class="grid grid-cols-2 gap-6">
                <!-- Document Information -->
                <div class="col-span-2 p-4 bg-slate-800 bg-opacity-50 rounded-lg border border-slate-700">
                    <h4 class="text-sm font-semibold text-white mb-4 flex items-center">
                        <i class="fas fa-id-card text-blue-500 mr-2"></i>
                        Documento de Identificação
                    </h4>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-sm font-medium text-slate-400">Tipo de Documento</label>
                            <p class="text-white mt-1 font-semibold">
                                <?= $seller['personal_document_type'] === 'rg' ? 'RG - Registro Geral' : 'CNH - Carteira de Habilitação' ?>
                            </p>
                        </div>
                        <?php if ($seller['personal_document_type'] === 'rg'): ?>
                            <div>
                                <label class="text-sm font-medium text-slate-400">Número do RG</label>
                                <p class="text-white mt-1 font-mono"><?= htmlspecialchars($seller['rg_number'] ?? 'N/A') ?></p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-slate-400">Órgão Emissor</label>
                                <p class="text-white mt-1"><?= htmlspecialchars($seller['rg_issuer'] ?? 'N/A') ?></p>
                            </div>
                            <?php if ($seller['rg_issue_date']): ?>
                            <div>
                                <label class="text-sm font-medium text-slate-400">Data de Emissão</label>
                                <p class="text-white mt-1"><?= date('d/m/Y', strtotime($seller['rg_issue_date'])) ?></p>
                            </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div>
                                <label class="text-sm font-medium text-slate-400">Número da CNH</label>
                                <p class="text-white mt-1 font-mono"><?= htmlspecialchars($seller['cnh_number'] ?? 'N/A') ?></p>
                            </div>
                            <?php if ($seller['cnh_expiry_date']): ?>
                            <div>
                                <label class="text-sm font-medium text-slate-400">Data de Validade</label>
                                <p class="text-white mt-1"><?= date('d/m/Y', strtotime($seller['cnh_expiry_date'])) ?></p>
                            </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Birth Date -->
                <?php if ($seller['birth_date']): ?>
                <div>
                    <label class="text-sm font-medium text-slate-400">Data de Nascimento</label>
                    <p class="text-white mt-1"><?= date('d/m/Y', strtotime($seller['birth_date'])) ?></p>
                </div>
                <div>
                    <label class="text-sm font-medium text-slate-400">Idade</label>
                    <p class="text-white mt-1">
                        <?php
                        $birthDate = new DateTime($seller['birth_date']);
                        $today = new DateTime();
                        $age = $today->diff($birthDate)->y;
                        echo $age . ' anos';
                        ?>
                    </p>
                </div>
                <?php endif; ?>

                <!-- Address Information -->
                <div class="col-span-2 p-4 bg-slate-800 bg-opacity-50 rounded-lg border border-slate-700">
                    <h4 class="text-sm font-semibold text-white mb-4 flex items-center">
                        <i class="fas fa-map-marker-alt text-red-500 mr-2"></i>
                        Endereço Residencial
                    </h4>
                    <div class="space-y-3">
                        <div class="grid grid-cols-3 gap-4">
                            <div>
                                <label class="text-sm font-medium text-slate-400">CEP</label>
                                <p class="text-white mt-1 font-mono"><?= htmlspecialchars($seller['address_zipcode'] ?? 'N/A') ?></p>
                            </div>
                            <div class="col-span-2">
                                <label class="text-sm font-medium text-slate-400">Rua/Avenida</label>
                                <p class="text-white mt-1"><?= htmlspecialchars($seller['address_street'] ?? 'N/A') ?></p>
                            </div>
                        </div>
                        <div class="grid grid-cols-3 gap-4">
                            <div>
                                <label class="text-sm font-medium text-slate-400">Número</label>
                                <p class="text-white mt-1"><?= htmlspecialchars($seller['address_number'] ?? 'N/A') ?></p>
                            </div>
                            <div class="col-span-2">
                                <label class="text-sm font-medium text-slate-400">Complemento</label>
                                <p class="text-white mt-1"><?= htmlspecialchars($seller['address_complement'] ?: 'Não informado') ?></p>
                            </div>
                        </div>
                        <div class="grid grid-cols-3 gap-4">
                            <div>
                                <label class="text-sm font-medium text-slate-400">Bairro</label>
                                <p class="text-white mt-1"><?= htmlspecialchars($seller['address_neighborhood'] ?? 'N/A') ?></p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-slate-400">Cidade</label>
                                <p class="text-white mt-1"><?= htmlspecialchars($seller['address_city'] ?? 'N/A') ?></p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-slate-400">Estado</label>
                                <p class="text-white mt-1 uppercase"><?= htmlspecialchars($seller['address_state'] ?? 'N/A') ?></p>
                            </div>
                        </div>
                        <?php if ($seller['address_street'] && $seller['address_city'] && $seller['address_state']): ?>
                        <div class="pt-3 border-t border-slate-700">
                            <a href="https://www.google.com/maps/search/?api=1&query=<?= urlencode($seller['address_street'] . ', ' . $seller['address_number'] . ', ' . $seller['address_neighborhood'] . ', ' . $seller['address_city'] . ' - ' . $seller['address_state'] . ', ' . $seller['address_zipcode']) ?>"
                               target="_blank"
                               class="inline-flex items-center text-blue-400 hover:text-blue-300 text-sm">
                                <i class="fas fa-map-marked-alt mr-2"></i>
                                Ver no Google Maps
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php else: ?>
        <div class="card p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-xl font-bold text-white mb-2 flex items-center">
                        <i class="fas fa-user-times text-yellow-500 mr-2"></i>
                        Informações Pessoais
                    </h3>
                    <p class="text-slate-400 text-sm">O seller ainda não completou as informações pessoais</p>
                </div>
                <span class="badge badge-warning">
                    <i class="fas fa-exclamation-triangle mr-1"></i>Incompleto
                </span>
            </div>
        </div>
        <?php endif; ?>

        <!-- Fees Configuration Card -->
        <div class="card p-6">
            <h3 class="text-xl font-bold text-white mb-6 flex items-center">
                <i class="fas fa-percentage text-yellow-500 mr-2"></i>
                Configuração de Taxas
            </h3>
            <form method="POST" action="/admin/sellers/<?= $seller['id'] ?>/fees" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Cash-in Fees -->
                    <div class="p-4 bg-slate-800 bg-opacity-50 rounded-lg border border-slate-700">
                        <h4 class="text-lg font-semibold text-white mb-4 flex items-center">
                            <i class="fas fa-arrow-down text-green-500 mr-2"></i>
                            Taxas Cash-in
                        </h4>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-300 mb-2">
                                    Taxa Percentual (%)
                                </label>
                                <div class="relative">
                                    <input type="text"
                                           name="fee_percentage_cashin"
                                           value="<?= number_format($seller['fee_percentage_cashin'] * 100, 2, ',', '') ?>"
                                           class="w-full px-4 py-2.5 pr-8"
                                           placeholder="0,00"
                                           required>
                                    <span class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400">%</span>
                                </div>
                                <p class="text-xs text-slate-500 mt-1">Atual: <?= number_format($seller['fee_percentage_cashin'] * 100, 2) ?>%</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-300 mb-2">
                                    Taxa Fixa (R$)
                                </label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">R$</span>
                                    <input type="text"
                                           name="fee_fixed_cashin"
                                           value="<?= number_format($seller['fee_fixed_cashin'], 2, ',', '') ?>"
                                           class="w-full px-4 py-2.5 pl-11"
                                           placeholder="0,00"
                                           required>
                                </div>
                                <p class="text-xs text-slate-500 mt-1">Atual: R$ <?= number_format($seller['fee_fixed_cashin'], 2, ',', '.') ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- Cash-out Fees -->
                    <div class="p-4 bg-slate-800 bg-opacity-50 rounded-lg border border-slate-700">
                        <h4 class="text-lg font-semibold text-white mb-4 flex items-center">
                            <i class="fas fa-arrow-up text-red-500 mr-2"></i>
                            Taxas Cash-out
                        </h4>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-300 mb-2">
                                    Taxa Percentual (%)
                                </label>
                                <div class="relative">
                                    <input type="text"
                                           name="fee_percentage_cashout"
                                           value="<?= number_format($seller['fee_percentage_cashout'] * 100, 2, ',', '') ?>"
                                           class="w-full px-4 py-2.5 pr-8"
                                           placeholder="0,00"
                                           required>
                                    <span class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400">%</span>
                                </div>
                                <p class="text-xs text-slate-500 mt-1">Atual: <?= number_format($seller['fee_percentage_cashout'] * 100, 2) ?>%</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-300 mb-2">
                                    Taxa Fixa (R$)
                                </label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">R$</span>
                                    <input type="text"
                                           name="fee_fixed_cashout"
                                           value="<?= number_format($seller['fee_fixed_cashout'], 2, ',', '') ?>"
                                           class="w-full px-4 py-2.5 pl-11"
                                           placeholder="0,00"
                                           required>
                                </div>
                                <p class="text-xs text-slate-500 mt-1">Atual: R$ <?= number_format($seller['fee_fixed_cashout'], 2, ',', '.') ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Configuração de Retenção -->
                <div class="p-4 bg-slate-800 bg-opacity-50 rounded-lg border border-slate-700 mt-6">
                    <h4 class="text-lg font-semibold text-white mb-4 flex items-center">
                        <i class="fas fa-hand-holding-usd text-yellow-500 mr-2"></i>
                        Retenção de Valores
                    </h4>
                    <div class="space-y-4">
                        <div class="flex items-center justify-between p-3 bg-slate-900/50 rounded-lg">
                            <span class="text-white text-sm">Reter Saldo</span>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="balance_retention" value="1" <?= $seller['balance_retention'] ? 'checked' : '' ?> class="sr-only peer">
                                <div class="w-11 h-6 bg-slate-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-yellow-600"></div>
                            </label>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-2">
                                % Retenção do Faturamento
                            </label>
                            <div class="relative">
                                <input type="text"
                                       name="revenue_retention_percentage"
                                       value="<?= number_format($seller['revenue_retention_percentage'], 2, ',', '') ?>"
                                       class="w-full px-4 py-2.5 pr-10"
                                       placeholder="0,00">
                                <span class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400">%</span>
                            </div>
                            <p class="text-xs text-slate-500 mt-1">0% = sem retenção | Atual: <?= number_format($seller['revenue_retention_percentage'], 2, ',', '') ?>%</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-2">Motivo da Retenção</label>
                            <textarea name="retention_reason" rows="2" class="w-full px-4 py-2.5" placeholder="Descreva o motivo da retenção..."><?= htmlspecialchars($seller['retention_reason'] ?? '') ?></textarea>
                        </div>
                        <?php if ($seller['balance_retention'] && $seller['revenue_retention_percentage'] > 0): ?>
                        <div class="bg-yellow-900/20 border border-yellow-700 rounded-lg p-3">
                            <p class="text-yellow-400 text-xs font-medium mb-1">
                                <i class="fas fa-exclamation-triangle mr-1"></i>Retenção Ativa
                            </p>
                            <?php if ($seller['retention_started_at']): ?>
                            <p class="text-yellow-300 text-xs">Iniciada em: <?= date('d/m/Y H:i', strtotime($seller['retention_started_at'])) ?></p>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="flex items-center justify-between pt-4 border-t border-slate-700">
                    <div class="text-sm text-slate-400">
                        <i class="fas fa-info-circle mr-1"></i>
                        As taxas e retenções são aplicadas automaticamente
                    </div>
                    <button type="submit" class="btn-primary px-6 py-2.5 rounded-lg font-medium">
                        <i class="fas fa-save mr-2"></i>Salvar Configurações
                    </button>
                </div>
            </form>
        </div>

        <!-- Transaction Limits Configuration Card -->
        <div class="card p-6">
            <h3 class="text-xl font-bold text-white mb-6 flex items-center">
                <i class="fas fa-sliders-h text-blue-500 mr-2"></i>
                Limites de Transação
            </h3>
            <form method="POST" action="/admin/sellers/<?= $seller['id'] ?>/limits" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Cash-in Limits -->
                    <div class="p-4 bg-slate-800 bg-opacity-50 rounded-lg border border-slate-700">
                        <h4 class="text-lg font-semibold text-white mb-4 flex items-center">
                            <i class="fas fa-arrow-down text-green-500 mr-2"></i>
                            Limites Cash-in
                        </h4>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-300 mb-2">
                                    Valor Mínimo (R$)
                                </label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">R$</span>
                                    <input type="text"
                                           name="min_cashin_amount"
                                           value="<?= $seller['min_cashin_amount'] ? number_format($seller['min_cashin_amount'], 2, ',', '') : '' ?>"
                                           class="w-full px-4 py-2.5 pl-11"
                                           placeholder="Sem limite">
                                </div>
                                <p class="text-xs text-slate-500 mt-1">Deixe vazio para sem limite mínimo</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-300 mb-2">
                                    Valor Máximo (R$)
                                </label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">R$</span>
                                    <input type="text"
                                           name="max_cashin_amount"
                                           value="<?= $seller['max_cashin_amount'] ? number_format($seller['max_cashin_amount'], 2, ',', '') : '' ?>"
                                           class="w-full px-4 py-2.5 pl-11"
                                           placeholder="Sem limite">
                                </div>
                                <p class="text-xs text-slate-500 mt-1">Deixe vazio para sem limite máximo</p>
                            </div>
                        </div>
                    </div>

                    <!-- Cash-out Limits -->
                    <div class="p-4 bg-slate-800 bg-opacity-50 rounded-lg border border-slate-700">
                        <h4 class="text-lg font-semibold text-white mb-4 flex items-center">
                            <i class="fas fa-arrow-up text-red-500 mr-2"></i>
                            Limites Cash-out
                        </h4>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-300 mb-2">
                                    Valor Mínimo (R$)
                                </label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">R$</span>
                                    <input type="text"
                                           name="min_cashout_amount"
                                           value="<?= $seller['min_cashout_amount'] ? number_format($seller['min_cashout_amount'], 2, ',', '') : '' ?>"
                                           class="w-full px-4 py-2.5 pl-11"
                                           placeholder="Sem limite">
                                </div>
                                <p class="text-xs text-slate-500 mt-1">Deixe vazio para sem limite mínimo</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-300 mb-2">
                                    Valor Máximo (R$)
                                </label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">R$</span>
                                    <input type="text"
                                           name="max_cashout_amount"
                                           value="<?= $seller['max_cashout_amount'] ? number_format($seller['max_cashout_amount'], 2, ',', '') : '' ?>"
                                           class="w-full px-4 py-2.5 pl-11"
                                           placeholder="Sem limite">
                                </div>
                                <p class="text-xs text-slate-500 mt-1">Deixe vazio para sem limite máximo</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-300 mb-2">
                                    Limite Diário (R$)
                                </label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">R$</span>
                                    <input type="text"
                                           name="cashout_daily_limit"
                                           value="<?= $seller['cashout_daily_limit'] ? number_format($seller['cashout_daily_limit'], 2, ',', '') : '' ?>"
                                           class="w-full px-4 py-2.5 pl-11"
                                           placeholder="Sem limite">
                                </div>
                                <p class="text-xs text-slate-500 mt-1">Deixe vazio para sem limite diário</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-between pt-4 border-t border-slate-700">
                    <div class="text-sm text-slate-400">
                        <i class="fas fa-info-circle mr-1"></i>
                        Limites são validados em cada transação
                    </div>
                    <button type="submit" class="btn-primary px-6 py-2.5 rounded-lg font-medium">
                        <i class="fas fa-save mr-2"></i>Salvar Limites
                    </button>
                </div>
            </form>
        </div>

        <!-- Processing Accounts -->
        <div class="card p-6">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-xl font-bold text-white flex items-center">
                    <i class="fas fa-wallet text-green-500 mr-2"></i>
                    Contas de Processamento
                    <span class="text-sm text-slate-400 ml-3 font-normal">
                        <?= isset($accounts) ? count($accounts) : 0 ?> conta(s)
                    </span>
                </h3>
                <button onclick="openAddAccountModal()" class="btn-primary px-4 py-2 rounded-lg text-sm font-medium">
                    <i class="fas fa-plus mr-2"></i>Adicionar Conta
                </button>
            </div>

            <?php if (empty($accounts)): ?>
                <div class="text-center py-12 bg-slate-800 bg-opacity-50 rounded-lg border border-slate-700">
                    <i class="fas fa-wallet text-5xl text-slate-600 mb-3"></i>
                    <p class="text-slate-400 mb-4">Nenhuma conta de processamento atribuída</p>
                    <p class="text-slate-500 text-sm">As transações deste seller usarão as contas padrão do sistema</p>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <?php foreach ($accounts as $account): ?>
                    <div class="p-4 bg-slate-800 bg-opacity-50 rounded-lg border border-slate-700" data-account-id="<?= $account['acquirer_account_id'] ?>">
                        <div class="flex items-start justify-between mb-3">
                            <div class="flex-1">
                                <h4 class="font-semibold text-white"><?= htmlspecialchars($account['account_name']) ?></h4>
                                <p class="text-sm text-slate-400 mt-1">
                                    <i class="fas fa-building mr-1"></i>
                                    <?= htmlspecialchars($account['acquirer_name']) ?>
                                </p>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="px-2 py-1 text-xs font-medium rounded-full <?= $account['is_active'] && $account['account_active'] ? 'bg-green-900 text-green-300' : 'bg-red-900 text-red-300' ?>">
                                    <?= $account['is_active'] && $account['account_active'] ? 'Ativa' : 'Inativa' ?>
                                </span>
                                <button onclick="toggleAccountStatus(<?= $account['acquirer_account_id'] ?>)" class="text-slate-400 hover:text-white text-sm">
                                    <i class="fas fa-power-off"></i>
                                </button>
                                <button onclick="removeAccount(<?= $account['acquirer_account_id'] ?>)" class="text-red-400 hover:text-red-300 text-sm">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3 mb-3">
                            <div>
                                <p class="text-xs text-slate-500">Prioridade</p>
                                <p class="text-sm font-medium text-white">
                                    <i class="fas fa-sort text-slate-500 mr-1"></i><?= $account['priority'] ?>
                                </p>
                            </div>
                            <div>
                                <p class="text-xs text-slate-500">Código</p>
                                <p class="text-sm font-medium text-white uppercase"><?= htmlspecialchars($account['acquirer_type']) ?></p>
                            </div>
                        </div>

                        <div class="grid grid-cols-3 gap-2 py-2 border-t border-slate-700">
                            <div class="text-center">
                                <p class="text-xs text-slate-500">Transações</p>
                                <p class="text-sm font-bold text-white"><?= $account['total_transactions'] ?? 0 ?></p>
                            </div>
                            <div class="text-center">
                                <p class="text-xs text-slate-500">Volume</p>
                                <p class="text-sm font-bold text-green-400">R$ <?= number_format($account['total_volume'] ?? 0, 2, ',', '.') ?></p>
                            </div>
                            <div class="text-center">
                                <p class="text-xs text-slate-500">Identificador</p>
                                <p class="text-xs font-medium text-slate-300 truncate" title="<?= htmlspecialchars($account['account_identifier']) ?>">
                                    <?= htmlspecialchars(substr($account['account_identifier'], 0, 8)) ?>...
                                </p>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Recent Transactions -->
        <div class="card p-6">
            <h3 class="text-xl font-bold text-white mb-6 flex items-center justify-between">
                <span>
                    <i class="fas fa-history text-purple-500 mr-2"></i>
                    Transações Recentes
                </span>
                <a href="/admin/transactions?seller_id=<?= $seller['id'] ?>" class="text-blue-400 hover:text-blue-300 text-sm">
                    Ver todas <i class="fas fa-arrow-right ml-1"></i>
                </a>
            </h3>
            <div class="space-y-3">
                <?php if (empty($recentTransactions)): ?>
                    <div class="text-center py-12">
                        <i class="fas fa-receipt text-5xl text-slate-600 mb-3"></i>
                        <p class="text-slate-400">Nenhuma transação ainda</p>
                    </div>
                <?php else: ?>
                    <?php foreach (array_slice($recentTransactions, 0, 10) as $tx): ?>
                    <div class="p-4 bg-slate-800 bg-opacity-50 rounded-lg border border-slate-700 hover:border-blue-500 transition">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-white font-bold text-lg">R$ <?= number_format($tx['amount'], 2, ',', '.') ?></p>
                                <p class="text-sm text-slate-400"><?= $tx['transaction_id'] ?></p>
                            </div>
                            <div class="text-right">
                                <?php
                                $badgeClass = 'badge-info';
                                if (in_array($tx['status'] ?? '', ['paid', 'approved', 'completed', 'COMPLETED'])) {
                                    $badgeClass = 'badge-success';
                                } elseif (in_array($tx['status'] ?? '', ['waiting_payment', 'pending', 'PENDING_QUEUE'])) {
                                    $badgeClass = 'badge-warning';
                                } elseif (in_array($tx['status'] ?? '', ['failed', 'cancelled', 'refused'])) {
                                    $badgeClass = 'badge-danger';
                                }
                                ?>
                                <span class="badge <?= $badgeClass ?>"><?= ucfirst($tx['status'] ?? 'Unknown') ?></span>
                                <p class="text-xs text-slate-500 mt-1"><?= date('d/m H:i', strtotime($tx['created_at'])) ?></p>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Right Column - 1/3 width -->
    <div class="space-y-6">
        <!-- Documentos -->
        <div class="card p-6">
            <h3 class="text-lg font-bold text-white mb-4 flex items-center justify-between">
                <span>
                    <i class="fas fa-file-alt text-purple-500 mr-2"></i>
                    Documentos
                </span>
                <?php if (!empty($missingDocs)): ?>
                <span class="badge badge-warning text-xs">
                    <i class="fas fa-exclamation-triangle mr-1"></i><?= count($missingDocs) ?> faltando
                </span>
                <?php endif; ?>
            </h3>

            <?php if (!empty($missingDocs)): ?>
            <div class="bg-yellow-900/20 border border-yellow-700 rounded-lg p-3 mb-4">
                <p class="text-yellow-400 text-sm font-medium mb-2">
                    <i class="fas fa-exclamation-circle mr-1"></i>Documentos Faltantes:
                </p>
                <ul class="text-yellow-300 text-sm space-y-1 list-disc list-inside">
                    <?php foreach ($missingDocs as $doc): ?>
                    <li><?= $doc ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <div class="space-y-3">
                <?php if (empty($documents)): ?>
                    <div class="text-center py-6">
                        <i class="fas fa-folder-open text-3xl text-slate-600 mb-2"></i>
                        <p class="text-slate-400 text-sm">Nenhum documento enviado</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($documents as $doc): ?>
                        <?php
                        $statusIcons = [
                            'pending' => ['icon' => 'clock', 'color' => 'text-yellow-500'],
                            'approved' => ['icon' => 'check-circle', 'color' => 'text-green-500'],
                            'rejected' => ['icon' => 'times-circle', 'color' => 'text-red-500']
                        ];
                        $status = $statusIcons[$doc['status']] ?? $statusIcons['pending'];
                        ?>
                        <div class="flex items-center justify-between p-3 bg-slate-800/50 rounded-lg hover:bg-slate-700/50 transition">
                            <div class="flex items-center space-x-3">
                                <i class="fas fa-<?= $status['icon'] ?> <?= $status['color'] ?>"></i>
                                <div>
                                    <p class="text-white text-sm font-medium"><?= $this->getDocumentTypeName($doc['document_type']) ?></p>
                                    <p class="text-slate-400 text-xs"><?= ucfirst($doc['status']) ?></p>
                                </div>
                            </div>
                            <button onclick="viewDocument(<?= $doc['id'] ?>, '<?= htmlspecialchars($doc['file_path']) ?>', '<?= $this->getDocumentTypeName($doc['document_type']) ?>')"
                                    class="text-blue-400 hover:text-blue-300 text-sm">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Controles Administrativos -->
        <div class="card p-6">
            <h3 class="text-lg font-bold text-white mb-4 flex items-center">
                <i class="fas fa-cog text-red-500 mr-2"></i>
                Controles Administrativos
            </h3>

            <!-- Status de Operações -->
            <div class="space-y-3 mb-4">
                <div class="flex items-center justify-between p-3 bg-slate-800/50 rounded-lg">
                    <div class="flex items-center space-x-3">
                        <i class="fas fa-arrow-down text-green-500"></i>
                        <span class="text-white text-sm">Cash-in</span>
                    </div>
                    <form method="POST" action="/admin/sellers/<?= $seller['id'] ?>/toggle-cashin" class="inline">
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" <?= $seller['cashin_enabled'] ? 'checked' : '' ?> onchange="this.form.submit()" class="sr-only peer">
                            <div class="w-11 h-6 bg-slate-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-600"></div>
                        </label>
                    </form>
                </div>

                <div class="flex items-center justify-between p-3 bg-slate-800/50 rounded-lg">
                    <div class="flex items-center space-x-3">
                        <i class="fas fa-arrow-up text-red-500"></i>
                        <span class="text-white text-sm">Cash-out</span>
                    </div>
                    <form method="POST" action="/admin/sellers/<?= $seller['id'] ?>/toggle-cashout" class="inline">
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" <?= $seller['cashout_enabled'] ? 'checked' : '' ?> onchange="this.form.submit()" class="sr-only peer">
                            <div class="w-11 h-6 bg-slate-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-600"></div>
                        </label>
                    </form>
                </div>
            </div>

            <!-- Bloqueio -->
            <?php if ($seller['temporarily_blocked'] || $seller['permanently_blocked']): ?>
            <div class="bg-red-900/20 border border-red-700 rounded-lg p-4 mb-4">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-red-400 font-medium text-sm">
                        <i class="fas fa-ban mr-1"></i>
                        <?= $seller['permanently_blocked'] ? 'Bloqueado Permanentemente' : 'Bloqueado Temporariamente' ?>
                    </span>
                </div>
                <?php if ($seller['blocked_reason']): ?>
                <p class="text-red-300 text-xs mb-3"><?= htmlspecialchars($seller['blocked_reason']) ?></p>
                <?php endif; ?>
                <form method="POST" action="/admin/sellers/<?= $seller['id'] ?>/unblock">
                    <button type="submit" class="w-full px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded text-sm transition">
                        <i class="fas fa-unlock mr-1"></i>Desbloquear
                    </button>
                </form>
            </div>
            <?php else: ?>
            <button onclick="openBlockModal()" class="w-full px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded text-sm transition">
                <i class="fas fa-ban mr-1"></i>Bloquear Seller
            </button>
            <?php endif; ?>
        </div>

        <!-- API Credentials -->
        <?php if ($seller['api_key']): ?>
        <div class="card p-6">
            <h3 class="text-lg font-bold text-white mb-4 flex items-center">
                <i class="fas fa-key text-blue-500 mr-2"></i>
                Credenciais API
            </h3>
            <div class="space-y-4">
                <div>
                    <label class="text-sm font-medium text-slate-400">API Key</label>
                    <div class="mt-2 flex items-center space-x-2">
                        <code class="flex-1 px-3 py-2 bg-slate-900 text-slate-300 rounded text-xs font-mono break-all"><?= htmlspecialchars($seller['api_key']) ?></code>
                        <button onclick="copyToClipboard('<?= $seller['api_key'] ?>', this)" class="px-3 py-2 bg-slate-700 hover:bg-slate-600 text-white rounded transition">
                            <i class="fas fa-copy"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Documents -->
        <div class="card p-6">
            <h3 class="text-lg font-bold text-white mb-4 flex items-center justify-between">
                <span>
                    <i class="fas fa-file-alt text-purple-500 mr-2"></i>
                    Documentos
                </span>
                <span class="badge badge-<?= $seller['document_status'] === 'approved' ? 'success' : 'warning' ?>">
                    <?= ucfirst($seller['document_status']) ?>
                </span>
            </h3>
            <div class="space-y-3">
                <?php if (empty($documents)): ?>
                    <p class="text-slate-400 text-sm">Nenhum documento enviado</p>
                <?php else: ?>
                    <?php foreach ($documents as $doc): ?>
                    <div class="p-3 bg-slate-800 bg-opacity-50 rounded-lg border border-slate-700">
                        <div class="flex items-center justify-between">
                            <div class="flex-1">
                                <p class="text-sm text-white font-medium"><?= ucfirst(str_replace('_', ' ', $doc['document_type'])) ?></p>
                                <p class="text-xs text-slate-500"><?= date('d/m/Y', strtotime($doc['created_at'])) ?></p>
                            </div>
                            <div class="flex items-center space-x-2">
                                <?php
                                $docBadgeClass = match($doc['status']) {
                                    'approved' => 'badge-success',
                                    'rejected' => 'badge-danger',
                                    'under_review' => 'badge-warning',
                                    default => 'badge-info'
                                };
                                ?>
                                <span class="badge <?= $docBadgeClass ?> text-xs"><?= ucfirst($doc['status']) ?></span>
                                <a href="/admin/documents/view/<?= $doc['id'] ?>" class="text-blue-400 hover:text-blue-300">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div id="rejectModal" class="modal hidden">
    <div class="modal-content max-w-md">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-2xl font-bold text-white">Rejeitar Seller</h3>
            <button onclick="closeRejectModal()" class="text-slate-400 hover:text-white">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <form method="POST" action="/admin/sellers/<?= $seller['id'] ?>/reject">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-slate-300 mb-2">Motivo da Rejeição</label>
                    <textarea name="reason" rows="4" class="w-full px-4 py-2.5" required placeholder="Digite o motivo da rejeição..."></textarea>
                </div>
            </div>
            <div class="flex items-center justify-end space-x-3 mt-6">
                <button type="button" onclick="closeRejectModal()" class="px-6 py-2.5 bg-slate-700 hover:bg-slate-600 text-white rounded-lg transition">
                    Cancelar
                </button>
                <button type="submit" class="px-6 py-2.5 bg-red-600 hover:bg-red-700 text-white rounded-lg transition">
                    <i class="fas fa-times mr-2"></i>Rejeitar
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Bloqueio -->
<div id="blockModal" class="modal hidden">
    <div class="modal-content max-w-md">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-2xl font-bold text-white">Bloquear Seller</h3>
            <button onclick="closeBlockModal()" class="text-slate-400 hover:text-white">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <form method="POST" action="/admin/sellers/<?= $seller['id'] ?>/block">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-slate-300 mb-2">Tipo de Bloqueio</label>
                    <select name="block_type" class="w-full px-4 py-2.5 bg-slate-700 border-slate-600 text-white rounded-lg" required>
                        <option value="temporary">Temporário</option>
                        <option value="permanent">Permanente</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-300 mb-2">Motivo</label>
                    <textarea name="reason" rows="4" class="w-full px-4 py-2.5" placeholder="Descreva o motivo do bloqueio..." required></textarea>
                </div>
            </div>
            <div class="flex items-center justify-end space-x-3 mt-6">
                <button type="button" onclick="closeBlockModal()" class="px-6 py-2.5 bg-slate-700 hover:bg-slate-600 text-white rounded-lg transition">
                    Cancelar
                </button>
                <button type="submit" class="px-6 py-2.5 bg-red-600 hover:bg-red-700 text-white rounded-lg transition">
                    <i class="fas fa-ban mr-2"></i>Bloquear
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Visualizar Documento -->
<div id="documentModal" class="modal hidden">
    <div class="modal-content max-w-4xl">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-2xl font-bold text-white" id="documentTitle">Documento</h3>
            <button onclick="closeDocumentModal()" class="text-slate-400 hover:text-white">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <div class="bg-slate-800 rounded-lg p-6 mb-6">
            <img id="documentImage" src="" alt="Documento" class="w-full rounded-lg">
        </div>
        <div class="flex items-center justify-end space-x-3">
            <button type="button" onclick="closeDocumentModal()" class="px-6 py-2.5 bg-slate-700 hover:bg-slate-600 text-white rounded-lg transition">
                Fechar
            </button>
            <button type="button" onclick="approveDocument()" class="px-6 py-2.5 bg-green-600 hover:bg-green-700 text-white rounded-lg transition">
                <i class="fas fa-check mr-2"></i>Aprovar
            </button>
            <button type="button" onclick="rejectDocument()" class="px-6 py-2.5 bg-red-600 hover:bg-red-700 text-white rounded-lg transition">
                <i class="fas fa-times mr-2"></i>Rejeitar
            </button>
        </div>
    </div>
</div>

<script>
let currentDocumentId = null;

function openRejectModal() {
    document.getElementById('rejectModal').classList.remove('hidden');
    document.getElementById('rejectModal').classList.add('flex');
}

function closeRejectModal() {
    document.getElementById('rejectModal').classList.add('hidden');
    document.getElementById('rejectModal').classList.remove('flex');
}

function openBlockModal() {
    document.getElementById('blockModal').classList.remove('hidden');
    document.getElementById('blockModal').classList.add('flex');
}

function closeBlockModal() {
    document.getElementById('blockModal').classList.add('hidden');
    document.getElementById('blockModal').classList.remove('flex');
}

function viewDocument(docId, filePath, docName) {
    currentDocumentId = docId;
    document.getElementById('documentTitle').textContent = docName;
    document.getElementById('documentImage').src = '/uploads/documents/' + filePath;
    document.getElementById('documentModal').classList.remove('hidden');
    document.getElementById('documentModal').classList.add('flex');
}

function closeDocumentModal() {
    document.getElementById('documentModal').classList.add('hidden');
    document.getElementById('documentModal').classList.remove('flex');
    currentDocumentId = null;
}

function approveDocument() {
    if (!currentDocumentId) return;

    fetch(`/admin/documents/${currentDocumentId}/approve`, {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(async data => {
        if (data.success) {
            await customAlert('Documento aprovado com sucesso!', 'Sucesso', 'success');
            location.reload();
        }
    })
    .catch(async error => {
        await customAlert('Erro ao aprovar documento', 'Erro', 'error');
    });
}

async function rejectDocument() {
    if (!currentDocumentId) return;

    const reason = prompt('Motivo da rejeição:');
    if (!reason) return;

    const formData = new FormData();
    formData.append('reason', reason);

    fetch(`/admin/documents/${currentDocumentId}/reject`, {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(response => response.json())
    .then(async data => {
        if (data.success) {
            await customAlert('Documento rejeitado', 'Sucesso', 'success');
            location.reload();
        }
    })
    .catch(async error => {
        await customAlert('Erro ao rejeitar documento', 'Erro', 'error');
    });
}

function openAddAccountModal() {
    console.log('Abrindo modal de adicionar conta...');

    fetch('/admin/accounts/available')
        .then(response => {
            console.log('Resposta recebida:', response.status);
            if (!response.ok) {
                throw new Error('Erro na resposta: ' + response.status);
            }
            return response.json();
        })
        .then(async data => {
            console.log('Dados recebidos:', data);
            if (data.success) {
                showAccountSelectionModal(data.accounts);
            } else {
                await customAlert('Erro: ' + (data.error || 'Resposta inválida'), 'Erro', 'error');
            }
        })
        .catch(async error => {
            console.error('Erro ao carregar contas:', error);
            await customAlert('Erro ao carregar contas disponíveis: ' + error.message, 'Erro', 'error');
        });
}

async function showAccountSelectionModal(accounts) {
    if (!accounts || accounts.length === 0) {
        await customAlert('Não há contas disponíveis. Crie uma conta de adquirente primeiro.', 'Aviso', 'info');
        return;
    }

    const modal = document.createElement('div');
    modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
    modal.innerHTML = `
        <div class="bg-slate-900 rounded-lg p-6 max-w-2xl w-full mx-4 max-h-[80vh] overflow-y-auto">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-xl font-bold text-white">Adicionar Conta de Processamento</h3>
                <button onclick="this.closest('.fixed').remove()" class="text-slate-400 hover:text-white">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div class="space-y-4 mb-6">
                <div>
                    <label class="block text-sm font-medium text-slate-300 mb-2">Selecione uma conta</label>
                    <select id="accountSelect" class="w-full px-4 py-2.5 rounded-lg bg-slate-800 border border-slate-700 text-white">
                        <option value="">Selecione...</option>
                        ${accounts.map(acc => `
                            <option value="${acc.id}">
                                ${acc.acquirer_name} - ${acc.account_name} (${acc.account_identifier})
                            </option>
                        `).join('')}
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-300 mb-2">Prioridade</label>
                    <input type="number" id="accountPriority" value="1" min="1" class="w-full px-4 py-2.5 rounded-lg bg-slate-800 border border-slate-700 text-white">
                    <p class="text-xs text-slate-500 mt-1">Menor número = maior prioridade</p>
                </div>
            </div>
            <div class="flex justify-end gap-3">
                <button onclick="this.closest('.fixed').remove()" class="px-4 py-2 bg-slate-700 hover:bg-slate-600 text-white rounded-lg">
                    Cancelar
                </button>
                <button onclick="addAccount()" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg">
                    <i class="fas fa-plus mr-2"></i>Adicionar
                </button>
            </div>
        </div>
    `;
    document.body.appendChild(modal);
}

async function addAccount() {
    const accountId = document.getElementById('accountSelect').value;
    const priority = document.getElementById('accountPriority').value;

    if (!accountId) {
        await customAlert('Selecione uma conta', 'Atenção', 'info');
        return;
    }

    fetch(`/admin/sellers/<?= $seller['id'] ?>/accounts/assign`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
            account_id: parseInt(accountId),
            priority: parseInt(priority)
        })
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Erro na resposta: ' + response.status);
        }
        return response.json();
    })
    .then(async data => {
        if (data.success) {
            location.reload();
        } else {
            await customAlert(data.error || 'Erro ao adicionar conta', 'Erro', 'error');
        }
    })
    .catch(async error => {
        console.error('Erro:', error);
        await customAlert('Erro ao adicionar conta: ' + error.message, 'Erro', 'error');
    });
}

async function removeAccount(accountId) {
    const confirmed = await customConfirm('Tem certeza que deseja remover esta conta?', 'Confirmar Remoção');
    if (!confirmed) {
        return;
    }

    fetch(`/admin/sellers/<?= $seller['id'] ?>/accounts/${accountId}/remove`, {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(async data => {
        if (data.success) {
            location.reload();
        } else {
            await customAlert(data.error || 'Erro ao remover conta', 'Erro', 'error');
        }
    })
    .catch(async error => {
        await customAlert('Erro ao remover conta', 'Erro', 'error');
    });
}

function toggleAccountStatus(accountId) {
    fetch(`/admin/sellers/<?= $seller['id'] ?>/accounts/${accountId}/toggle`, {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(async data => {
        if (data.success) {
            location.reload();
        } else {
            await customAlert(data.error || 'Erro ao alterar status', 'Erro', 'error');
        }
    })
    .catch(async error => {
        await customAlert('Erro ao alterar status', 'Erro', 'error');
    });
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
