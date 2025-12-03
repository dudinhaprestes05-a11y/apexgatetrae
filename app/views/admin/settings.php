<?php
$pageTitle = 'Configurações do Sistema';
require_once __DIR__ . '/../layouts/header.php';
?>

<div class="max-w-4xl">
    <div class="card p-6 mb-6">
        <h2 class="text-2xl font-bold text-white mb-6 flex items-center">
            <i class="fas fa-cog text-blue-500 mr-3"></i>
            Configurações Gerais
        </h2>

        <form method="POST" action="/admin/settings/update">
            <div class="space-y-6">
                <!-- Taxas Padrão Cash-in -->
                <div class="p-4 bg-slate-800 bg-opacity-50 rounded-lg border border-slate-700">
                    <h3 class="text-lg font-semibold text-white mb-4 flex items-center">
                        <i class="fas fa-arrow-down text-green-500 mr-2"></i>
                        Taxas Padrão Cash-in (Recebimentos)
                    </h3>
                    <p class="text-sm text-slate-400 mb-4">Essas taxas serão aplicadas automaticamente a todos os novos sellers cadastrados</p>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-2">
                                Taxa Percentual (%)
                            </label>
                            <div class="relative">
                                <input type="text"
                                       name="default_fee_percentage_cashin"
                                       value="<?= number_format($settings['default_fee_percentage_cashin'] * 100, 2, ',', '') ?>"
                                       class="w-full px-4 py-2.5 pr-10"
                                       placeholder="0,00"
                                       required>
                                <span class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400">%</span>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-2">
                                Taxa Fixa (R$)
                            </label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">R$</span>
                                <input type="text"
                                       name="default_fee_fixed_cashin"
                                       value="<?= number_format($settings['default_fee_fixed_cashin'], 2, ',', '') ?>"
                                       class="w-full px-4 py-2.5 pl-11"
                                       placeholder="0,00"
                                       required>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Taxas Padrão Cash-out -->
                <div class="p-4 bg-slate-800 bg-opacity-50 rounded-lg border border-slate-700">
                    <h3 class="text-lg font-semibold text-white mb-4 flex items-center">
                        <i class="fas fa-arrow-up text-red-500 mr-2"></i>
                        Taxas Padrão Cash-out (Saques)
                    </h3>
                    <p class="text-sm text-slate-400 mb-4">Essas taxas serão aplicadas automaticamente a todos os novos sellers cadastrados</p>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-2">
                                Taxa Percentual (%)
                            </label>
                            <div class="relative">
                                <input type="text"
                                       name="default_fee_percentage_cashout"
                                       value="<?= number_format($settings['default_fee_percentage_cashout'] * 100, 2, ',', '') ?>"
                                       class="w-full px-4 py-2.5 pr-10"
                                       placeholder="0,00"
                                       required>
                                <span class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400">%</span>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-2">
                                Taxa Fixa (R$)
                            </label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">R$</span>
                                <input type="text"
                                       name="default_fee_fixed_cashout"
                                       value="<?= number_format($settings['default_fee_fixed_cashout'], 2, ',', '') ?>"
                                       class="w-full px-4 py-2.5 pl-11"
                                       placeholder="0,00"
                                       required>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Informações Adicionais -->
                <div class="bg-blue-900/20 border border-blue-700 rounded-lg p-4">
                    <div class="flex items-start">
                        <i class="fas fa-info-circle text-blue-400 mt-1 mr-3"></i>
                        <div class="text-sm text-blue-300">
                            <p class="font-medium mb-1">Importante:</p>
                            <ul class="list-disc list-inside space-y-1 text-blue-200">
                                <li>Essas taxas são aplicadas APENAS a novos sellers</li>
                                <li>Sellers já cadastrados mantêm suas taxas atuais</li>
                                <li>Você pode personalizar as taxas individualmente na página de cada seller</li>
                                <li>Use vírgula para separar decimais (ex: 2,50)</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-between mt-6 pt-6 border-t border-slate-700">
                <?php if ($settings['updated_at']): ?>
                <div class="text-sm text-slate-400">
                    <i class="fas fa-clock mr-1"></i>
                    Última atualização: <?= date('d/m/Y H:i', strtotime($settings['updated_at'])) ?>
                </div>
                <?php else: ?>
                <div></div>
                <?php endif; ?>

                <button type="submit" class="btn-primary px-8 py-3 rounded-lg font-medium">
                    <i class="fas fa-save mr-2"></i>Salvar Configurações
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
