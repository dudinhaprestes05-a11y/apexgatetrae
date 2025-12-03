<?php
$pageTitle = 'Credenciais da API';
require_once __DIR__ . '/../layouts/header.php';

$newApiSecret = $_SESSION['new_api_secret'] ?? null;
unset($_SESSION['new_api_secret']);
?>

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Credenciais da API</h1>
        <p class="text-gray-600 mt-2">Use essas credenciais para integrar com nossa API</p>
    </div>

    <?php if ($newApiSecret): ?>
    <div class="mb-6 bg-yellow-50 border border-yellow-200 rounded-lg p-4">
        <div class="flex items-start">
            <i class="fas fa-exclamation-triangle text-yellow-600 text-xl mr-3 mt-1"></i>
            <div class="flex-1">
                <h3 class="font-semibold text-yellow-900">Atenção! Salve suas novas credenciais</h3>
                <p class="text-yellow-700 text-sm mt-1">Copie e guarde em local seguro, pois não serão mostradas novamente!</p>

                <div class="mt-3">
                    <label class="block text-xs font-medium text-yellow-800 mb-1">Novo API Secret:</label>
                    <div class="p-3 bg-white rounded border border-yellow-300 flex items-center justify-between">
                        <code class="text-sm text-gray-900 break-all"><?= htmlspecialchars($newApiSecret) ?></code>
                        <button onclick="copyToClipboard('<?= htmlspecialchars($newApiSecret) ?>', this)"
                                class="ml-2 px-3 py-1 bg-yellow-600 text-white rounded hover:bg-yellow-700 text-xs">
                            <i class="fas fa-copy"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
        <h2 class="text-lg font-bold text-gray-900 mb-4">Suas Credenciais</h2>

        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">API Key</label>
                <div class="flex items-center space-x-2">
                    <input type="text" readonly
                           value="<?= htmlspecialchars($seller['api_key']) ?>"
                           class="flex-1 px-4 py-3 bg-gray-50 border border-gray-300 rounded-lg text-gray-900 font-mono text-sm">
                    <button onclick="copyToClipboard('<?= htmlspecialchars($seller['api_key']) ?>', this)"
                            class="px-4 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                        <i class="fas fa-copy"></i>
                    </button>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">API Secret</label>
                <div class="flex items-center space-x-2">
                    <input type="password" readonly
                           value="••••••••••••••••••••••••••••••••"
                           class="flex-1 px-4 py-3 bg-gray-50 border border-gray-300 rounded-lg text-gray-900">
                    <span class="text-sm text-gray-500">Oculto por segurança</span>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
        <h2 class="text-lg font-bold text-gray-900 mb-4">Regenerar API Secret</h2>
        <p class="text-gray-600 text-sm mb-4">
            Se seu API Secret foi comprometido, você pode gerar um novo. O API Key permanecerá o mesmo, mas o secret antigo será invalidado imediatamente.
        </p>
        <form method="POST" action="/seller/api-credentials/regenerate" onsubmit="return confirm('Tem certeza? O API Secret antigo será invalidado!')">
            <button type="submit" class="bg-red-600 text-white px-6 py-3 rounded-lg font-medium hover:bg-red-700 transition">
                <i class="fas fa-sync-alt mr-2"></i>Regenerar API Secret
            </button>
        </form>
    </div>

    <div class="bg-blue-50 border border-blue-200 rounded-xl p-6">
        <h2 class="text-lg font-bold text-blue-900 mb-4">
            <i class="fas fa-book mr-2"></i>Documentação da API
        </h2>
        <p class="text-blue-800 text-sm mb-4">
            Consulte nossa documentação completa para integrar com a API de pagamentos PIX.
        </p>
        <div class="space-y-2">
            <a href="/docs/api" target="_blank" class="inline-block text-blue-700 hover:text-blue-900 font-medium text-sm">
                <i class="fas fa-external-link-alt mr-1"></i>Documentação Completa
            </a>
            <br>
            <a href="/docs/api-examples" target="_blank" class="inline-block text-blue-700 hover:text-blue-900 font-medium text-sm">
                <i class="fas fa-code mr-1"></i>Exemplos de Código
            </a>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mt-6">
        <h2 class="text-lg font-bold text-gray-900 mb-4">Exemplos de Uso</h2>

        <h3 class="font-semibold text-gray-800 mb-2">Método 1: Basic Authentication (Recomendado)</h3>
        <div class="bg-gray-900 rounded-lg p-4 overflow-x-auto mb-4">
            <pre class="text-green-400 text-sm"><code>curl -X POST <?= BASE_URL ?>/api/pix/create \
  -u "<?= htmlspecialchars($seller['api_key']) ?>:SEU_API_SECRET" \
  -H "Content-Type: application/json" \
  -d '{
    "amount": 100.00,
    "external_id": "ORDER-123",
    "customer": {
      "name": "João Silva",
      "document": "12345678900"
    }
  }'</code></pre>
        </div>

        <h3 class="font-semibold text-gray-800 mb-2">Método 2: Headers Personalizados</h3>
        <div class="bg-gray-900 rounded-lg p-4 overflow-x-auto">
            <pre class="text-green-400 text-sm"><code>curl -X POST <?= BASE_URL ?>/api/pix/create \
  -H "X-API-Key: <?= htmlspecialchars($seller['api_key']) ?>" \
  -H "X-API-Secret: SEU_API_SECRET" \
  -H "Content-Type: application/json" \
  -d '{
    "amount": 100.00,
    "external_id": "ORDER-123",
    "customer": {
      "name": "João Silva",
      "document": "12345678900"
    }
  }'</code></pre>
        </div>

        <div class="mt-4 p-3 bg-blue-50 border border-blue-200 rounded">
            <p class="text-sm text-blue-800">
                <i class="fas fa-info-circle mr-1"></i>
                <strong>Importante:</strong> Substitua "SEU_API_SECRET" pelo secret mostrado após regenerar as credenciais.
            </p>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
