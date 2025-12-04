<?php
$pageTitle = 'Configurações de Webhook';
require_once __DIR__ . '/../layouts/header.php';
?>

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Configurações de Webhook</h1>
        <p class="text-gray-600 mt-2">Configure notificações automáticas sobre suas transações</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h2 class="text-lg font-bold text-gray-900 mb-6">Configurações</h2>
                <form method="POST" action="/seller/webhooks/update" class="space-y-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">URL do Webhook</label>
                        <input type="url" name="webhook_url"
                               value="<?= htmlspecialchars($seller['webhook_url'] ?? '') ?>"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="https://seu-site.com/webhook">
                        <p class="text-xs text-gray-500 mt-2">URL que receberá notificações sobre transações via POST</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Webhook Secret</label>
                        <input type="text" name="webhook_secret"
                               value="<?= htmlspecialchars($seller['webhook_secret'] ?? '') ?>"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="sua_chave_secreta">
                        <p class="text-xs text-gray-500 mt-2">Chave secreta enviada no header X-Webhook-Secret para validar a autenticidade</p>
                    </div>

                    <div class="flex items-center justify-end space-x-3 pt-4 border-t border-gray-200">
                        <button type="submit" class="px-6 py-3 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 transition">
                            <i class="fas fa-save mr-2"></i>Salvar Configurações
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="space-y-6">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="font-bold text-gray-900 mb-4 flex items-center">
                    <i class="fas fa-info-circle text-blue-600 mr-2"></i>
                    Como Funciona
                </h3>
                <div class="text-sm text-gray-600 space-y-3">
                    <p>Os webhooks são notificações automáticas enviadas para sua aplicação quando eventos ocorrem.</p>
                    <p><strong>Entrega Garantida:</strong> Tentamos enviar imediatamente. Se falhar, nosso sistema retenta automaticamente com intervalos crescentes.</p>
                    <p>Eventos notificados:</p>
                    <ul class="list-disc list-inside space-y-1 ml-2">
                        <li>Pagamento aprovado</li>
                        <li>Pagamento cancelado</li>
                        <li>Saque processado</li>
                        <li>Transação expirada</li>
                    </ul>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="font-bold text-gray-900 mb-4 flex items-center">
                    <i class="fas fa-shield-alt text-green-600 mr-2"></i>
                    Segurança
                </h3>
                <div class="text-sm text-gray-600 space-y-3">
                    <p>Para validar que a requisição veio de nós, verifique o header <code class="px-2 py-1 bg-gray-100 rounded text-xs font-mono">X-Webhook-Secret</code>.</p>
                    <p>Compare o valor recebido neste header com o webhook secret que você configurou. Se forem iguais, a requisição é autêntica.</p>
                </div>
            </div>

            <div class="bg-blue-50 border border-blue-200 rounded-xl p-4">
                <div class="flex items-start">
                    <i class="fas fa-lightbulb text-blue-600 mt-1 mr-3"></i>
                    <div class="text-sm text-blue-800">
                        <p class="font-medium mb-1">Dica</p>
                        <p>Teste sua integração usando ferramentas como webhook.site ou requestbin.com</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-8 bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <h3 class="font-bold text-gray-900 mb-4">Exemplo de Payload</h3>
        <pre class="bg-gray-900 text-gray-100 p-4 rounded-lg overflow-x-auto text-xs"><code>{
  "event": "payment.approved",
  "transaction_id": "txn_123456789",
  "amount": 100.00,
  "status": "approved",
  "customer": {
    "name": "João Silva",
    "document": "12345678901",
    "email": "joao@exemplo.com"
  },
  "payer": {
    "name": "João Silva",
    "document": "12345678901"
  },
  "created_at": "2024-01-15T10:30:00Z",
  "paid_at": "2024-01-15T10:32:15Z"
}</code></pre>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
