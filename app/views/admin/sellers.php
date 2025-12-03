<?php
$pageTitle = 'Sellers';
require_once __DIR__ . '/../layouts/header.php';
$status = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Sellers</h1>
        <p class="text-gray-600 mt-2">Gerencie todos os sellers do sistema</p>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
        <form method="GET" action="/admin/sellers" class="flex items-end gap-4">
            <div class="flex-1">
                <label class="block text-sm font-medium text-gray-700 mb-2">Buscar</label>
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>"
                       placeholder="Nome, email ou documento..."
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="flex-1">
                <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                <select name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    <option value="">Todos</option>
                    <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>Pendente</option>
                    <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Ativo</option>
                    <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Inativo</option>
                    <option value="blocked" <?= $status === 'blocked' ? 'selected' : '' ?>>Bloqueado</option>
                    <option value="rejected" <?= $status === 'rejected' ? 'selected' : '' ?>>Rejeitado</option>
                </select>
            </div>
            <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                <i class="fas fa-search mr-2"></i>Buscar
            </button>
        </form>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nome</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Documento</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Saldo</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cadastro</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ações</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (empty($sellers)): ?>
                <tr>
                    <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                        <i class="fas fa-users-slash text-4xl mb-3 block"></i>
                        Nenhum seller encontrado
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($sellers as $seller): ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                        #<?= $seller['id'] ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($seller['name']) ?></div>
                        <?php if ($seller['person_type'] === 'business' && $seller['company_name']): ?>
                        <div class="text-xs text-gray-500"><?= htmlspecialchars($seller['company_name']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                        <?= htmlspecialchars($seller['email']) ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-mono text-gray-700">
                        <?= htmlspecialchars($seller['document']) ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 py-1 text-xs font-medium rounded-full
                            <?php
                            echo match($seller['status']) {
                                'active' => 'bg-green-100 text-green-800',
                                'pending' => 'bg-yellow-100 text-yellow-800',
                                'inactive' => 'bg-gray-100 text-gray-800',
                                'blocked' => 'bg-red-100 text-red-800',
                                'rejected' => 'bg-red-100 text-red-800',
                                default => 'bg-gray-100 text-gray-800'
                            };
                            ?>">
                            <?= ucfirst($seller['status']) ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                        R$ <?= number_format($seller['balance'], 2, ',', '.') ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                        <?= date('d/m/Y', strtotime($seller['created_at'])) ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                        <a href="/admin/sellers/view/<?= $seller['id'] ?>" class="text-blue-600 hover:text-blue-800 font-medium">
                            Ver detalhes
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
