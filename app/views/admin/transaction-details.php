<?php
$pageTitle = 'Detalhes da Transação';
require_once __DIR__ . '/../layouts/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-8">
        <a href="/admin/transactions" class="text-blue-600 hover:text-blue-800 text-sm mb-2 inline-block">
            <i class="fas fa-arrow-left mr-1"></i>Voltar
        </a>
        <h1 class="text-3xl font-bold text-gray-900">Detalhes da Transação</h1>
        <p class="text-gray-600 mt-2"><?= $type === 'cashin' ? 'Recebimento PIX' : 'Saque PIX' ?></p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h2 class="text-lg font-bold text-gray-900 mb-4">Informações Gerais</h2>
                <div class="grid grid-cols-1 gap-4">
                    <div>
                        <label class="text-sm font-medium text-gray-600">ID da Transação</label>
                        <p class="text-gray-900 mt-1 font-mono text-sm break-all"><?= htmlspecialchars($transaction['transaction_id']) ?></p>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-sm font-medium text-gray-600">Status</label>
                            <div class="mt-1">
                                <span class="px-3 py-1 text-xs font-medium rounded-full
                                    <?php
                                    echo match($transaction['status']) {
                                        'approved', 'paid' => 'bg-green-100 text-green-800',
                                        'waiting_payment', 'pending' => 'bg-yellow-100 text-yellow-800',
                                        'cancelled', 'failed' => 'bg-red-100 text-red-800',
                                        default => 'bg-gray-100 text-gray-800'
                                    };
                                    ?>">
                                    <?= ucfirst($transaction['status']) ?>
                                </span>
                            </div>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-600">Valor</label>
                            <p class="text-gray-900 mt-1 font-semibold">R$ <?= number_format($transaction['amount'], 2, ',', '.') ?></p>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <?php if (isset($transaction['fee_amount'])): ?>
                        <div>
                            <label class="text-sm font-medium text-gray-600">Taxa</label>
                            <p class="text-gray-900 mt-1">R$ <?= number_format($transaction['fee_amount'], 2, ',', '.') ?></p>
                        </div>
                        <?php endif; ?>
                        <?php if (isset($transaction['net_amount'])): ?>
                        <div>
                            <label class="text-sm font-medium text-gray-600">Valor Líquido</label>
                            <p class="text-gray-900 mt-1 font-semibold">R$ <?= number_format($transaction['net_amount'], 2, ',', '.') ?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-sm font-medium text-gray-600">Criado em</label>
                            <p class="text-gray-900 mt-1"><?= date('d/m/Y H:i:s', strtotime($transaction['created_at'])) ?></p>
                        </div>
                        <?php if (isset($transaction['paid_at']) && $transaction['paid_at']): ?>
                        <div>
                            <label class="text-sm font-medium text-gray-600">Pago em</label>
                            <p class="text-gray-900 mt-1"><?= date('d/m/Y H:i:s', strtotime($transaction['paid_at'])) ?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php if (isset($transaction['external_id']) && $transaction['external_id']): ?>
                    <div>
                        <label class="text-sm font-medium text-gray-600">ID Externo</label>
                        <p class="text-gray-900 mt-1 font-mono text-sm break-all"><?= htmlspecialchars($transaction['external_id']) ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h2 class="text-lg font-bold text-gray-900 mb-4">Informações do Seller</h2>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="text-sm font-medium text-gray-600">ID</label>
                        <p class="text-gray-900 mt-1">
                            <a href="/admin/sellers/view/<?= $seller['id'] ?>" class="text-blue-600 hover:underline">
                                #<?= $seller['id'] ?>
                            </a>
                        </p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-600">Nome</label>
                        <p class="text-gray-900 mt-1"><?= htmlspecialchars($seller['name']) ?></p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-600">Email</label>
                        <p class="text-gray-900 mt-1"><?= htmlspecialchars($seller['email']) ?></p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-600">Status</label>
                        <p class="text-gray-900 mt-1">
                            <span class="px-2 py-1 text-xs font-medium rounded-full
                                <?php
                                echo match($seller['status']) {
                                    'active' => 'bg-green-100 text-green-800',
                                    'pending' => 'bg-yellow-100 text-yellow-800',
                                    'blocked' => 'bg-red-100 text-red-800',
                                    default => 'bg-gray-100 text-gray-800'
                                };
                                ?>">
                                <?= ucfirst($seller['status']) ?>
                            </span>
                        </p>
                    </div>
                </div>
            </div>

            <?php if (isset($account) && $account): ?>
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h2 class="text-lg font-bold text-gray-900 mb-4">Conta Adquirente</h2>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="text-sm font-medium text-gray-600">Adquirente</label>
                        <p class="text-gray-900 mt-1"><?= htmlspecialchars($account['acquirer_name'] ?? 'N/A') ?></p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-600">Conta</label>
                        <p class="text-gray-900 mt-1"><?= htmlspecialchars($account['name'] ?? 'N/A') ?></p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-600">Client ID</label>
                        <p class="text-gray-900 mt-1 font-mono text-sm"><?= htmlspecialchars(substr($account['client_id'] ?? '', 0, 20)) ?>...</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-600">Status da Conta</label>
                        <p class="text-gray-900 mt-1">
                            <span class="px-2 py-1 text-xs font-medium rounded-full
                                <?= ($account['is_active'] ?? false) ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                <?= ($account['is_active'] ?? false) ? 'Ativa' : 'Inativa' ?>
                            </span>
                        </p>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($type === 'cashin' && isset($transaction['customer_name'])): ?>
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h2 class="text-lg font-bold text-gray-900 mb-4">Dados do Cliente</h2>
                <div class="grid grid-cols-2 gap-4">
                    <?php if ($transaction['customer_name']): ?>
                    <div>
                        <label class="text-sm font-medium text-gray-600">Nome</label>
                        <p class="text-gray-900 mt-1"><?= htmlspecialchars($transaction['customer_name']) ?></p>
                    </div>
                    <?php endif; ?>
                    <?php if ($transaction['customer_document']): ?>
                    <div>
                        <label class="text-sm font-medium text-gray-600">Documento</label>
                        <p class="text-gray-900 mt-1"><?= htmlspecialchars($transaction['customer_document']) ?></p>
                    </div>
                    <?php endif; ?>
                    <?php if ($transaction['customer_email']): ?>
                    <div class="col-span-2">
                        <label class="text-sm font-medium text-gray-600">Email</label>
                        <p class="text-gray-900 mt-1"><?= htmlspecialchars($transaction['customer_email']) ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($type === 'cashin' && isset($transaction['payer_name']) && $transaction['payer_name']): ?>
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h2 class="text-lg font-bold text-gray-900 mb-4">Dados do Pagador</h2>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="text-sm font-medium text-gray-600">Nome</label>
                        <p class="text-gray-900 mt-1"><?= htmlspecialchars($transaction['payer_name']) ?></p>
                    </div>
                    <?php if ($transaction['payer_document']): ?>
                    <div>
                        <label class="text-sm font-medium text-gray-600">Documento</label>
                        <p class="text-gray-900 mt-1"><?= htmlspecialchars($transaction['payer_document']) ?></p>
                    </div>
                    <?php endif; ?>
                    <?php if ($transaction['payer_bank']): ?>
                    <div class="col-span-2">
                        <label class="text-sm font-medium text-gray-600">Banco</label>
                        <p class="text-gray-900 mt-1"><?= htmlspecialchars($transaction['payer_bank']) ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <div class="space-y-6">
            <?php if ($type === 'cashin' && $transaction['qrcode'] && !in_array($transaction['status'], ['approved', 'paid'])): ?>
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="font-bold text-gray-900 mb-4">QR Code PIX</h3>
                <?php if ($transaction['qrcode_base64']): ?>
                <div class="mb-4 flex justify-center">
                    <img src="data:image/png;base64,<?= htmlspecialchars($transaction['qrcode_base64']) ?>" alt="QR Code PIX" class="w-64 h-64 border-2 border-gray-200 rounded-lg">
                </div>
                <?php endif; ?>
                <div>
                    <label class="text-sm font-medium text-gray-600 mb-2 block">Código Copia e Cola</label>
                    <div class="bg-gray-50 p-3 rounded-lg break-all text-xs font-mono text-gray-700">
                        <?= htmlspecialchars($transaction['qrcode']) ?>
                    </div>
                    <button onclick="copyToClipboard('<?= htmlspecialchars($transaction['qrcode']) ?>', this)"
                            class="w-full mt-3 px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 transition">
                        <i class="fas fa-copy mr-2"></i>Copiar Código
                    </button>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($transaction['end_to_end_id']): ?>
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="font-bold text-gray-900 mb-4">Identificadores</h3>
                <div class="space-y-3">
                    <div>
                        <label class="text-sm font-medium text-gray-600">End-to-End ID</label>
                        <p class="text-gray-900 mt-1 font-mono text-xs break-all"><?= htmlspecialchars($transaction['end_to_end_id']) ?></p>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($type === 'cashout' && isset($transaction['receipt_url']) && $transaction['receipt_url']): ?>
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="font-bold text-gray-900 mb-4">Comprovante</h3>
                <p class="text-sm text-gray-600 mb-4">Visualize o comprovante PDF sem sair da página.</p>
                <button id="openPdfViewerBtn"
                        data-url="/admin/transactions/receipt?transaction_id=<?= urlencode($transaction['transaction_id']) ?>&type=cashout"
                        data-external-url="<?= htmlspecialchars($transaction['receipt_url']) ?>"
                        class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 transition">
                    <i class="fas fa-file-pdf mr-2"></i>Ver Comprovante (PDF)
                </button>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div id="pdfViewerModal" class="modal hidden">
    <div class="modal-content max-w-6xl w-full">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-xl font-bold text-white">Comprovante PIX</h3>
            <button id="closePdfViewerBtn" class="px-3 py-2 rounded-lg bg-slate-700 text-white hover:bg-slate-600"><i class="fas fa-times"></i></button>
        </div>
        <div class="flex items-center justify-between mb-3">
            <div class="flex items-center space-x-2">
                <button id="prevPageBtn" class="px-3 py-2 rounded-lg bg-slate-700 text-white hover:bg-slate-600"><i class="fas fa-chevron-left"></i></button>
                <button id="nextPageBtn" class="px-3 py-2 rounded-lg bg-slate-700 text-white hover:bg-slate-600"><i class="fas fa-chevron-right"></i></button>
                <span id="pageIndicator" class="ml-2 text-slate-300 text-sm">Página <span id="pageNum">1</span>/<span id="pageCount">1</span></span>
            </div>
            <div class="flex items-center space-x-2">
                <button id="zoomOutBtn" class="px-3 py-2 rounded-lg bg-slate-700 text-white hover:bg-slate-600"><i class="fas fa-search-minus"></i></button>
                <button id="zoomInBtn" class="px-3 py-2 rounded-lg bg-slate-700 text-white hover:bg-slate-600"><i class="fas fa-search-plus"></i></button>
            </div>
        </div>
        <div id="pdfContainer" class="relative bg-slate-800 rounded-lg border border-slate-700 h-[75vh] overflow-auto">
            <div id="pdfLoader" class="absolute inset-0 flex items-center justify-center">
                <div class="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-blue-500"></div>
            </div>
            <div id="pdfError" class="hidden absolute inset-0 flex items-center justify-center">
                <div class="alert alert-error">
                    <div class="flex items-center space-x-2">
                        <i class="fas fa-exclamation-triangle"></i>
                        <span>Não foi possível carregar o PDF.</span>
                    </div>
                    <div class="mt-3 flex justify-center">
                        <a id="pdfErrorOpenLink" href="#" target="_blank" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700">Abrir em nova guia</a>
                    </div>
                </div>
            </div>
            <canvas id="pdfCanvas" class="mx-auto block"></canvas>
            <iframe id="pdfIframeFallback" class="hidden w-full h-full"></iframe>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/4.2.67/pdf.min.js"></script>
<script>
    const openBtn = document.getElementById('openPdfViewerBtn');
    const modal = document.getElementById('pdfViewerModal');
    const closeBtn = document.getElementById('closePdfViewerBtn');
    const loader = document.getElementById('pdfLoader');
    const errorBox = document.getElementById('pdfError');
    const canvas = document.getElementById('pdfCanvas');
    const ctx = canvas ? canvas.getContext('2d') : null;
    const iframeFallback = document.getElementById('pdfIframeFallback');
    const prevBtn = document.getElementById('prevPageBtn');
    const nextBtn = document.getElementById('nextPageBtn');
    const zoomInBtn = document.getElementById('zoomInBtn');
    const zoomOutBtn = document.getElementById('zoomOutBtn');
    const pageNumEl = document.getElementById('pageNum');
    const pageCountEl = document.getElementById('pageCount');
    let pdfDoc = null;
    let pageNum = 1;
    let pageRendering = false;
    let pageNumPending = null;
    let scale = 1.0;
    let pdfUrl = null;
    let externalUrl = null;

    function openModal() {
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    function closeModal() {
        modal.classList.add('hidden');
        document.body.style.overflow = '';
        pdfDoc = null;
        pageNum = 1;
        pageNumPending = null;
        scale = 1.0;
        loader.classList.add('hidden');
        errorBox.classList.add('hidden');
        iframeFallback.classList.add('hidden');
    }

    function renderPage(num) {
        pageRendering = true;
        pdfDoc.getPage(num).then(function(page) {
            const viewport = page.getViewport({ scale });
            const containerWidth = document.getElementById('pdfContainer').clientWidth;
            const ratio = Math.min(containerWidth / viewport.width, 1);
            const vp = page.getViewport({ scale: scale * ratio });
            canvas.width = vp.width;
            canvas.height = vp.height;
            const renderContext = { canvasContext: ctx, viewport: vp };
            const renderTask = page.render(renderContext);
            renderTask.promise.then(function() {
                pageRendering = false;
                if (pageNumPending !== null) {
                    renderPage(pageNumPending);
                    pageNumPending = null;
                }
                loader.classList.add('hidden');
            });
        }).catch(function() {
            errorBox.classList.remove('hidden');
            loader.classList.add('hidden');
        });
        pageNumEl.textContent = num;
    }

    function queueRenderPage(num) {
        if (pageRendering) {
            pageNumPending = num;
        } else {
            renderPage(num);
        }
    }

    function onPrevPage() {
        if (pageNum <= 1) return;
        pageNum--;
        queueRenderPage(pageNum);
    }

    function onNextPage() {
        if (!pdfDoc) return;
        if (pageNum >= pdfDoc.numPages) return;
        pageNum++;
        queueRenderPage(pageNum);
    }

    function onZoom(delta) {
        scale = Math.max(0.5, Math.min(3.0, scale + delta));
        queueRenderPage(pageNum);
    }

    function initPdf(url) {
        loader.classList.remove('hidden');
        errorBox.classList.add('hidden');
        iframeFallback.classList.add('hidden');
        if (!window['pdfjsLib']) {
            iframeFallback.src = url;
            iframeFallback.classList.remove('hidden');
            loader.classList.add('hidden');
            return;
        }
        pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/4.2.67/pdf.worker.min.js';
        pdfjsLib.getDocument({ url }).promise.then(function(pdf) {
            pdfDoc = pdf;
            pageCountEl.textContent = pdf.numPages;
            renderPage(1);
        }).catch(function() {
            loader.classList.add('hidden');
            errorBox.classList.remove('hidden');
            const link = document.getElementById('pdfErrorOpenLink');
            if (link && externalUrl) {
                link.href = externalUrl;
                link.target = '_blank';
            }
        });
    }

    if (openBtn) {
        openBtn.addEventListener('click', function() {
            pdfUrl = this.getAttribute('data-url');
            externalUrl = this.getAttribute('data-external-url');
            openModal();
            initPdf(pdfUrl);
        });
    }

    if (closeBtn) {
        closeBtn.addEventListener('click', function() {
            closeModal();
        });
    }

    if (prevBtn) prevBtn.addEventListener('click', onPrevPage);
    if (nextBtn) nextBtn.addEventListener('click', onNextPage);
    if (zoomInBtn) zoomInBtn.addEventListener('click', function(){ onZoom(0.2); });
    if (zoomOutBtn) zoomOutBtn.addEventListener('click', function(){ onZoom(-0.2); });

    document.addEventListener('keydown', function(e) {
        if (modal.classList.contains('hidden')) return;
        if (e.key === 'Escape') closeModal();
        if (e.key === 'ArrowLeft') onPrevPage();
        if (e.key === 'ArrowRight') onNextPage();
        if ((e.ctrlKey || e.metaKey) && e.key === '=') onZoom(0.2);
        if ((e.ctrlKey || e.metaKey) && e.key === '-') onZoom(-0.2);
    });
</script>
<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
