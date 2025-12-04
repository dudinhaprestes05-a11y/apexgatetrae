<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pageTitle = $pageTitle ?? 'Gateway PIX';
$user = CheckAuth::user();
$currentPath = $_SERVER['REQUEST_URI'];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> - Gateway PIX</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

        * {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
        }

        body {
            background: #0f172a;
            color: #e2e8f0;
        }

        .sidebar {
            background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%);
            border-right: 1px solid rgba(51, 65, 85, 0.5);
        }

        .sidebar-link {
            transition: all 0.2s ease;
            border-left: 3px solid transparent;
        }

        .sidebar-link:hover {
            background: rgba(30, 41, 59, 0.5);
            border-left-color: #3b82f6;
        }

        .sidebar-link.active {
            background: rgba(59, 130, 246, 0.1);
            border-left-color: #3b82f6;
            color: #60a5fa;
        }

        .card {
            background: linear-gradient(145deg, #1e293b 0%, #0f172a 100%);
            border: 1px solid rgba(51, 65, 85, 0.5);
            border-radius: 12px;
            transition: all 0.3s ease;
        }

        .card:hover {
            border-color: rgba(59, 130, 246, 0.3);
            transform: translateY(-2px);
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
        }

        .btn-primary {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            transform: translateY(-1px);
            box-shadow: 0 8px 20px rgba(59, 130, 246, 0.4);
        }

        .stat-card {
            background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
            border: 1px solid rgba(51, 65, 85, 0.8);
        }

        .stat-icon {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3);
        }

        .badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-weight: 600;
        }

        .badge-success {
            background: rgba(16, 185, 129, 0.2);
            color: #34d399;
            border: 1px solid rgba(16, 185, 129, 0.3);
        }

        .badge-warning {
            background: rgba(245, 158, 11, 0.2);
            color: #fbbf24;
            border: 1px solid rgba(245, 158, 11, 0.3);
        }

        .badge-danger {
            background: rgba(239, 68, 68, 0.2);
            color: #f87171;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }

        .badge-info {
            background: rgba(59, 130, 246, 0.2);
            color: #60a5fa;
            border: 1px solid rgba(59, 130, 246, 0.3);
        }

        input, select, textarea {
            background: #1e293b;
            border: 1px solid #334155;
            color: #e2e8f0;
            border-radius: 8px;
            transition: all 0.2s ease;
        }

        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .table-dark {
            background: #1e293b;
            border-radius: 12px;
            overflow: hidden;
        }

        .table-dark thead {
            background: #0f172a;
        }

        .table-dark tbody tr {
            border-bottom: 1px solid #334155;
            transition: all 0.2s ease;
        }

        .table-dark tbody tr:hover {
            background: rgba(59, 130, 246, 0.05);
        }

        .fade-in {
            animation: fadeIn 0.3s ease-in;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert {
            border-radius: 10px;
            padding: 1rem 1.25rem;
            backdrop-filter: blur(10px);
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.3);
            color: #34d399;
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #f87171;
        }

        .scrollbar-thin::-webkit-scrollbar {
            width: 6px;
        }

        .scrollbar-thin::-webkit-scrollbar-track {
            background: #1e293b;
        }

        .scrollbar-thin::-webkit-scrollbar-thumb {
            background: #334155;
            border-radius: 3px;
        }

        .scrollbar-thin::-webkit-scrollbar-thumb:hover {
            background: #475569;
        }

        .modal {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.75);
            backdrop-filter: blur(4px);
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            overflow-y: auto;
        }

        .modal-content {
            background: linear-gradient(145deg, #1e293b 0%, #0f172a 100%);
            border: 1px solid rgba(51, 65, 85, 0.5);
            border-radius: 16px;
            padding: 2rem;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            animation: modalFadeIn 0.2s ease-out;
        }

        @keyframes modalFadeIn {
            from {
                opacity: 0;
                transform: scale(0.95) translateY(-20px);
            }
            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }

        .modal.hidden {
            display: none;
        }
    </style>
</head>
<body class="bg-slate-900">
    <!-- Sidebar -->
    <div class="fixed inset-y-0 left-0 w-64 sidebar flex flex-col z-50">
        <!-- Logo -->
        <div class="p-6 border-b border-slate-700">
            <a href="<?= $user && $user['role'] === 'admin' ? '/admin/dashboard' : '/seller/dashboard' ?>" class="flex items-center space-x-3">
                <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center shadow-lg">
                    <i class="fas fa-bolt text-white"></i>
                </div>
                <div>
                    <div class="text-lg font-bold text-white">Gateway PIX</div>
                    <div class="text-xs text-slate-400">Pagamentos seguros</div>
                </div>
            </a>
        </div>

        <!-- Navigation -->
        <nav class="flex-1 px-3 py-6 overflow-y-auto scrollbar-thin">
            <?php if ($user && $user['role'] === 'admin'): ?>
                <div class="space-y-1">
                    <a href="/admin/dashboard" class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-lg text-sm <?= strpos($currentPath, '/admin/dashboard') !== false ? 'active' : 'text-slate-300' ?>">
                        <i class="fas fa-chart-line w-5"></i>
                        <span>Dashboard</span>
                    </a>

                    <a href="/admin/sellers" class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-lg text-sm <?= strpos($currentPath, '/admin/sellers') !== false ? 'active' : 'text-slate-300' ?>">
                        <i class="fas fa-store w-5"></i>
                        <span>Sellers</span>
                    </a>

                    <a href="/admin/transactions" class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-lg text-sm <?= strpos($currentPath, '/admin/transactions') !== false ? 'active' : 'text-slate-300' ?>">
                        <i class="fas fa-exchange-alt w-5"></i>
                        <span>Transações</span>
                    </a>

                    <a href="/admin/acquirers" class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-lg text-sm <?= strpos($currentPath, '/admin/acquirers') !== false ? 'active' : 'text-slate-300' ?>">
                        <i class="fas fa-building w-5"></i>
                        <span>Adquirentes</span>
                    </a>

                    <a href="/admin/documents" class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-lg text-sm <?= strpos($currentPath, '/admin/documents') !== false ? 'active' : 'text-slate-300' ?>">
                        <i class="fas fa-file-alt w-5"></i>
                        <span>Documentos</span>
                    </a>

                    <a href="/admin/reports" class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-lg text-sm <?= strpos($currentPath, '/admin/reports') !== false ? 'active' : 'text-slate-300' ?>">
                        <i class="fas fa-chart-bar w-5"></i>
                        <span>Relatórios</span>
                    </a>

                    <a href="/admin/logs" class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-lg text-sm <?= strpos($currentPath, '/admin/logs') !== false ? 'active' : 'text-slate-300' ?>">
                        <i class="fas fa-list-alt w-5"></i>
                        <span>Logs</span>
                    </a>

                    <div class="border-t border-slate-700 my-4"></div>

                    <a href="/admin/settings" class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-lg text-sm <?= strpos($currentPath, '/admin/settings') !== false ? 'active' : 'text-slate-300' ?>">
                        <i class="fas fa-cog w-5"></i>
                        <span>Configurações</span>
                    </a>
                </div>
            <?php else: ?>
                <div class="space-y-1">
                    <a href="/seller/dashboard" class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-lg text-sm <?= strpos($currentPath, '/seller/dashboard') !== false ? 'active' : 'text-slate-300' ?>">
                        <i class="fas fa-chart-line w-5"></i>
                        <span>Dashboard</span>
                    </a>

                    <a href="/seller/transactions" class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-lg text-sm <?= strpos($currentPath, '/seller/transactions') !== false ? 'active' : 'text-slate-300' ?>">
                        <i class="fas fa-exchange-alt w-5"></i>
                        <span>Transações</span>
                    </a>

                    <a href="/seller/documents" class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-lg text-sm <?= strpos($currentPath, '/seller/documents') !== false ? 'active' : 'text-slate-300' ?>">
                        <i class="fas fa-file-alt w-5"></i>
                        <span>Documentos</span>
                    </a>

                    <a href="/seller/api-credentials" class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-lg text-sm <?= strpos($currentPath, '/seller/api-credentials') !== false ? 'active' : 'text-slate-300' ?>">
                        <i class="fas fa-key w-5"></i>
                        <span>Credenciais API</span>
                    </a>

                    <a href="/seller/notifications" class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-lg text-sm <?= strpos($currentPath, '/seller/notifications') !== false ? 'active' : 'text-slate-300' ?>">
                        <i class="fas fa-bell w-5"></i>
                        <span>Notificações</span>
                    </a>

                    <div class="border-t border-slate-700 my-4"></div>

                    <a href="/seller/webhooks" class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-lg text-sm <?= strpos($currentPath, '/seller/webhooks') !== false ? 'active' : 'text-slate-300' ?>">
                        <i class="fas fa-satellite-dish w-5"></i>
                        <span>Webhooks</span>
                    </a>

                    <a href="/seller/profile" class="sidebar-link flex items-center space-x-3 px-4 py-3 rounded-lg text-sm <?= strpos($currentPath, '/seller/profile') !== false ? 'active' : 'text-slate-300' ?>">
                        <i class="fas fa-user w-5"></i>
                        <span>Perfil</span>
                    </a>
                </div>
            <?php endif; ?>
        </nav>

        <!-- User Profile -->
        <div class="p-4 border-t border-slate-700">
            <div class="flex items-center space-x-3 mb-3">
                <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-blue-600 rounded-full flex items-center justify-center text-white text-sm font-semibold">
                    <?= strtoupper(substr($user['name'], 0, 2)) ?>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="text-sm font-medium text-white truncate"><?= htmlspecialchars($user['name']) ?></div>
                    <div class="text-xs text-slate-400"><?= $user['role'] === 'admin' ? 'Administrador' : 'Vendedor' ?></div>
                </div>
            </div>
            <a href="/logout" class="flex items-center justify-center space-x-2 w-full px-4 py-2 bg-red-500 bg-opacity-10 hover:bg-opacity-20 text-red-400 rounded-lg text-sm transition">
                <i class="fas fa-sign-out-alt"></i>
                <span>Sair</span>
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="ml-64 min-h-screen">
        <!-- Top Bar -->
        <div class="bg-slate-800 bg-opacity-50 backdrop-blur-sm border-b border-slate-700 sticky top-0 z-40">
            <div class="px-8 py-4 flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-white"><?= htmlspecialchars($pageTitle) ?></h1>
                </div>
                <div class="flex items-center space-x-4">
                    <button class="relative p-2 text-slate-400 hover:text-white transition">
                        <i class="fas fa-bell text-lg"></i>
                        <span class="absolute top-1 right-1 w-2 h-2 bg-red-500 rounded-full"></span>
                    </button>
                    <div class="text-sm text-slate-400">
                        <i class="fas fa-clock mr-1"></i>
                        <span id="current-time"></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Alerts -->
        <?php if (isset($_SESSION['success'])): ?>
        <div class="px-8 mt-6">
            <div class="alert alert-success fade-in flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <i class="fas fa-check-circle text-xl"></i>
                    <span><?= htmlspecialchars($_SESSION['success']) ?></span>
                </div>
                <button onclick="this.parentElement.parentElement.remove()" class="text-green-400 hover:text-green-300">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
        <?php unset($_SESSION['success']); endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
        <div class="px-8 mt-6">
            <div class="alert alert-error fade-in flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <i class="fas fa-exclamation-circle text-xl"></i>
                    <span><?= htmlspecialchars($_SESSION['error']) ?></span>
                </div>
                <button onclick="this.parentElement.parentElement.remove()" class="text-red-400 hover:text-red-300">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
        <?php unset($_SESSION['error']); endif; ?>

        <!-- Page Content -->
        <div class="p-8">
            <script>
                function updateTime() {
                    const now = new Date();
                    const timeString = now.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
                    document.getElementById('current-time').textContent = timeString;
                }
                updateTime();
                setInterval(updateTime, 60000);
            </script>
