<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= APP_NAME ?> - Gateway de Pagamentos PIX</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap');

        * {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%);
            overflow-x: hidden;
        }

        .glass-card {
            background: rgba(30, 41, 59, 0.6);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(148, 163, 184, 0.1);
            transition: all 0.3s ease;
        }

        .glass-card:hover {
            background: rgba(30, 41, 59, 0.8);
            border-color: rgba(59, 130, 246, 0.3);
            transform: translateY(-5px);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4);
        }

        .gradient-text {
            background: linear-gradient(135deg, #3b82f6 0%, #60a5fa 50%, #93c5fd 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
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

        .btn-secondary {
            background: rgba(30, 41, 59, 0.6);
            backdrop-filter: blur(10px);
            border: 2px solid rgba(59, 130, 246, 0.3);
            transition: all 0.3s ease;
        }

        .btn-secondary:hover {
            background: rgba(59, 130, 246, 0.1);
            border-color: rgba(59, 130, 246, 0.6);
            transform: translateY(-2px);
        }

        .floating {
            animation: floating 3s ease-in-out infinite;
        }

        @keyframes floating {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }

        .fade-in {
            animation: fadeIn 1s ease-in;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .navbar {
            background: rgba(15, 23, 42, 0.8);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(148, 163, 184, 0.1);
        }

        .stat-number {
            font-size: 3rem;
            font-weight: 900;
            line-height: 1;
        }

        @media (max-width: 768px) {
            .stat-number {
                font-size: 2rem;
            }
        }

        .pricing-card {
            position: relative;
            overflow: hidden;
        }

        .pricing-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #3b82f6, #60a5fa);
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }

        .pricing-card:hover::before {
            transform: scaleX(1);
        }

        .accordion-content {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease;
        }

        .accordion-content.active {
            max-height: 500px;
        }

        .glow {
            box-shadow: 0 0 20px rgba(59, 130, 246, 0.4);
        }
    </style>
</head>
<body class="text-gray-100">

    <!-- Navbar -->
    <nav class="navbar fixed top-0 left-0 right-0 z-50">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16 md:h-20">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-gradient-to-br from-blue-600 to-blue-400 rounded-xl flex items-center justify-center">
                        <i class="fas fa-bolt text-white text-xl"></i>
                    </div>
                    <span class="text-xl md:text-2xl font-bold text-white"><?= APP_NAME ?></span>
                </div>

                <div class="hidden md:flex items-center space-x-8">
                    <a href="#features" class="text-gray-300 hover:text-blue-400 transition">Recursos</a>
                    <a href="#how-it-works" class="text-gray-300 hover:text-blue-400 transition">Como Funciona</a>
                    <?php if (getenv('SHOW_PRICING_ON_LANDING') === 'true'): ?>
                    <a href="#pricing" class="text-gray-300 hover:text-blue-400 transition">Taxas</a>
                    <?php endif; ?>
                    <a href="#faq" class="text-gray-300 hover:text-blue-400 transition">FAQ</a>
                </div>

                <div class="flex items-center space-x-4">
                    <a href="/login" class="text-gray-300 hover:text-white transition text-sm md:text-base">Entrar</a>
                    <a href="/register" class="btn-primary px-3 py-2 md:px-6 md:py-2 rounded-lg text-white font-medium text-sm md:text-base">
                        Começar Agora
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="pt-32 pb-20 px-4 sm:px-6 lg:px-8 fade-in">
        <div class="container mx-auto max-w-7xl">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                <div>
                    <div class="inline-block mb-4">
                        <span class="px-4 py-2 bg-blue-500 bg-opacity-10 border border-blue-500 border-opacity-30 rounded-full text-blue-400 text-sm font-medium">
                            <i class="fas fa-rocket mr-2"></i>Gateway de Pagamentos PIX
                        </span>
                    </div>
                    <h1 class="text-4xl sm:text-5xl lg:text-6xl font-bold text-white mb-6 leading-tight">
                        Pagamentos PIX <span class="gradient-text">Simplificados</span> para seu Negócio
                    </h1>
                    <p class="text-xl text-gray-400 mb-8 leading-relaxed">
                        Integre pagamentos PIX em minutos com nossa API moderna e segura. Receba pagamentos e faça transferências com agilidade e transparência.
                    </p>
                    <div class="flex flex-col sm:flex-row gap-4">
                        <a href="/register" class="btn-primary px-6 py-3 sm:px-8 sm:py-4 rounded-xl text-white font-semibold text-center text-sm sm:text-base">
                            <i class="fas fa-rocket mr-2"></i>Começar Gratuitamente
                        </a>
                        <a href="#how-it-works" class="btn-secondary px-6 py-3 sm:px-8 sm:py-4 rounded-xl text-white font-semibold text-center text-sm sm:text-base">
                            <i class="fas fa-play-circle mr-2"></i>Como Funciona
                        </a>
                    </div>
                    <div class="mt-8 flex flex-col sm:flex-row items-start sm:items-center gap-3 sm:gap-8 text-xs sm:text-sm text-gray-400">
                        <div class="flex items-center">
                            <i class="fas fa-check-circle text-green-400 mr-2"></i>
                            Integração em 5 minutos
                        </div>
                        <div class="flex items-center">
                            <i class="fas fa-check-circle text-green-400 mr-2"></i>
                            API RESTful
                        </div>
                        <div class="flex items-center">
                            <i class="fas fa-check-circle text-green-400 mr-2"></i>
                            Suporte 24/7
                        </div>
                    </div>
                </div>

                <div class="hidden lg:block">
                    <div class="relative">
                        <div class="glass-card p-8 rounded-3xl floating">
                            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 border border-slate-700">
                                <div class="flex items-center justify-between mb-6">
                                    <span class="text-gray-400">Transação PIX</span>
                                    <span class="px-3 py-1 bg-green-500 bg-opacity-20 text-green-400 rounded-full text-sm">
                                        <i class="fas fa-check mr-1"></i>Aprovado
                                    </span>
                                </div>
                                <div class="mb-4">
                                    <div class="text-gray-400 text-sm mb-1">Valor</div>
                                    <div class="text-3xl font-bold text-white">R$ 1.250,00</div>
                                </div>
                                <div class="space-y-3 text-sm">
                                    <div class="flex justify-between">
                                        <span class="text-gray-400">Cliente</span>
                                        <span class="text-white">João Silva</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-400">Taxa</span>
                                        <span class="text-white">R$ 12,50</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-400">Líquido</span>
                                        <span class="text-green-400 font-semibold">R$ 1.237,50</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="absolute -top-10 -right-10 w-40 h-40 bg-blue-500 rounded-full opacity-20 blur-3xl"></div>
                        <div class="absolute -bottom-10 -left-10 w-40 h-40 bg-purple-500 rounded-full opacity-20 blur-3xl"></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="py-16 px-4 sm:px-6 lg:px-8">
        <div class="container mx-auto max-w-7xl">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div class="glass-card p-8 rounded-2xl text-center">
                    <div class="stat-number gradient-text mb-2">99.9%</div>
                    <div class="text-gray-400">Uptime</div>
                </div>
                <div class="glass-card p-8 rounded-2xl text-center">
                    <div class="stat-number gradient-text mb-2">&lt;2s</div>
                    <div class="text-gray-400">Tempo de Resposta</div>
                </div>
                <div class="glass-card p-8 rounded-2xl text-center">
                    <div class="stat-number gradient-text mb-2">24/7</div>
                    <div class="text-gray-400">Suporte</div>
                </div>
                <div class="glass-card p-8 rounded-2xl text-center">
                    <div class="stat-number gradient-text mb-2">100%</div>
                    <div class="text-gray-400">Seguro</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-20 px-4 sm:px-6 lg:px-8">
        <div class="container mx-auto max-w-7xl">
            <div class="text-center mb-16">
                <h2 class="text-4xl md:text-5xl font-bold text-white mb-4">
                    Recursos <span class="gradient-text">Poderosos</span>
                </h2>
                <p class="text-xl text-gray-400">Tudo que você precisa para gerenciar pagamentos PIX</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <div class="glass-card p-8 rounded-2xl">
                    <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl flex items-center justify-center mb-6 glow">
                        <i class="fas fa-shield-alt text-white text-2xl"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-white mb-4">Segurança Avançada</h3>
                    <p class="text-gray-400 leading-relaxed">
                        Criptografia de ponta a ponta, autenticação HMAC e whitelist de IPs para máxima proteção.
                    </p>
                </div>

                <div class="glass-card p-8 rounded-2xl">
                    <div class="w-16 h-16 bg-gradient-to-br from-green-500 to-green-600 rounded-2xl flex items-center justify-center mb-6 glow">
                        <i class="fas fa-bolt text-white text-2xl"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-white mb-4">Processamento Rápido</h3>
                    <p class="text-gray-400 leading-relaxed">
                        Transações processadas em tempo real com confirmação instantânea via webhook.
                    </p>
                </div>

                <div class="glass-card p-8 rounded-2xl">
                    <div class="w-16 h-16 bg-gradient-to-br from-purple-500 to-purple-600 rounded-2xl flex items-center justify-center mb-6 glow">
                        <i class="fas fa-code text-white text-2xl"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-white mb-4">API RESTful</h3>
                    <p class="text-gray-400 leading-relaxed">
                        Documentação completa e exemplos de código para integração rápida e fácil.
                    </p>
                </div>

                <div class="glass-card p-8 rounded-2xl">
                    <div class="w-16 h-16 bg-gradient-to-br from-yellow-500 to-yellow-600 rounded-2xl flex items-center justify-center mb-6 glow">
                        <i class="fas fa-chart-line text-white text-2xl"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-white mb-4">Dashboard Completo</h3>
                    <p class="text-gray-400 leading-relaxed">
                        Acompanhe todas as transações, relatórios e análises em tempo real.
                    </p>
                </div>

                <div class="glass-card p-8 rounded-2xl">
                    <div class="w-16 h-16 bg-gradient-to-br from-red-500 to-red-600 rounded-2xl flex items-center justify-center mb-6 glow">
                        <i class="fas fa-headset text-white text-2xl"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-white mb-4">Suporte Dedicado</h3>
                    <p class="text-gray-400 leading-relaxed">
                        Equipe especializada disponível 24/7 para auxiliar sua integração.
                    </p>
                </div>

                <div class="glass-card p-8 rounded-2xl">
                    <div class="w-16 h-16 bg-gradient-to-br from-indigo-500 to-indigo-600 rounded-2xl flex items-center justify-center mb-6 glow">
                        <i class="fas fa-sync-alt text-white text-2xl"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-white mb-4">Webhooks Inteligentes</h3>
                    <p class="text-gray-400 leading-relaxed">
                        Receba notificações automáticas sobre todas as transações em tempo real.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- How it Works Section -->
    <section id="how-it-works" class="py-20 px-4 sm:px-6 lg:px-8 bg-slate-900 bg-opacity-50">
        <div class="container mx-auto max-w-7xl">
            <div class="text-center mb-16">
                <h2 class="text-4xl md:text-5xl font-bold text-white mb-4">
                    Como <span class="gradient-text">Funciona</span>
                </h2>
                <p class="text-xl text-gray-400">Comece a receber pagamentos em 4 passos simples</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                <div class="text-center">
                    <div class="relative mb-6">
                        <div class="w-20 h-20 mx-auto bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl flex items-center justify-center text-3xl font-bold text-white glow">
                            1
                        </div>
                        <div class="hidden lg:block absolute top-10 left-1/2 w-full h-0.5 bg-gradient-to-r from-blue-500 to-transparent"></div>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-3">Cadastre-se</h3>
                    <p class="text-gray-400">Crie sua conta gratuitamente em menos de 2 minutos</p>
                </div>

                <div class="text-center">
                    <div class="relative mb-6">
                        <div class="w-20 h-20 mx-auto bg-gradient-to-br from-green-500 to-green-600 rounded-2xl flex items-center justify-center text-3xl font-bold text-white glow">
                            2
                        </div>
                        <div class="hidden lg:block absolute top-10 left-1/2 w-full h-0.5 bg-gradient-to-r from-green-500 to-transparent"></div>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-3">Integre</h3>
                    <p class="text-gray-400">Use nossa API RESTful simples e documentação completa</p>
                </div>

                <div class="text-center">
                    <div class="relative mb-6">
                        <div class="w-20 h-20 mx-auto bg-gradient-to-br from-purple-500 to-purple-600 rounded-2xl flex items-center justify-center text-3xl font-bold text-white glow">
                            3
                        </div>
                        <div class="hidden lg:block absolute top-10 left-1/2 w-full h-0.5 bg-gradient-to-r from-purple-500 to-transparent"></div>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-3">Processe</h3>
                    <p class="text-gray-400">Comece a processar pagamentos PIX instantaneamente</p>
                </div>

                <div class="text-center">
                    <div class="relative mb-6">
                        <div class="w-20 h-20 mx-auto bg-gradient-to-br from-yellow-500 to-yellow-600 rounded-2xl flex items-center justify-center text-3xl font-bold text-white glow">
                            4
                        </div>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-3">Receba</h3>
                    <p class="text-gray-400">Seus valores caem direto na sua conta rapidamente</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Pricing Section -->
    <?php if (getenv('SHOW_PRICING_ON_LANDING') === 'true'): ?>
    <section id="pricing" class="py-20 px-4 sm:px-6 lg:px-8">
        <div class="container mx-auto max-w-7xl">
            <div class="text-center mb-16">
                <h2 class="text-4xl md:text-5xl font-bold text-white mb-4">
                    Taxas <span class="gradient-text">Transparentes</span>
                </h2>
                <p class="text-xl text-gray-400">Sem custos ocultos, pague apenas pelo que usar</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 max-w-4xl mx-auto">
                <div class="glass-card pricing-card p-8 rounded-2xl border-2 border-blue-500 relative">
                    <div class="absolute -top-4 left-1/2 transform -translate-x-1/2">
                        <span class="px-4 py-1 bg-blue-500 text-white rounded-full text-sm font-semibold">
                            Padrão
                        </span>
                    </div>
                    <div class="text-center mb-8">
                        <h3 class="text-2xl font-bold text-white mb-2">Taxa Padrão</h3>
                        <p class="text-gray-400 mb-6">Para a maioria dos negócios</p>
                        <div class="mb-6">
                            <span class="text-5xl font-bold gradient-text">A partir de</span>
                        </div>
                        <div class="mb-6">
                            <span class="text-4xl font-bold text-white">1%</span>
                            <span class="text-gray-400 text-lg"> + R$ 0,50</span>
                        </div>
                        <p class="text-sm text-gray-400">por transação PIX</p>
                    </div>
                    <ul class="space-y-4 mb-8">
                        <li class="flex items-center text-gray-300">
                            <i class="fas fa-check-circle text-green-400 mr-3"></i>
                            API RESTful completa
                        </li>
                        <li class="flex items-center text-gray-300">
                            <i class="fas fa-check-circle text-green-400 mr-3"></i>
                            Dashboard completo
                        </li>
                        <li class="flex items-center text-gray-300">
                            <i class="fas fa-check-circle text-green-400 mr-3"></i>
                            Webhooks em tempo real
                        </li>
                        <li class="flex items-center text-gray-300">
                            <i class="fas fa-check-circle text-green-400 mr-3"></i>
                            Relatórios detalhados
                        </li>
                        <li class="flex items-center text-gray-300">
                            <i class="fas fa-check-circle text-green-400 mr-3"></i>
                            Split de pagamentos
                        </li>
                        <li class="flex items-center text-gray-300">
                            <i class="fas fa-check-circle text-green-400 mr-3"></i>
                            Suporte técnico
                        </li>
                    </ul>
                    <a href="/register" class="btn-primary w-full px-6 py-3 rounded-xl text-white font-semibold text-center block">
                        Começar Agora
                    </a>
                </div>

                <div class="glass-card pricing-card p-8 rounded-2xl">
                    <div class="text-center mb-8">
                        <h3 class="text-2xl font-bold text-white mb-2">Enterprise</h3>
                        <p class="text-gray-400 mb-6">Para grandes volumes</p>
                        <div class="mb-6">
                            <span class="text-5xl font-bold text-white">A combinar</span>
                        </div>
                        <p class="text-sm text-gray-400 mb-6">taxas personalizadas</p>
                    </div>
                    <ul class="space-y-4 mb-8">
                        <li class="flex items-center text-gray-300">
                            <i class="fas fa-check-circle text-green-400 mr-3"></i>
                            Todos os recursos
                        </li>
                        <li class="flex items-center text-gray-300">
                            <i class="fas fa-check-circle text-green-400 mr-3"></i>
                            Taxas customizadas
                        </li>
                        <li class="flex items-center text-gray-300">
                            <i class="fas fa-check-circle text-green-400 mr-3"></i>
                            Gerente de conta dedicado
                        </li>
                        <li class="flex items-center text-gray-300">
                            <i class="fas fa-check-circle text-green-400 mr-3"></i>
                            SLA garantido
                        </li>
                        <li class="flex items-center text-gray-300">
                            <i class="fas fa-check-circle text-green-400 mr-3"></i>
                            Integrações personalizadas
                        </li>
                        <li class="flex items-center text-gray-300">
                            <i class="fas fa-check-circle text-green-400 mr-3"></i>
                            Suporte prioritário 24/7
                        </li>
                    </ul>
                    <a href="/register" class="btn-secondary w-full px-6 py-3 rounded-xl text-white font-semibold text-center block">
                        Entre em Contato
                    </a>
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- FAQ Section -->
    <section id="faq" class="py-20 px-4 sm:px-6 lg:px-8 bg-slate-900 bg-opacity-50">
        <div class="container mx-auto max-w-4xl">
            <div class="text-center mb-16">
                <h2 class="text-4xl md:text-5xl font-bold text-white mb-4">
                    Perguntas <span class="gradient-text">Frequentes</span>
                </h2>
                <p class="text-xl text-gray-400">Tire suas dúvidas sobre nossa plataforma</p>
            </div>

            <div class="space-y-4">
                <div class="glass-card rounded-xl overflow-hidden">
                    <button class="accordion-btn w-full p-6 text-left flex items-center justify-between" onclick="toggleAccordion(this)">
                        <span class="text-lg font-semibold text-white">Como funciona a integração?</span>
                        <i class="fas fa-chevron-down text-gray-400 transition-transform"></i>
                    </button>
                    <div class="accordion-content px-6 pb-6">
                        <p class="text-gray-400 leading-relaxed">
                            Nossa API RESTful é simples e documentada. Basta criar uma conta, gerar suas credenciais e fazer requisições HTTP para nossos endpoints. Fornecemos exemplos em várias linguagens de programação.
                        </p>
                    </div>
                </div>

                <div class="glass-card rounded-xl overflow-hidden">
                    <button class="accordion-btn w-full p-6 text-left flex items-center justify-between" onclick="toggleAccordion(this)">
                        <span class="text-lg font-semibold text-white">Quanto tempo leva para receber os pagamentos?</span>
                        <i class="fas fa-chevron-down text-gray-400 transition-transform"></i>
                    </button>
                    <div class="accordion-content px-6 pb-6">
                        <p class="text-gray-400 leading-relaxed">
                            Os pagamentos PIX são processados instantaneamente. O valor fica disponível para saque conforme o prazo configurado para sua conta, geralmente D+1 ou D+2.
                        </p>
                    </div>
                </div>

                <div class="glass-card rounded-xl overflow-hidden">
                    <button class="accordion-btn w-full p-6 text-left flex items-center justify-between" onclick="toggleAccordion(this)">
                        <span class="text-lg font-semibold text-white">Há taxa de setup ou mensalidade?</span>
                        <i class="fas fa-chevron-down text-gray-400 transition-transform"></i>
                    </button>
                    <div class="accordion-content px-6 pb-6">
                        <p class="text-gray-400 leading-relaxed">
                            Não! Não cobramos taxa de setup nem mensalidade. Você paga apenas pelas transações processadas, de acordo com o plano escolhido.
                        </p>
                    </div>
                </div>

                <div class="glass-card rounded-xl overflow-hidden">
                    <button class="accordion-btn w-full p-6 text-left flex items-center justify-between" onclick="toggleAccordion(this)">
                        <span class="text-lg font-semibold text-white">Como funciona o suporte técnico?</span>
                        <i class="fas fa-chevron-down text-gray-400 transition-transform"></i>
                    </button>
                    <div class="accordion-content px-6 pb-6">
                        <p class="text-gray-400 leading-relaxed">
                            Oferecemos suporte via email para todos os planos. Planos Business e Enterprise têm suporte prioritário com SLA garantido e gerente de conta dedicado.
                        </p>
                    </div>
                </div>

                <div class="glass-card rounded-xl overflow-hidden">
                    <button class="accordion-btn w-full p-6 text-left flex items-center justify-between" onclick="toggleAccordion(this)">
                        <span class="text-lg font-semibold text-white">Posso testar antes de usar em produção?</span>
                        <i class="fas fa-chevron-down text-gray-400 transition-transform"></i>
                    </button>
                    <div class="accordion-content px-6 pb-6">
                        <p class="text-gray-400 leading-relaxed">
                            Sim! Fornecemos um ambiente de sandbox completo para você testar todas as funcionalidades antes de ir para produção.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-20 px-4 sm:px-6 lg:px-8">
        <div class="container mx-auto max-w-5xl">
            <div class="glass-card p-12 rounded-3xl text-center relative overflow-hidden">
                <div class="absolute top-0 left-0 w-full h-full opacity-10">
                    <div class="absolute top-10 left-10 w-40 h-40 bg-blue-500 rounded-full blur-3xl"></div>
                    <div class="absolute bottom-10 right-10 w-40 h-40 bg-purple-500 rounded-full blur-3xl"></div>
                </div>
                <div class="relative z-10">
                    <h2 class="text-4xl md:text-5xl font-bold text-white mb-6">
                        Pronto para <span class="gradient-text">Começar?</span>
                    </h2>
                    <p class="text-xl text-gray-400 mb-8 max-w-2xl mx-auto">
                        Junte-se a centenas de empresas que já processam pagamentos PIX com nossa plataforma
                    </p>
                    <div class="flex flex-col sm:flex-row gap-4 justify-center">
                        <a href="/register" class="btn-primary px-6 py-3 sm:px-10 sm:py-4 rounded-xl text-white font-semibold text-base sm:text-lg">
                            <i class="fas fa-rocket mr-2"></i>Criar Conta Gratuita
                        </a>
                        <a href="/docs/api" class="btn-secondary px-6 py-3 sm:px-10 sm:py-4 rounded-xl text-white font-semibold text-base sm:text-lg">
                            <i class="fas fa-book mr-2"></i>Ver Documentação
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="py-12 px-4 sm:px-6 lg:px-8 border-t border-gray-800">
        <div class="container mx-auto max-w-7xl">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8 mb-8">
                <div>
                    <div class="flex items-center space-x-3 mb-4">
                        <div class="w-10 h-10 bg-gradient-to-br from-blue-600 to-blue-400 rounded-xl flex items-center justify-center">
                            <i class="fas fa-bolt text-white text-xl"></i>
                        </div>
                        <span class="text-xl font-bold text-white"><?= APP_NAME ?></span>
                    </div>
                    <p class="text-gray-400 text-sm leading-relaxed">
                        Gateway de pagamentos PIX moderno e seguro para empresas de todos os tamanhos.
                    </p>
                </div>

                <div>
                    <h3 class="text-white font-semibold mb-4">Produto</h3>
                    <ul class="space-y-2">
                        <li><a href="#features" class="text-gray-400 hover:text-blue-400 transition text-sm">Recursos</a></li>
                        <?php if (getenv('SHOW_PRICING_ON_LANDING') === 'true'): ?>
                        <li><a href="#pricing" class="text-gray-400 hover:text-blue-400 transition text-sm">Taxas</a></li>
                        <?php endif; ?>
                        <li><a href="/docs/api" class="text-gray-400 hover:text-blue-400 transition text-sm">Documentação</a></li>
                        <li><a href="#faq" class="text-gray-400 hover:text-blue-400 transition text-sm">FAQ</a></li>
                    </ul>
                </div>

                <div>
                    <h3 class="text-white font-semibold mb-4">Empresa</h3>
                    <ul class="space-y-2">
                        <li><a href="#" class="text-gray-400 hover:text-blue-400 transition text-sm">Sobre Nós</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-blue-400 transition text-sm">Blog</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-blue-400 transition text-sm">Carreiras</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-blue-400 transition text-sm">Contato</a></li>
                    </ul>
                </div>

                <div>
                    <h3 class="text-white font-semibold mb-4">Legal</h3>
                    <ul class="space-y-2">
                        <li><a href="#" class="text-gray-400 hover:text-blue-400 transition text-sm">Termos de Uso</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-blue-400 transition text-sm">Política de Privacidade</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-blue-400 transition text-sm">Segurança</a></li>
                    </ul>
                </div>
            </div>

            <div class="border-t border-gray-800 pt-8 flex flex-col md:flex-row items-center justify-between">
                <p class="text-gray-400 text-sm mb-4 md:mb-0">
                    &copy; <?= date('Y') ?> <?= APP_NAME ?>. Todos os direitos reservados.
                </p>
                <div class="flex items-center space-x-6">
                    <a href="#" class="text-gray-400 hover:text-blue-400 transition">
                        <i class="fab fa-twitter text-xl"></i>
                    </a>
                    <a href="#" class="text-gray-400 hover:text-blue-400 transition">
                        <i class="fab fa-linkedin text-xl"></i>
                    </a>
                    <a href="#" class="text-gray-400 hover:text-blue-400 transition">
                        <i class="fab fa-github text-xl"></i>
                    </a>
                    <a href="#" class="text-gray-400 hover:text-blue-400 transition">
                        <i class="fab fa-instagram text-xl"></i>
                    </a>
                </div>
            </div>
        </div>
    </footer>

    <script>
        function toggleAccordion(button) {
            const content = button.nextElementSibling;
            const icon = button.querySelector('i');
            const allContents = document.querySelectorAll('.accordion-content');
            const allIcons = document.querySelectorAll('.accordion-btn i');

            allContents.forEach(item => {
                if (item !== content) {
                    item.classList.remove('active');
                }
            });

            allIcons.forEach(item => {
                if (item !== icon) {
                    item.style.transform = 'rotate(0deg)';
                }
            });

            content.classList.toggle('active');
            icon.style.transform = content.classList.contains('active') ? 'rotate(180deg)' : 'rotate(0deg)';
        }

        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    </script>

</body>
</html>
