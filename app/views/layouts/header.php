<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pageTitle = $pageTitle ?? 'Gateway PIX';
$user = CheckAuth::user();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> - Gateway PIX</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Inter', system-ui, -apple-system, sans-serif; }
        .fade-in { animation: fadeIn 0.3s ease-in; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
        .hover-lift { transition: all 0.2s ease; }
        .hover-lift:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <nav class="bg-white border-b border-gray-200 sticky top-0 z-50 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="<?= $user && $user['role'] === 'admin' ? '/admin/dashboard' : '/seller/dashboard' ?>" class="flex items-center space-x-2">
                        <div class="w-8 h-8 bg-gradient-to-br from-blue-600 to-blue-400 rounded-lg flex items-center justify-center">
                            <i class="fas fa-bolt text-white text-sm"></i>
                        </div>
                        <span class="text-xl font-bold bg-gradient-to-r from-blue-600 to-blue-400 bg-clip-text text-transparent">Gateway PIX</span>
                    </a>
                </div>

                <?php if ($user): ?>
                <div class="flex items-center space-x-4">
                    <?php if ($user['role'] === 'admin'): ?>
                        <a href="/admin/dashboard" class="text-gray-600 hover:text-gray-900 px-3 py-2 rounded-lg text-sm font-medium transition">Dashboard</a>
                        <a href="/admin/sellers" class="text-gray-600 hover:text-gray-900 px-3 py-2 rounded-lg text-sm font-medium transition">Sellers</a>
                        <a href="/admin/transactions" class="text-gray-600 hover:text-gray-900 px-3 py-2 rounded-lg text-sm font-medium transition">Transações</a>
                        <a href="/admin/acquirers" class="text-gray-600 hover:text-gray-900 px-3 py-2 rounded-lg text-sm font-medium transition">Adquirentes</a>
                        <a href="/admin/documents" class="text-gray-600 hover:text-gray-900 px-3 py-2 rounded-lg text-sm font-medium transition">Documentos</a>
                        <a href="/admin/logs" class="text-gray-600 hover:text-gray-900 px-3 py-2 rounded-lg text-sm font-medium transition">Logs</a>
                    <?php else: ?>
                        <a href="/seller/dashboard" class="text-gray-600 hover:text-gray-900 px-3 py-2 rounded-lg text-sm font-medium transition">Dashboard</a>
                        <a href="/seller/transactions" class="text-gray-600 hover:text-gray-900 px-3 py-2 rounded-lg text-sm font-medium transition">Transações</a>
                        <a href="/seller/documents" class="text-gray-600 hover:text-gray-900 px-3 py-2 rounded-lg text-sm font-medium transition">Documentos</a>
                        <a href="/seller/api-credentials" class="text-gray-600 hover:text-gray-900 px-3 py-2 rounded-lg text-sm font-medium transition">API</a>
                    <?php endif; ?>

                    <div class="relative group">
                        <button class="flex items-center space-x-2 text-gray-700 hover:text-gray-900 px-3 py-2 rounded-lg transition">
                            <div class="w-8 h-8 bg-gradient-to-br from-blue-500 to-blue-600 rounded-full flex items-center justify-center text-white text-sm font-medium">
                                <?= strtoupper(substr($user['name'], 0, 1)) ?>
                            </div>
                            <span class="text-sm font-medium"><?= htmlspecialchars($user['name']) ?></span>
                            <i class="fas fa-chevron-down text-xs"></i>
                        </button>
                        <div class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200">
                            <a href="<?= $user['role'] === 'admin' ? '/admin/profile' : '/seller/profile' ?>" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded-t-lg">
                                <i class="fas fa-user mr-2"></i>Perfil
                            </a>
                            <a href="/logout" class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50 rounded-b-lg">
                                <i class="fas fa-sign-out-alt mr-2"></i>Sair
                            </a>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <?php if (isset($_SESSION['success'])): ?>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
        <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg fade-in flex items-center justify-between">
            <div class="flex items-center">
                <i class="fas fa-check-circle mr-2"></i>
                <span><?= htmlspecialchars($_SESSION['success']) ?></span>
            </div>
            <button onclick="this.parentElement.remove()" class="text-green-600 hover:text-green-800">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>
    <?php unset($_SESSION['success']); endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
        <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg fade-in flex items-center justify-between">
            <div class="flex items-center">
                <i class="fas fa-exclamation-circle mr-2"></i>
                <span><?= htmlspecialchars($_SESSION['error']) ?></span>
            </div>
            <button onclick="this.parentElement.remove()" class="text-red-600 hover:text-red-800">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>
    <?php unset($_SESSION['error']); endif; ?>
