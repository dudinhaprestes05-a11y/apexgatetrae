<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?= APP_NAME ?></title>
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
    </style>
</head>
<body class="flex items-center justify-center p-4 relative overflow-hidden">

    <!-- Background Particles -->
    <div class="absolute top-20 left-20 w-32 h-32 bg-blue-500 rounded-full opacity-10 blur-3xl particle floating"></div>
    <div class="absolute bottom-20 right-20 w-40 h-40 bg-purple-500 rounded-full opacity-10 blur-3xl particle floating" style="animation-delay: 1s;"></div>
    <div class="absolute top-1/2 left-1/3 w-24 h-24 bg-indigo-500 rounded-full opacity-10 blur-3xl particle floating" style="animation-delay: 2s;"></div>

    <div class="w-full max-w-md z-10 fade-in">
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
            <p class="text-gray-400">Entre na sua conta</p>
        </div>

        <!-- Login Card -->
        <div class="glass-card rounded-2xl p-8 shadow-2xl">
            <?php if (isset($_SESSION['error'])): ?>
            <div class="mb-6 bg-red-500 bg-opacity-10 border border-red-500 border-opacity-30 text-red-400 px-4 py-3 rounded-lg">
                <i class="fas fa-exclamation-circle mr-2"></i>
                <?= htmlspecialchars($_SESSION['error']) ?>
            </div>
            <?php unset($_SESSION['error']); endif; ?>

            <?php if (isset($_SESSION['success'])): ?>
            <div class="mb-6 bg-green-500 bg-opacity-10 border border-green-500 border-opacity-30 text-green-400 px-4 py-3 rounded-lg">
                <i class="fas fa-check-circle mr-2"></i>
                <?= htmlspecialchars($_SESSION['success']) ?>
            </div>
            <?php unset($_SESSION['success']); endif; ?>

            <form method="POST" action="/login" class="space-y-6">
                <!-- Email Field -->
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">
                        <i class="fas fa-envelope mr-2 text-blue-400"></i>Email
                    </label>
                    <input type="email"
                           name="email"
                           required
                           class="input-field w-full px-4 py-3 rounded-lg text-white placeholder-gray-500"
                           placeholder="seu@email.com"
                           autocomplete="email">
                </div>

                <!-- Password Field -->
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">
                        <i class="fas fa-lock mr-2 text-blue-400"></i>Senha
                    </label>
                    <input type="password"
                           name="password"
                           required
                           class="input-field w-full px-4 py-3 rounded-lg text-white placeholder-gray-500"
                           placeholder="••••••••"
                           autocomplete="current-password">
                </div>

                <!-- Turnstile -->
                <div class="flex justify-center py-2">
                    <div class="cf-turnstile" data-sitekey="<?= $_ENV['TURNSTILE_SITE_KEY'] ?? '' ?>"></div>
                </div>

                <!-- Submit Button -->
                <button type="submit"
                        id="loginBtn"
                        class="btn-primary w-full py-3.5 rounded-lg text-white font-semibold text-base disabled:opacity-50 disabled:cursor-not-allowed">
                    <i class="fas fa-sign-in-alt mr-2"></i>Entrar
                </button>
            </form>

            <!-- Register Link -->
            <div class="mt-6 text-center">
                <p class="text-gray-400 text-sm">
                    Não tem uma conta?
                    <a href="/register" class="text-blue-400 hover:text-blue-300 font-semibold transition">
                        Cadastre-se gratuitamente
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
        const loginForm = document.querySelector('form');
        const loginBtn = document.getElementById('loginBtn');

        loginForm.addEventListener('submit', async function(e) {
            const turnstileResponse = document.querySelector('[name="cf-turnstile-response"]');

            if (!turnstileResponse || !turnstileResponse.value) {
                e.preventDefault();
                alert('Por favor, complete a verificação de segurança.');
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
    </script>
</body>
</html>
