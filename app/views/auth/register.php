<?php
$oldData = $_SESSION['old_data'] ?? [];
unset($_SESSION['old_data']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro - <?= APP_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
</head>
<body class="bg-gradient-to-br from-blue-50 to-gray-100 min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-2xl">
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-br from-blue-600 to-blue-400 rounded-2xl mb-4 shadow-lg">
                <i class="fas fa-bolt text-white text-2xl"></i>
            </div>
            <h1 class="text-3xl font-bold text-gray-900"><?= APP_NAME ?></h1>
            <p class="text-gray-600 mt-2">Crie sua conta de seller</p>
        </div>

        <div class="bg-white rounded-2xl shadow-xl p-8">
            <?php if (isset($_SESSION['error'])): ?>
            <div class="mb-4 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
                <i class="fas fa-exclamation-circle mr-2"></i>
                <?= htmlspecialchars($_SESSION['error']) ?>
            </div>
            <?php unset($_SESSION['error']); endif; ?>

            <form method="POST" action="/register" class="space-y-5">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Nome Completo *</label>
                        <input type="text" name="name" required
                               value="<?= htmlspecialchars($oldData['name'] ?? '') ?>"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                               placeholder="João Silva">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Email *</label>
                        <input type="email" name="email" required
                               value="<?= htmlspecialchars($oldData['email'] ?? '') ?>"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                               placeholder="seu@email.com">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">CPF/CNPJ *</label>
                        <input type="text" name="document" required
                               value="<?= htmlspecialchars($oldData['document'] ?? '') ?>"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                               placeholder="000.000.000-00">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Telefone *</label>
                        <input type="text" name="phone" required
                               value="<?= htmlspecialchars($oldData['phone'] ?? '') ?>"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                               placeholder="(11) 99999-9999">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tipo de Pessoa *</label>
                        <select name="person_type" required onchange="togglePersonType(this.value)"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
                            <option value="">Selecione</option>
                            <option value="individual" <?= ($oldData['person_type'] ?? '') === 'individual' ? 'selected' : '' ?>>Pessoa Física</option>
                            <option value="business" <?= ($oldData['person_type'] ?? '') === 'business' ? 'selected' : '' ?>>Pessoa Jurídica</option>
                        </select>
                    </div>

                    <div id="business-fields" style="display: none;" class="md:col-span-2">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Razão Social</label>
                                <input type="text" name="company_name"
                                       value="<?= htmlspecialchars($oldData['company_name'] ?? '') ?>"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Nome Fantasia</label>
                                <input type="text" name="trading_name"
                                       value="<?= htmlspecialchars($oldData['trading_name'] ?? '') ?>"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Faturamento Mensal</label>
                                <input type="number" step="0.01" name="monthly_revenue"
                                       value="<?= htmlspecialchars($oldData['monthly_revenue'] ?? '') ?>"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                                       placeholder="10000.00">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Ticket Médio</label>
                                <input type="number" step="0.01" name="average_ticket"
                                       value="<?= htmlspecialchars($oldData['average_ticket'] ?? '') ?>"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                                       placeholder="100.00">
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Senha *</label>
                        <input type="password" name="password" required
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                               placeholder="••••••••">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Confirmar Senha *</label>
                        <input type="password" name="password_confirmation" required
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                               placeholder="••••••••">
                    </div>
                </div>

                <div class="flex justify-center">
                    <div class="cf-turnstile" data-sitekey="<?= $_ENV['TURNSTILE_SITE_KEY'] ?? '' ?>"></div>
                </div>

                <button type="submit" id="registerBtn"
                        class="w-full bg-gradient-to-r from-blue-600 to-blue-500 text-white py-3 rounded-lg font-medium hover:from-blue-700 hover:to-blue-600 transition shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 disabled:opacity-50 disabled:cursor-not-allowed">
                    <i class="fas fa-user-plus mr-2"></i>Criar Conta
                </button>
            </form>

            <div class="mt-6 text-center">
                <p class="text-gray-600 text-sm">
                    Já tem uma conta?
                    <a href="/login" class="text-blue-600 hover:text-blue-700 font-medium">Faça login</a>
                </p>
            </div>
        </div>

        <p class="text-center text-gray-500 text-sm mt-8">
            &copy; <?= date('Y') ?> <?= APP_NAME ?>. Todos os direitos reservados.
        </p>
    </div>

    <script>
        function togglePersonType(type) {
            const businessFields = document.getElementById('business-fields');
            businessFields.style.display = type === 'business' ? 'block' : 'none';
        }

        document.addEventListener('DOMContentLoaded', () => {
            const personType = document.querySelector('[name="person_type"]').value;
            if (personType) togglePersonType(personType);

            const registerForm = document.querySelector('form');
            registerForm.addEventListener('submit', async function(e) {
                const turnstileResponse = document.querySelector('[name="cf-turnstile-response"]');

                if (!turnstileResponse || !turnstileResponse.value) {
                    e.preventDefault();
                    await customAlert('Por favor, complete a verificação de segurança.', 'Atenção', 'info');
                    return false;
                }
            });
        });
    </script>
</body>
</html>
