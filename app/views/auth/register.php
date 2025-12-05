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
    <link rel="icon" type="image/png" href="<?= FAVICON_URL ?>">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap');

        * {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%);
            min-height: 100vh;
            padding-bottom: 2rem;
        }

        .glass-card {
            background: rgba(30, 41, 59, 0.6);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(148, 163, 184, 0.1);
        }

        .input-field {
            background: rgba(15, 23, 42, 0.8);
            border: 1px solid rgba(148, 163, 184, 0.2);
            transition: all 0.3s ease;
        }

        .input-field:focus {
            background: rgba(30, 41, 59, 0.9);
            border-color: rgba(59, 130, 246, 0.5);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
            outline: none;
        }

        .btn-primary {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            box-shadow: 0 10px 30px rgba(59, 130, 246, 0.3);
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            box-shadow: 0 15px 40px rgba(59, 130, 246, 0.5);
            transform: translateY(-2px);
        }

        .logo-glow {
            box-shadow: 0 0 30px rgba(59, 130, 246, 0.4);
        }

        .fade-in {
            animation: fadeIn 0.8s ease-in;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .floating {
            animation: floating 3s ease-in-out infinite;
        }

        @keyframes floating {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }

        .particle {
            position: absolute;
            border-radius: 50%;
            pointer-events: none;
        }

        select {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
            background-position: right 0.5rem center;
            background-repeat: no-repeat;
            background-size: 1.5em 1.5em;
            padding-right: 2.5rem;
        }
    </style>
</head>
<body class="p-4 py-12 relative">

    <!-- Background Particles -->
    <div class="absolute top-20 left-20 w-32 h-32 bg-blue-500 rounded-full opacity-10 blur-3xl particle floating"></div>
    <div class="absolute bottom-20 right-20 w-40 h-40 bg-purple-500 rounded-full opacity-10 blur-3xl particle floating" style="animation-delay: 1s;"></div>
    <div class="absolute top-1/2 left-1/3 w-24 h-24 bg-indigo-500 rounded-full opacity-10 blur-3xl particle floating" style="animation-delay: 2s;"></div>

    <div class="w-full max-w-3xl mx-auto z-10 fade-in">
        <!-- Back to Home Link -->
        <div class="mb-6 text-center">
            <a href="/" class="inline-flex items-center text-gray-400 hover:text-blue-400 transition text-sm">
                <i class="fas fa-arrow-left mr-2"></i>
                Voltar para o início
            </a>
        </div>

        <!-- Logo and Title -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center mb-4">
                <img src="<?= LOGO_URL ?>" alt="<?= APP_NAME ?>" class="h-16 w-auto object-contain logo-glow" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                <div class="w-16 h-16 bg-gradient-to-br from-blue-600 to-blue-400 rounded-2xl items-center justify-center logo-glow hidden">
                    <i class="fas fa-bolt text-white text-2xl"></i>
                </div>
            </div>
            <h1 class="text-3xl md:text-4xl font-bold text-white mb-2"><?= APP_NAME ?></h1>
            <p class="text-gray-400">Crie sua conta de seller</p>
        </div>

        <!-- Register Card -->
        <div class="glass-card rounded-2xl p-6 md:p-8 shadow-2xl">
            <?php if (isset($_SESSION['error'])): ?>
            <div class="mb-6 bg-red-500 bg-opacity-10 border border-red-500 border-opacity-30 text-red-400 px-4 py-3 rounded-lg">
                <i class="fas fa-exclamation-circle mr-2"></i>
                <?= htmlspecialchars($_SESSION['error']) ?>
            </div>
            <?php unset($_SESSION['error']); endif; ?>

            <form method="POST" action="/register" class="space-y-6">
                <!-- Personal Information Section -->
                <div>
                    <h3 class="text-lg font-semibold text-white mb-4 flex items-center">
                        <div class="w-8 h-8 bg-blue-500 bg-opacity-20 rounded-lg flex items-center justify-center mr-3">
                            <i class="fas fa-user text-blue-400"></i>
                        </div>
                        Informações Pessoais
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-2">
                                <i class="fas fa-user-circle mr-2 text-blue-400"></i>Nome Completo *
                            </label>
                            <input type="text"
                                   name="name"
                                   required
                                   value="<?= htmlspecialchars($oldData['name'] ?? '') ?>"
                                   class="input-field w-full px-4 py-3 rounded-lg text-white placeholder-gray-500"
                                   placeholder="João Silva">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-2">
                                <i class="fas fa-envelope mr-2 text-blue-400"></i>Email *
                            </label>
                            <input type="email"
                                   name="email"
                                   required
                                   value="<?= htmlspecialchars($oldData['email'] ?? '') ?>"
                                   class="input-field w-full px-4 py-3 rounded-lg text-white placeholder-gray-500"
                                   placeholder="seu@email.com">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-2">
                                <i class="fas fa-id-card mr-2 text-blue-400"></i>CPF/CNPJ *
                            </label>
                            <input type="text"
                                   name="document"
                                   required
                                   value="<?= htmlspecialchars($oldData['document'] ?? '') ?>"
                                   class="input-field w-full px-4 py-3 rounded-lg text-white placeholder-gray-500"
                                   placeholder="000.000.000-00">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-2">
                                <i class="fas fa-phone mr-2 text-blue-400"></i>Telefone *
                            </label>
                            <input type="text"
                                   name="phone"
                                   required
                                   value="<?= htmlspecialchars($oldData['phone'] ?? '') ?>"
                                   class="input-field w-full px-4 py-3 rounded-lg text-white placeholder-gray-500"
                                   placeholder="(11) 99999-9999">
                        </div>
                    </div>
                </div>

                <!-- Business Information Section -->
                <div>
                    <h3 class="text-lg font-semibold text-white mb-4 flex items-center">
                        <div class="w-8 h-8 bg-purple-500 bg-opacity-20 rounded-lg flex items-center justify-center mr-3">
                            <i class="fas fa-building text-purple-400"></i>
                        </div>
                        Informações do Negócio
                    </h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-2">
                                <i class="fas fa-briefcase mr-2 text-purple-400"></i>Tipo de Pessoa *
                            </label>
                            <select name="person_type"
                                    required
                                    onchange="togglePersonType(this.value)"
                                    class="input-field w-full px-4 py-3 rounded-lg text-white appearance-none cursor-pointer">
                                <option value="">Selecione</option>
                                <option value="individual" <?= ($oldData['person_type'] ?? '') === 'individual' ? 'selected' : '' ?>>Pessoa Física</option>
                                <option value="business" <?= ($oldData['person_type'] ?? '') === 'business' ? 'selected' : '' ?>>Pessoa Jurídica</option>
                            </select>
                        </div>

                        <div id="business-fields" style="display: none;">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-300 mb-2">
                                        <i class="fas fa-file-alt mr-2 text-purple-400"></i>Razão Social
                                    </label>
                                    <input type="text"
                                           name="company_name"
                                           value="<?= htmlspecialchars($oldData['company_name'] ?? '') ?>"
                                           class="input-field w-full px-4 py-3 rounded-lg text-white placeholder-gray-500"
                                           placeholder="Nome da Empresa LTDA">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-300 mb-2">
                                        <i class="fas fa-store mr-2 text-purple-400"></i>Nome Fantasia
                                    </label>
                                    <input type="text"
                                           name="trading_name"
                                           value="<?= htmlspecialchars($oldData['trading_name'] ?? '') ?>"
                                           class="input-field w-full px-4 py-3 rounded-lg text-white placeholder-gray-500"
                                           placeholder="Nome Comercial">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-300 mb-2">
                                        <i class="fas fa-chart-line mr-2 text-purple-400"></i>Faturamento Mensal
                                    </label>
                                    <input type="number"
                                           step="0.01"
                                           name="monthly_revenue"
                                           value="<?= htmlspecialchars($oldData['monthly_revenue'] ?? '') ?>"
                                           class="input-field w-full px-4 py-3 rounded-lg text-white placeholder-gray-500"
                                           placeholder="10000.00">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-300 mb-2">
                                        <i class="fas fa-receipt mr-2 text-purple-400"></i>Ticket Médio
                                    </label>
                                    <input type="number"
                                           step="0.01"
                                           name="average_ticket"
                                           value="<?= htmlspecialchars($oldData['average_ticket'] ?? '') ?>"
                                           class="input-field w-full px-4 py-3 rounded-lg text-white placeholder-gray-500"
                                           placeholder="100.00">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Security Section -->
                <div>
                    <h3 class="text-lg font-semibold text-white mb-4 flex items-center">
                        <div class="w-8 h-8 bg-green-500 bg-opacity-20 rounded-lg flex items-center justify-center mr-3">
                            <i class="fas fa-lock text-green-400"></i>
                        </div>
                        Segurança
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-2">
                                <i class="fas fa-key mr-2 text-green-400"></i>Senha *
                            </label>
                            <input type="password"
                                   name="password"
                                   required
                                   class="input-field w-full px-4 py-3 rounded-lg text-white placeholder-gray-500"
                                   placeholder="••••••••"
                                   minlength="6">
                            <p class="text-xs text-gray-500 mt-1">Mínimo de 6 caracteres</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-2">
                                <i class="fas fa-check-circle mr-2 text-green-400"></i>Confirmar Senha *
                            </label>
                            <input type="password"
                                   name="password_confirmation"
                                   required
                                   class="input-field w-full px-4 py-3 rounded-lg text-white placeholder-gray-500"
                                   placeholder="••••••••"
                                   minlength="6">
                        </div>
                    </div>
                </div>

                <!-- Turnstile -->
                <div class="flex justify-center py-2">
                    <div class="cf-turnstile" data-sitekey="<?= $_ENV['TURNSTILE_SITE_KEY'] ?? '' ?>"></div>
                </div>

                <!-- Submit Button -->
                <button type="submit"
                        id="registerBtn"
                        class="btn-primary w-full py-4 rounded-lg text-white font-semibold text-base disabled:opacity-50 disabled:cursor-not-allowed">
                    <i class="fas fa-user-plus mr-2"></i>Criar Conta Gratuitamente
                </button>
            </form>

            <!-- Login Link -->
            <div class="mt-6 text-center">
                <p class="text-gray-400 text-sm">
                    Já tem uma conta?
                    <a href="/login" class="text-blue-400 hover:text-blue-300 font-semibold transition">
                        Faça login
                    </a>
                </p>
            </div>
        </div>

        <!-- Footer -->
        <p class="text-center text-gray-500 text-sm mt-8">
            &copy; <?= date('Y') ?> <?= APP_NAME ?>. Todos os direitos reservados.
        </p>
    </div>

    <script>
        function togglePersonType(type) {
            const businessFields = document.getElementById('business-fields');
            businessFields.style.display = type === 'business' ? 'block' : 'none';

            const inputs = businessFields.querySelectorAll('input');
            inputs.forEach(input => {
                if (type === 'business') {
                    input.setAttribute('required', 'required');
                } else {
                    input.removeAttribute('required');
                }
            });
        }

        document.addEventListener('DOMContentLoaded', () => {
            const personType = document.querySelector('[name="person_type"]').value;
            if (personType) togglePersonType(personType);

            const registerForm = document.querySelector('form');
            registerForm.addEventListener('submit', async function(e) {
                const turnstileResponse = document.querySelector('[name="cf-turnstile-response"]');

                if (!turnstileResponse || !turnstileResponse.value) {
                    e.preventDefault();
                    alert('Por favor, complete a verificação de segurança.');
                    return false;
                }

                const password = document.querySelector('[name="password"]').value;
                const passwordConfirmation = document.querySelector('[name="password_confirmation"]').value;

                if (password !== passwordConfirmation) {
                    e.preventDefault();
                    alert('As senhas não coincidem. Por favor, verifique.');
                    return false;
                }
            });

            // Add focus glow effect
            document.querySelectorAll('.input-field').forEach(input => {
                input.addEventListener('focus', function() {
                    this.style.boxShadow = '0 0 20px rgba(59, 130, 246, 0.2)';
                });

                input.addEventListener('blur', function() {
                    this.style.boxShadow = 'none';
                });
            });
        });
    </script>
</body>
</html>
