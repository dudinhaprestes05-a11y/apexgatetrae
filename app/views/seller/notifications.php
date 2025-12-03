<?php
$pageTitle = 'Notificações';
require_once __DIR__ . '/../layouts/header.php';
?>

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Notificações</h1>
        <p class="text-gray-600 mt-2">Todas as suas notificações em um só lugar</p>
    </div>

    <div class="space-y-4">
        <?php if (empty($notifications)): ?>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-12 text-center">
            <i class="fas fa-bell-slash text-gray-400 text-5xl mb-4"></i>
            <p class="text-gray-500">Nenhuma notificação ainda</p>
        </div>
        <?php else: ?>
        <?php foreach ($notifications as $notif): ?>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 <?= $notif['is_read'] ? 'opacity-75' : '' ?> hover-lift">
            <div class="flex items-start space-x-4">
                <div class="flex-shrink-0">
                    <div class="w-12 h-12 rounded-full flex items-center justify-center <?php
                    echo match($notif['type']) {
                        'success', 'document_approved', 'account_approved' => 'bg-green-100',
                        'error', 'document_rejected', 'account_rejected' => 'bg-red-100',
                        'warning' => 'bg-yellow-100',
                        default => 'bg-blue-100'
                    };
                    ?>">
                        <i class="fas <?php
                        echo match($notif['type']) {
                            'success', 'document_approved', 'account_approved' => 'fa-check-circle text-green-600',
                            'error', 'document_rejected', 'account_rejected' => 'fa-exclamation-circle text-red-600',
                            'warning' => 'fa-exclamation-triangle text-yellow-600',
                            default => 'fa-info-circle text-blue-600'
                        };
                        ?> text-xl"></i>
                    </div>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <h3 class="text-lg font-semibold text-gray-900"><?= htmlspecialchars($notif['title']) ?></h3>
                            <p class="text-gray-600 mt-1"><?= htmlspecialchars($notif['message']) ?></p>
                            <p class="text-xs text-gray-500 mt-2">
                                <i class="fas fa-clock mr-1"></i>
                                <?= date('d/m/Y H:i', strtotime($notif['created_at'])) ?>
                            </p>
                        </div>
                        <?php if (!$notif['is_read']): ?>
                        <form method="POST" action="/seller/notifications/<?= $notif['id'] ?>/read" class="ml-4">
                            <button type="submit" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                                Marcar como lida
                            </button>
                        </form>
                        <?php endif; ?>
                    </div>
                    <?php if ($notif['link']): ?>
                    <a href="<?= htmlspecialchars($notif['link']) ?>" class="inline-block mt-3 text-blue-600 hover:text-blue-800 text-sm font-medium">
                        Ver mais <i class="fas fa-arrow-right ml-1"></i>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
