<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Documentação da API - Gateway PIX</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            font-family: 'Inter', sans-serif;
        }
        body {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
        }
        .code-block {
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 0.5rem;
            padding: 1rem;
            overflow-x: auto;
        }
        .code-block pre {
            margin: 0;
            color: #e2e8f0;
            font-family: 'Monaco', 'Menlo', monospace;
            font-size: 0.875rem;
            line-height: 1.5;
        }
        .endpoint-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 0.375rem;
            font-weight: 600;
            font-size: 0.875rem;
        }
        .method-post {
            background: #10b981;
            color: white;
        }
        .method-get {
            background: #3b82f6;
            color: white;
        }
        .sidebar {
            position: sticky;
            top: 2rem;
            max-height: calc(100vh - 4rem);
            overflow-y: auto;
        }
        .sidebar::-webkit-scrollbar {
            width: 6px;
        }
        .sidebar::-webkit-scrollbar-thumb {
            background: #475569;
            border-radius: 3px;
        }
        .nav-link {
            color: #cbd5e1;
            transition: all 0.2s;
        }
        .nav-link:hover {
            color: #3b82f6;
            padding-left: 0.5rem;
        }
        .response-success {
            border-left: 4px solid #10b981;
        }
        .response-error {
            border-left: 4px solid #ef4444;
        }
        .copy-btn {
            position: absolute;
            top: 0.5rem;
            right: 0.5rem;
            background: #334155;
            color: #cbd5e1;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            cursor: pointer;
            font-size: 0.875rem;
            transition: all 0.2s;
        }
        .copy-btn:hover {
            background: #475569;
        }
        .section {
            scroll-margin-top: 2rem;
        }
    </style>
</head>
<body class="text-slate-200">
    <div class="container mx-auto px-4 py-8">
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
            <!-- Sidebar -->
            <div class="lg:col-span-1">
                <div class="sidebar bg-slate-800 rounded-xl p-6 border border-slate-700">
                    <h2 class="text-xl font-bold text-white mb-4">Navegação</h2>
                    <nav class="space-y-2">
                        <a href="#introducao" class="nav-link block py-2 text-sm">Introdução</a>
                        <a href="#autenticacao" class="nav-link block py-2 text-sm">Autenticação</a>
                        <a href="#ambiente" class="nav-link block py-2 text-sm">Ambiente</a>
                        <a href="#cash-in" class="nav-link block py-2 text-sm font-semibold">PIX Cash-In</a>
                        <a href="#cash-in-criar" class="nav-link block py-2 text-sm pl-4">• Criar Cobrança</a>
                        <a href="#cash-in-consultar" class="nav-link block py-2 text-sm pl-4">• Consultar Status</a>
                        <a href="#cash-out" class="nav-link block py-2 text-sm font-semibold">PIX Cash-Out</a>
                        <a href="#cash-out-criar" class="nav-link block py-2 text-sm pl-4">• Solicitar Saque</a>
                        <a href="#cash-out-consultar" class="nav-link block py-2 text-sm pl-4">• Consultar Saque</a>
                        <a href="#webhooks" class="nav-link block py-2 text-sm font-semibold">Webhooks</a>
                        <a href="#webhook-config" class="nav-link block py-2 text-sm pl-4">• Configuração</a>
                        <a href="#webhook-eventos" class="nav-link block py-2 text-sm pl-4">• Eventos</a>
                        <a href="#codigos-erro" class="nav-link block py-2 text-sm">Códigos de Erro</a>
                    </nav>
                </div>
            </div>

            <!-- Main Content -->
            <div class="lg:col-span-3 space-y-8">
                <!-- Header -->
                <div class="bg-gradient-to-r from-blue-600 to-blue-700 rounded-xl p-8 shadow-xl">
                    <h1 class="text-4xl font-bold text-white mb-2">Documentação da API</h1>
                    <p class="text-blue-100 text-lg">Gateway de Pagamentos PIX - Versão 1.0</p>
                </div>

                <!-- Introdução -->
                <div id="introducao" class="section bg-slate-800 rounded-xl p-6 border border-slate-700">
                    <h2 class="text-2xl font-bold text-white mb-4 flex items-center">
                        <i class="fas fa-book text-blue-500 mr-3"></i>
                        Introdução
                    </h2>
                    <p class="text-slate-300 mb-4">
                        Bem-vindo à documentação da API do Gateway PIX. Nossa API permite que você integre pagamentos PIX em sua aplicação de forma simples e segura.
                    </p>
                    <div class="bg-blue-900 bg-opacity-30 border border-blue-700 rounded-lg p-4">
                        <h3 class="font-semibold text-blue-300 mb-2">Recursos Disponíveis</h3>
                        <ul class="space-y-2 text-slate-300 text-sm">
                            <li><i class="fas fa-check text-green-400 mr-2"></i>Criação de cobranças PIX com QR Code</li>
                            <li><i class="fas fa-check text-green-400 mr-2"></i>Consulta de status de transações</li>
                            <li><i class="fas fa-check text-green-400 mr-2"></i>Solicitação de saques (Cash-Out)</li>
                            <li><i class="fas fa-check text-green-400 mr-2"></i>Webhooks para notificações em tempo real</li>
                            <li><i class="fas fa-check text-green-400 mr-2"></i>Segurança com autenticação via API Key</li>
                        </ul>
                    </div>
                </div>

                <!-- Autenticação -->
                <div id="autenticacao" class="section bg-slate-800 rounded-xl p-6 border border-slate-700">
                    <h2 class="text-2xl font-bold text-white mb-4 flex items-center">
                        <i class="fas fa-lock text-yellow-500 mr-3"></i>
                        Autenticação
                    </h2>
                    <p class="text-slate-300 mb-4">
                        Todas as requisições à API devem incluir suas credenciais no cabeçalho. Você pode obter suas credenciais no painel do seller.
                    </p>

                    <h3 class="text-lg font-semibold text-white mb-3">Headers Obrigatórios</h3>
                    <div class="code-block relative">
                        <button class="copy-btn" onclick="copyToClipboard(this)">
                            <i class="fas fa-copy"></i> Copiar
                        </button>
                        <pre>X-API-Key: sua_api_key_aqui
X-API-Secret: seu_api_secret_aqui
Content-Type: application/json</pre>
                    </div>

                    <div class="mt-4 bg-red-900 bg-opacity-30 border border-red-700 rounded-lg p-4">
                        <h4 class="font-semibold text-red-300 mb-2 flex items-center">
                            <i class="fas fa-exclamation-triangle mr-2"></i>
                            Importante
                        </h4>
                        <ul class="space-y-1 text-slate-300 text-sm">
                            <li>• Mantenha suas credenciais em segredo</li>
                            <li>• Nunca exponha suas chaves em repositórios públicos</li>
                            <li>• Configure a whitelist de IPs para maior segurança</li>
                        </ul>
                    </div>
                </div>

                <!-- Ambiente -->
                <div id="ambiente" class="section bg-slate-800 rounded-xl p-6 border border-slate-700">
                    <h2 class="text-2xl font-bold text-white mb-4 flex items-center">
                        <i class="fas fa-server text-green-500 mr-3"></i>
                        Ambiente e Base URL
                    </h2>
                    <p class="text-slate-300 mb-4">
                        A API está disponível no seguinte endereço:
                    </p>

                    <div class="code-block">
                        <pre>Base URL: <?= (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] ?>/api</pre>
                    </div>
                </div>

                <!-- PIX Cash-In -->
                <div id="cash-in" class="section bg-slate-800 rounded-xl p-6 border border-slate-700">
                    <h2 class="text-2xl font-bold text-white mb-4 flex items-center">
                        <i class="fas fa-qrcode text-green-500 mr-3"></i>
                        PIX Cash-In (Receber)
                    </h2>
                    <p class="text-slate-300 mb-6">
                        Use este endpoint para criar cobranças PIX e receber pagamentos.
                    </p>

                    <!-- Criar Cobrança -->
                    <div id="cash-in-criar" class="mb-8">
                        <h3 class="text-xl font-semibold text-white mb-3">Criar Cobrança PIX</h3>
                        <div class="mb-4">
                            <span class="endpoint-badge method-post">POST</span>
                            <code class="ml-3 text-blue-400">/api/pix/create</code>
                        </div>

                        <h4 class="font-semibold text-white mb-2">Parâmetros da Requisição</h4>
                        <div class="overflow-x-auto mb-4">
                            <table class="w-full text-sm">
                                <thead class="bg-slate-900">
                                    <tr>
                                        <th class="text-left p-3 text-slate-300">Campo</th>
                                        <th class="text-left p-3 text-slate-300">Tipo</th>
                                        <th class="text-left p-3 text-slate-300">Obrigatório</th>
                                        <th class="text-left p-3 text-slate-300">Descrição</th>
                                    </tr>
                                </thead>
                                <tbody class="text-slate-400">
                                    <tr class="border-t border-slate-700">
                                        <td class="p-3"><code class="text-blue-400">amount</code></td>
                                        <td class="p-3">decimal</td>
                                        <td class="p-3"><span class="text-green-400">Sim</span></td>
                                        <td class="p-3">Valor da cobrança (ex: 100.00)</td>
                                    </tr>
                                    <tr class="border-t border-slate-700">
                                        <td class="p-3"><code class="text-blue-400">customer</code></td>
                                        <td class="p-3">object</td>
                                        <td class="p-3"><span class="text-slate-500">Não</span></td>
                                        <td class="p-3">Dados do cliente (name, document, email)</td>
                                    </tr>
                                    <tr class="border-t border-slate-700">
                                        <td class="p-3"><code class="text-blue-400">external_id</code></td>
                                        <td class="p-3">string</td>
                                        <td class="p-3"><span class="text-slate-500">Não</span></td>
                                        <td class="p-3">ID externo para referência (único)</td>
                                    </tr>
                                    <tr class="border-t border-slate-700">
                                        <td class="p-3"><code class="text-blue-400">metadata</code></td>
                                        <td class="p-3">object</td>
                                        <td class="p-3"><span class="text-slate-500">Não</span></td>
                                        <td class="p-3">Dados adicionais personalizados</td>
                                    </tr>
                                    <tr class="border-t border-slate-700">
                                        <td class="p-3"><code class="text-blue-400">expires_in_minutes</code></td>
                                        <td class="p-3">integer</td>
                                        <td class="p-3"><span class="text-slate-500">Não</span></td>
                                        <td class="p-3">Tempo de expiração em minutos (padrão: 60)</td>
                                    </tr>
                                    <tr class="border-t border-slate-700">
                                        <td class="p-3"><code class="text-blue-400">pix_type</code></td>
                                        <td class="p-3">string</td>
                                        <td class="p-3"><span class="text-slate-500">Não</span></td>
                                        <td class="p-3">Tipo do PIX (padrão: dynamic)</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <h4 class="font-semibold text-white mb-2">Exemplo de Requisição</h4>
                        <div class="code-block relative mb-4">
                            <button class="copy-btn" onclick="copyToClipboard(this)">
                                <i class="fas fa-copy"></i> Copiar
                            </button>
                            <pre>curl -X POST <?= (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] ?>/api/pix/create \
  -H "X-API-Key: sua_api_key" \
  -H "X-API-Secret: seu_api_secret" \
  -H "Content-Type: application/json" \
  -d '{
    "amount": 150.00,
    "external_id": "pedido-12345",
    "customer": {
      "name": "João Silva",
      "document": "12345678900",
      "email": "joao@email.com"
    },
    "metadata": {
      "order_id": "12345",
      "product": "Plano Premium"
    }
  }'</pre>
                        </div>

                        <h4 class="font-semibold text-white mb-2">Resposta de Sucesso (200 OK)</h4>
                        <div class="code-block response-success relative">
                            <button class="copy-btn" onclick="copyToClipboard(this)">
                                <i class="fas fa-copy"></i> Copiar
                            </button>
                            <pre>{
  "success": true,
  "message": "PIX transaction created successfully",
  "data": {
    "transaction_id": "CASHIN-20231204-ABC123",
    "amount": 150.00,
    "fee_amount": 3.75,
    "net_amount": 146.25,
    "status": "pending",
    "qrcode": "00020101021126580014br.gov.bcb.pix...",
    "qrcode_base64": "data:image/png;base64,iVBORw0KGgo...",
    "expires_at": "2023-12-04T18:30:00",
    "external_id": "pedido-12345"
  }
}</pre>
                        </div>
                    </div>

                    <!-- Consultar Status -->
                    <div id="cash-in-consultar">
                        <h3 class="text-xl font-semibold text-white mb-3">Consultar Status da Cobrança</h3>
                        <div class="mb-4">
                            <span class="endpoint-badge method-get">GET</span>
                            <code class="ml-3 text-blue-400">/api/pix/consult?transaction_id={transaction_id}</code>
                        </div>

                        <h4 class="font-semibold text-white mb-2">Exemplo de Requisição</h4>
                        <div class="code-block relative mb-4">
                            <button class="copy-btn" onclick="copyToClipboard(this)">
                                <i class="fas fa-copy"></i> Copiar
                            </button>
                            <pre>curl -X GET "<?= (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] ?>/api/pix/consult?transaction_id=CASHIN-20231204-ABC123" \
  -H "X-API-Key: sua_api_key" \
  -H "X-API-Secret: seu_api_secret"</pre>
                        </div>

                        <h4 class="font-semibold text-white mb-2">Resposta de Sucesso (200 OK)</h4>
                        <div class="code-block response-success relative">
                            <button class="copy-btn" onclick="copyToClipboard(this)">
                                <i class="fas fa-copy"></i> Copiar
                            </button>
                            <pre>{
  "success": true,
  "data": {
    "transaction_id": "CASHIN-20231204-ABC123",
    "amount": 150.00,
    "fee_amount": 3.75,
    "net_amount": 146.25,
    "status": "completed",
    "qrcode": "00020101021126580014br.gov.bcb.pix...",
    "qrcode_base64": "data:image/png;base64,iVBORw0KGgo...",
    "paid_at": "2023-12-04 17:45:00",
    "expires_at": "2023-12-04 18:30:00",
    "created_at": "2023-12-04 17:30:00",
    "external_id": "pedido-12345"
  }
}</pre>
                        </div>

                        <h4 class="font-semibold text-white mt-4 mb-2">Status Possíveis</h4>
                        <div class="space-y-2">
                            <div class="bg-yellow-900 bg-opacity-20 border border-yellow-700 rounded p-2 text-sm">
                                <code class="text-yellow-400">pending</code> - Aguardando pagamento
                            </div>
                            <div class="bg-green-900 bg-opacity-20 border border-green-700 rounded p-2 text-sm">
                                <code class="text-green-400">completed</code> - Pagamento confirmado
                            </div>
                            <div class="bg-red-900 bg-opacity-20 border border-red-700 rounded p-2 text-sm">
                                <code class="text-red-400">expired</code> - Cobrança expirada
                            </div>
                            <div class="bg-red-900 bg-opacity-20 border border-red-700 rounded p-2 text-sm">
                                <code class="text-red-400">cancelled</code> - Cobrança cancelada
                            </div>
                        </div>
                    </div>
                </div>

                <!-- PIX Cash-Out -->
                <div id="cash-out" class="section bg-slate-800 rounded-xl p-6 border border-slate-700">
                    <h2 class="text-2xl font-bold text-white mb-4 flex items-center">
                        <i class="fas fa-money-bill-wave text-orange-500 mr-3"></i>
                        PIX Cash-Out (Saques)
                    </h2>
                    <p class="text-slate-300 mb-6">
                        Use este endpoint para solicitar saques via PIX.
                    </p>

                    <!-- Criar Saque -->
                    <div id="cash-out-criar" class="mb-8">
                        <h3 class="text-xl font-semibold text-white mb-3">Solicitar Saque</h3>
                        <div class="mb-4">
                            <span class="endpoint-badge method-post">POST</span>
                            <code class="ml-3 text-blue-400">/api/cashout/create</code>
                        </div>

                        <h4 class="font-semibold text-white mb-2">Parâmetros da Requisição</h4>
                        <div class="overflow-x-auto mb-4">
                            <table class="w-full text-sm">
                                <thead class="bg-slate-900">
                                    <tr>
                                        <th class="text-left p-3 text-slate-300">Campo</th>
                                        <th class="text-left p-3 text-slate-300">Tipo</th>
                                        <th class="text-left p-3 text-slate-300">Obrigatório</th>
                                        <th class="text-left p-3 text-slate-300">Descrição</th>
                                    </tr>
                                </thead>
                                <tbody class="text-slate-400">
                                    <tr class="border-t border-slate-700">
                                        <td class="p-3"><code class="text-blue-400">amount</code></td>
                                        <td class="p-3">decimal</td>
                                        <td class="p-3"><span class="text-green-400">Sim</span></td>
                                        <td class="p-3">Valor do saque (ex: 100.00)</td>
                                    </tr>
                                    <tr class="border-t border-slate-700">
                                        <td class="p-3"><code class="text-blue-400">pix_key</code></td>
                                        <td class="p-3">string</td>
                                        <td class="p-3"><span class="text-green-400">Sim</span></td>
                                        <td class="p-3">Chave PIX de destino</td>
                                    </tr>
                                    <tr class="border-t border-slate-700">
                                        <td class="p-3"><code class="text-blue-400">pix_key_type</code></td>
                                        <td class="p-3">string</td>
                                        <td class="p-3"><span class="text-green-400">Sim</span></td>
                                        <td class="p-3">Tipo: cpf, cnpj, email, phone, random</td>
                                    </tr>
                                    <tr class="border-t border-slate-700">
                                        <td class="p-3"><code class="text-blue-400">beneficiary_name</code></td>
                                        <td class="p-3">string</td>
                                        <td class="p-3"><span class="text-green-400">Sim</span></td>
                                        <td class="p-3">Nome do beneficiário</td>
                                    </tr>
                                    <tr class="border-t border-slate-700">
                                        <td class="p-3"><code class="text-blue-400">beneficiary_document</code></td>
                                        <td class="p-3">string</td>
                                        <td class="p-3"><span class="text-green-400">Sim</span></td>
                                        <td class="p-3">CPF/CNPJ do beneficiário</td>
                                    </tr>
                                    <tr class="border-t border-slate-700">
                                        <td class="p-3"><code class="text-blue-400">external_id</code></td>
                                        <td class="p-3">string</td>
                                        <td class="p-3"><span class="text-slate-500">Não</span></td>
                                        <td class="p-3">ID externo para referência (único)</td>
                                    </tr>
                                    <tr class="border-t border-slate-700">
                                        <td class="p-3"><code class="text-blue-400">metadata</code></td>
                                        <td class="p-3">object</td>
                                        <td class="p-3"><span class="text-slate-500">Não</span></td>
                                        <td class="p-3">Dados adicionais personalizados</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <h4 class="font-semibold text-white mb-2">Exemplo de Requisição</h4>
                        <div class="code-block relative mb-4">
                            <button class="copy-btn" onclick="copyToClipboard(this)">
                                <i class="fas fa-copy"></i> Copiar
                            </button>
                            <pre>curl -X POST <?= (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] ?>/api/cashout/create \
  -H "X-API-Key: sua_api_key" \
  -H "X-API-Secret: seu_api_secret" \
  -H "Content-Type: application/json" \
  -d '{
    "amount": 500.00,
    "pix_key": "joao.silva@email.com",
    "pix_key_type": "email",
    "beneficiary_name": "João Silva",
    "beneficiary_document": "12345678900"
  }'</pre>
                        </div>

                        <h4 class="font-semibold text-white mb-2">Resposta de Sucesso (200 OK)</h4>
                        <div class="code-block response-success relative">
                            <button class="copy-btn" onclick="copyToClipboard(this)">
                                <i class="fas fa-copy"></i> Copiar
                            </button>
                            <pre>{
  "success": true,
  "message": "Cashout transaction created successfully",
  "data": {
    "transaction_id": "CASHOUT-20231204-XYZ789",
    "amount": 500.00,
    "fee_amount": 5.00,
    "total_charged": 505.00,
    "status": "processing",
    "pix_key": "joao.silva@email.com",
    "beneficiary_name": "João Silva",
    "external_id": "saque-001"
  }
}</pre>
                        </div>
                    </div>

                    <!-- Consultar Saque -->
                    <div id="cash-out-consultar">
                        <h3 class="text-xl font-semibold text-white mb-3">Consultar Status do Saque</h3>
                        <div class="mb-4">
                            <span class="endpoint-badge method-get">GET</span>
                            <code class="ml-3 text-blue-400">/api/cashout/consult?transaction_id={transaction_id}</code>
                        </div>

                        <h4 class="font-semibold text-white mb-2">Exemplo de Requisição</h4>
                        <div class="code-block relative mb-4">
                            <button class="copy-btn" onclick="copyToClipboard(this)">
                                <i class="fas fa-copy"></i> Copiar
                            </button>
                            <pre>curl -X GET "<?= (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] ?>/api/cashout/consult?transaction_id=CASHOUT-20231204-XYZ789" \
  -H "X-API-Key: sua_api_key" \
  -H "X-API-Secret: seu_api_secret"</pre>
                        </div>

                        <h4 class="font-semibold text-white mb-2">Resposta de Sucesso (200 OK)</h4>
                        <div class="code-block response-success relative">
                            <button class="copy-btn" onclick="copyToClipboard(this)">
                                <i class="fas fa-copy"></i> Copiar
                            </button>
                            <pre>{
  "success": true,
  "data": {
    "transaction_id": "CASHOUT-20231204-XYZ789",
    "amount": 500.00,
    "fee_amount": 5.00,
    "net_amount": 500.00,
    "status": "completed",
    "pix_key": "joao.silva@email.com",
    "beneficiary_name": "João Silva",
    "processed_at": "2023-12-04 18:05:00",
    "created_at": "2023-12-04 18:00:00",
    "external_id": "saque-001"
  }
}</pre>
                        </div>
                    </div>
                </div>

                <!-- Webhooks -->
                <div id="webhooks" class="section bg-slate-800 rounded-xl p-6 border border-slate-700">
                    <h2 class="text-2xl font-bold text-white mb-4 flex items-center">
                        <i class="fas fa-bell text-purple-500 mr-3"></i>
                        Webhooks
                    </h2>
                    <p class="text-slate-300 mb-6">
                        Webhooks permitem que você receba notificações automáticas sobre eventos nas suas transações.
                    </p>

                    <!-- Configuração -->
                    <div id="webhook-config" class="mb-8">
                        <h3 class="text-xl font-semibold text-white mb-3">Configuração</h3>
                        <p class="text-slate-300 mb-4">
                            Configure sua URL de webhook no painel do seller em <strong>Configurações → Webhooks</strong>.
                        </p>

                        <div class="bg-blue-900 bg-opacity-30 border border-blue-700 rounded-lg p-4 mb-4">
                            <h4 class="font-semibold text-blue-300 mb-2">Requisitos</h4>
                            <ul class="space-y-1 text-slate-300 text-sm">
                                <li>• A URL deve usar HTTPS</li>
                                <li>• O endpoint deve responder com status 200</li>
                                <li>• Resposta deve ser enviada em até 5 segundos</li>
                                <li>• O sistema tentará reenviar até 3 vezes em caso de falha</li>
                            </ul>
                        </div>
                    </div>

                    <!-- Eventos -->
                    <div id="webhook-eventos">
                        <h3 class="text-xl font-semibold text-white mb-3">Eventos de Webhook</h3>

                        <h4 class="font-semibold text-white mb-2 mt-4">Pagamento Recebido (Cash-In)</h4>
                        <div class="code-block relative mb-4">
                            <button class="copy-btn" onclick="copyToClipboard(this)">
                                <i class="fas fa-copy"></i> Copiar
                            </button>
                            <pre>{
  "type": "pix.cashin",
  "transaction_id": "CASHIN-20231204-ABC123",
  "external_id": "pedido-12345",
  "status": "completed",
  "amount": 150.00,
  "paid_at": "2023-12-04 17:45:00"
}</pre>
                        </div>

                        <h4 class="font-semibold text-white mb-2 mt-4">Cobrança Expirada (Cash-In)</h4>
                        <div class="code-block relative mb-4">
                            <button class="copy-btn" onclick="copyToClipboard(this)">
                                <i class="fas fa-copy"></i> Copiar
                            </button>
                            <pre>{
  "type": "pix.cashin",
  "transaction_id": "CASHIN-20231204-ABC123",
  "external_id": "pedido-12345",
  "status": "expired",
  "amount": 150.00,
  "paid_at": null
}</pre>
                        </div>

                        <h4 class="font-semibold text-white mb-2 mt-4">Saque Processado (Cash-Out)</h4>
                        <div class="code-block relative mb-4">
                            <button class="copy-btn" onclick="copyToClipboard(this)">
                                <i class="fas fa-copy"></i> Copiar
                            </button>
                            <pre>{
  "type": "pix.cashout",
  "transaction_id": "CASHOUT-20231204-XYZ789",
  "external_id": "saque-001",
  "status": "completed",
  "net_amount": 500.00,
  "fee": 5.00
}</pre>
                        </div>

                        <h4 class="font-semibold text-white mb-2 mt-4">Saque Falhou (Cash-Out)</h4>
                        <div class="code-block relative mb-4">
                            <button class="copy-btn" onclick="copyToClipboard(this)">
                                <i class="fas fa-copy"></i> Copiar
                            </button>
                            <pre>{
  "type": "pix.cashout",
  "transaction_id": "CASHOUT-20231204-XYZ789",
  "external_id": "saque-001",
  "status": "failed",
  "net_amount": 500.00,
  "fee": 5.00
}</pre>
                        </div>

                        <h4 class="font-semibold text-white mb-2 mt-4">Validando Webhooks</h4>
                        <p class="text-slate-300 mb-3 text-sm">
                            Recomendamos que você valide os webhooks consultando o status da transação via API para confirmar a autenticidade.
                        </p>
                        <div class="code-block relative">
                            <button class="copy-btn" onclick="copyToClipboard(this)">
                                <i class="fas fa-copy"></i> Copiar
                            </button>
                            <pre>// Exemplo em PHP
$payload = file_get_contents('php://input');
$data = json_decode($payload, true);

// Valide consultando a API
$transactionId = $data['transaction_id'];
$type = $data['type']; // 'pix.cashin' ou 'pix.cashout'

// Faça uma requisição GET para:
// - Cash-in: /api/pix/consult?transaction_id={transaction_id}
// - Cash-out: /api/cashout/consult?transaction_id={transaction_id}

// Compare os dados retornados com os dados do webhook

// Responda com 200 OK
http_response_code(200);
echo json_encode(['success' => true]);</pre>
                        </div>

                        <h4 class="font-semibold text-white mb-2 mt-4">Status de Transações</h4>
                        <p class="text-slate-300 mb-3 text-sm">
                            Os webhooks são enviados para os seguintes status:
                        </p>
                        <div class="space-y-2">
                            <div class="bg-green-900 bg-opacity-20 border border-green-700 rounded p-2 text-sm">
                                <strong class="text-green-400">completed</strong> - Transação concluída com sucesso
                            </div>
                            <div class="bg-red-900 bg-opacity-20 border border-red-700 rounded p-2 text-sm">
                                <strong class="text-red-400">failed</strong> - Transação falhou
                            </div>
                            <div class="bg-red-900 bg-opacity-20 border border-red-700 rounded p-2 text-sm">
                                <strong class="text-red-400">expired</strong> - Cobrança expirou (apenas cash-in)
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Códigos de Erro -->
                <div id="codigos-erro" class="section bg-slate-800 rounded-xl p-6 border border-slate-700">
                    <h2 class="text-2xl font-bold text-white mb-4 flex items-center">
                        <i class="fas fa-exclamation-circle text-red-500 mr-3"></i>
                        Códigos de Erro
                    </h2>

                    <div class="space-y-4">
                        <div class="border border-slate-700 rounded-lg p-4">
                            <div class="flex items-center mb-2">
                                <span class="text-red-400 font-bold text-lg mr-3">400</span>
                                <span class="text-white font-semibold">Bad Request</span>
                            </div>
                            <p class="text-slate-400 text-sm">Requisição inválida ou parâmetros faltando</p>
                            <div class="code-block mt-2">
                                <pre>{"success": false, "error": "O campo 'amount' é obrigatório"}</pre>
                            </div>
                        </div>

                        <div class="border border-slate-700 rounded-lg p-4">
                            <div class="flex items-center mb-2">
                                <span class="text-red-400 font-bold text-lg mr-3">401</span>
                                <span class="text-white font-semibold">Unauthorized</span>
                            </div>
                            <p class="text-slate-400 text-sm">Credenciais de autenticação inválidas</p>
                            <div class="code-block mt-2">
                                <pre>{"success": false, "error": "Credenciais inválidas"}</pre>
                            </div>
                        </div>

                        <div class="border border-slate-700 rounded-lg p-4">
                            <div class="flex items-center mb-2">
                                <span class="text-red-400 font-bold text-lg mr-3">403</span>
                                <span class="text-white font-semibold">Forbidden</span>
                            </div>
                            <p class="text-slate-400 text-sm">IP não está na whitelist ou conta inativa</p>
                            <div class="code-block mt-2">
                                <pre>{"success": false, "error": "Acesso negado. IP não autorizado"}</pre>
                            </div>
                        </div>

                        <div class="border border-slate-700 rounded-lg p-4">
                            <div class="flex items-center mb-2">
                                <span class="text-red-400 font-bold text-lg mr-3">404</span>
                                <span class="text-white font-semibold">Not Found</span>
                            </div>
                            <p class="text-slate-400 text-sm">Transação não encontrada</p>
                            <div class="code-block mt-2">
                                <pre>{"success": false, "error": "Transação não encontrada"}</pre>
                            </div>
                        </div>

                        <div class="border border-slate-700 rounded-lg p-4">
                            <div class="flex items-center mb-2">
                                <span class="text-red-400 font-bold text-lg mr-3">422</span>
                                <span class="text-white font-semibold">Unprocessable Entity</span>
                            </div>
                            <p class="text-slate-400 text-sm">Saldo insuficiente ou limite excedido</p>
                            <div class="code-block mt-2">
                                <pre>{"success": false, "error": "Saldo insuficiente para realizar o saque"}</pre>
                            </div>
                        </div>

                        <div class="border border-slate-700 rounded-lg p-4">
                            <div class="flex items-center mb-2">
                                <span class="text-red-400 font-bold text-lg mr-3">500</span>
                                <span class="text-white font-semibold">Internal Server Error</span>
                            </div>
                            <p class="text-slate-400 text-sm">Erro interno do servidor</p>
                            <div class="code-block mt-2">
                                <pre>{"success": false, "error": "Erro interno do servidor. Tente novamente."}</pre>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="bg-slate-800 rounded-xl p-6 border border-slate-700 text-center">
                    <p class="text-slate-400 mb-2">
                        Precisa de ajuda? Entre em contato com nosso suporte.
                    </p>
                    <a href="/" class="text-blue-400 hover:underline">
                        <i class="fas fa-arrow-left mr-2"></i>Voltar ao Painel
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        function copyToClipboard(button) {
            const codeBlock = button.parentElement.querySelector('pre');
            const text = codeBlock.textContent;

            navigator.clipboard.writeText(text).then(() => {
                const originalText = button.innerHTML;
                button.innerHTML = '<i class="fas fa-check"></i> Copiado!';
                button.style.background = '#10b981';

                setTimeout(() => {
                    button.innerHTML = originalText;
                    button.style.background = '#334155';
                }, 2000);
            });
        }

        // Smooth scroll para links âncora
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
