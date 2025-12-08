<?php

require_once __DIR__ . '/../../middleware/CheckAdmin.php';
require_once __DIR__ . '/../../models/Seller.php';
require_once __DIR__ . '/../../models/SellerDocument.php';
require_once __DIR__ . '/../../models/PixCashin.php';
require_once __DIR__ . '/../../models/PixCashout.php';
require_once __DIR__ . '/../../models/Acquirer.php';
require_once __DIR__ . '/../../models/AcquirerAccount.php';
require_once __DIR__ . '/../../models/SellerAcquirerAccount.php';
require_once __DIR__ . '/../../models/User.php';
require_once __DIR__ . '/../../models/Log.php';
require_once __DIR__ . '/../../models/SystemSettings.php';
require_once __DIR__ . '/../../services/NotificationService.php';
require_once __DIR__ . '/../../services/FileUploadService.php';

class AdminController {
    private $sellerModel;
    private $documentModel;
    private $cashinModel;
    private $cashoutModel;
    private $acquirerModel;
    private $accountModel;
    private $sellerAccountModel;
    private $userModel;
    private $logModel;
    private $settingsModel;
    private $notificationService;
    private $fileUploadService;

    public function __construct() {
        CheckAdmin::handle();

        $this->sellerModel = new Seller();
        $this->documentModel = new SellerDocument();
        $this->cashinModel = new PixCashin();
        $this->cashoutModel = new PixCashout();
        $this->acquirerModel = new Acquirer();
        $this->accountModel = new AcquirerAccount();
        $this->sellerAccountModel = new SellerAcquirerAccount();
        $this->userModel = new User();
        $this->logModel = new Log();
        $this->settingsModel = new SystemSettings();
        $this->notificationService = new NotificationService();
        $this->fileUploadService = new FileUploadService();
    }

    private function parseDecimal($value) {
        if (is_numeric($value)) {
            return floatval($value);
        }
        // Substitui vírgula por ponto e remove outros caracteres
        $value = str_replace(',', '.', $value);
        $value = preg_replace('/[^0-9.]/', '', $value);
        return floatval($value);
    }

    public function dashboard() {
        $dateFrom = date('Y-m-d', strtotime('-7 days'));

        // Get platform revenue (fees collected)
        $revenueStats = $this->cashinModel->getStats();
        $platformRevenue = floatval($revenueStats['total_fees'] ?? 0);

        // Calculate cashout fees
        $cashoutRevenue = $this->cashoutModel->getTotalFees();

        // Total platform revenue
        $totalRevenue = $platformRevenue + floatval($cashoutRevenue);

        $stats = [
            'total_sellers' => $this->sellerModel->count(['status' => 'active']),
            'pending_sellers' => $this->sellerModel->count(['status' => 'pending']),
            'total_cashin' => $this->cashinModel->getTotalAmount(),
            'total_cashout' => $this->cashoutModel->getTotalAmount(),
            'pending_documents' => $this->documentModel->count(['status' => 'pending']),
            'platform_revenue' => $totalRevenue,
            'cashin_fees' => $platformRevenue,
            'cashout_fees' => floatval($cashoutRevenue),
            'daily_cashin' => $this->cashinModel->getDailyStats($dateFrom),
            'daily_cashout' => []
        ];

        $recentSellers = $this->sellerModel->where(['status' => 'pending'], 'created_at DESC', 5);
        $recentCashin = $this->cashinModel->all('created_at DESC', 10);
        $recentLogs = $this->logModel->where(['level' => 'error'], 'created_at DESC', 10);
        $accountStats = $this->getAccountStatistics();

        require __DIR__ . '/../../views/admin/dashboard.php';
    }

    public function sellers() {
        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $perPage = 20;
        $offset = ($page - 1) * $perPage;

        $status = $_GET['status'] ?? '';
        $search = $_GET['search'] ?? '';

        if ($status) {
            $sellers = $this->sellerModel->where(['status' => $status], 'created_at DESC', $perPage, $offset);
        } elseif ($search) {
            $sellers = $this->sellerModel->search($search);
        } else {
            $sellers = $this->sellerModel->all('created_at DESC', $perPage, $offset);
        }

        require __DIR__ . '/../../views/admin/sellers.php';
    }

    public function sellerDetails($sellerId) {
        $seller = $this->sellerModel->find($sellerId);

        if (!$seller) {
            $_SESSION['error'] = 'Seller não encontrado';
            header('Location: /admin/sellers');
            exit;
        }

        $documents = $this->documentModel->getDocumentsBySeller($sellerId);
        $cashinStats = $this->cashinModel->getStatsBySeller($sellerId);
        $cashoutStats = $this->cashoutModel->getStatsBySeller($sellerId);
        $recentTransactions = array_merge(
            $this->cashinModel->getRecentBySeller($sellerId, 5),
            $this->cashoutModel->getRecentBySeller($sellerId, 5)
        );
        $accounts = $this->sellerAccountModel->getBySellerWithDetails($sellerId);

        $requiredDocs = ['rg', 'cpf', 'selfie'];
        $missingDocs = [];
        $existingDocTypes = array_column($documents, 'document_type');

        foreach ($requiredDocs as $docType) {
            if (!in_array($docType, $existingDocTypes)) {
                $missingDocs[] = $this->getDocumentTypeName($docType);
            }
        }

        require __DIR__ . '/../../views/admin/seller-details.php';
    }

    private function getDocumentTypeName($type) {
        $names = [
            'rg' => 'RG',
            'cpf' => 'CPF',
            'cnpj' => 'CNPJ',
            'proof_address' => 'Comprovante de Endereço',
            'selfie' => 'Selfie com Documento',
            'bank_statement' => 'Extrato Bancário',
            'contract' => 'Contrato Social'
        ];
        return $names[$type] ?? $type;
    }

    public function approveSeller($sellerId) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /admin/sellers/view/' . $sellerId);
            exit;
        }

        $seller = $this->sellerModel->find($sellerId);

        if (!$seller) {
            $_SESSION['error'] = 'Seller não encontrado';
            header('Location: /admin/sellers');
            exit;
        }

        $apiKey = 'sk_live_' . bin2hex(random_bytes(32));
        $apiSecret = bin2hex(random_bytes(32));

        $this->sellerModel->update($sellerId, [
            'status' => 'active',
            'document_status' => 'approved',
            'api_key' => $apiKey,
            'api_secret' => hash('sha256', $apiSecret),
            'approved_by' => $_SESSION['user_id'],
            'approved_at' => date('Y-m-d H:i:s')
        ]);

        $this->notificationService->notifyAccountApproved($sellerId, $apiKey, $apiSecret);

        $_SESSION['success'] = 'Seller aprovado com sucesso!';
        header('Location: /admin/sellers/view/' . $sellerId);
        exit;
    }

    public function rejectSeller($sellerId) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /admin/sellers/view/' . $sellerId);
            exit;
        }

        $reason = $_POST['reason'] ?? 'Não especificado';

        $seller = $this->sellerModel->find($sellerId);

        if (!$seller) {
            $_SESSION['error'] = 'Seller não encontrado';
            header('Location: /admin/sellers');
            exit;
        }

        $this->sellerModel->update($sellerId, [
            'status' => 'rejected',
            'document_status' => 'rejected',
            'approval_notes' => $reason
        ]);

        $this->notificationService->notifyAccountRejected($sellerId, $reason);

        $_SESSION['success'] = 'Seller rejeitado';
        header('Location: /admin/sellers/view/' . $sellerId);
        exit;
    }

    public function documents() {
        $status = $_GET['status'] ?? 'pending';

        if ($status === 'pending') {
            $documents = $this->documentModel->getPendingDocuments(50);
        } elseif ($status === 'under_review') {
            $documents = $this->documentModel->getUnderReviewDocuments(50);
        } else {
            $documents = $this->documentModel->where(['status' => $status], 'created_at DESC', 50);
        }

        $documentsWithSeller = [];
        foreach ($documents as $doc) {
            $seller = $this->sellerModel->find($doc['seller_id']);
            $documentsWithSeller[] = array_merge($doc, ['seller' => $seller]);
        }

        require __DIR__ . '/../../views/admin/documents.php';
    }

    public function viewDocument($documentId) {
        $document = $this->documentModel->find($documentId);

        if (!$document) {
            $_SESSION['error'] = 'Documento não encontrado';
            header('Location: /admin/documents');
            exit;
        }

        $seller = $this->sellerModel->find($document['seller_id']);

        require __DIR__ . '/../../views/admin/document-view.php';
    }

    public function approveDocument($documentId) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /admin/documents');
            exit;
        }

        $document = $this->documentModel->find($documentId);

        if (!$document) {
            $_SESSION['error'] = 'Documento não encontrado';

            if ($this->isAjaxRequest()) {
                http_response_code(404);
                echo json_encode(['error' => 'Documento não encontrado']);
                exit;
            }

            header('Location: /admin/documents');
            exit;
        }

        $this->documentModel->approveDocument($documentId, $_SESSION['user_id']);
        $this->notificationService->notifyDocumentApproved($document['seller_id'], $document['document_type']);

        $_SESSION['success'] = 'Documento aprovado!';

        if ($this->isAjaxRequest()) {
            http_response_code(200);
            echo json_encode(['success' => true, 'message' => 'Documento aprovado!']);
            exit;
        }

        header('Location: /admin/documents/view/' . $documentId);
        exit;
    }

    public function rejectDocument($documentId) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /admin/documents');
            exit;
        }

        $reason = $_POST['reason'] ?? 'Não especificado';

        $document = $this->documentModel->find($documentId);

        if (!$document) {
            $_SESSION['error'] = 'Documento não encontrado';

            if ($this->isAjaxRequest()) {
                http_response_code(404);
                echo json_encode(['error' => 'Documento não encontrado']);
                exit;
            }

            header('Location: /admin/documents');
            exit;
        }

        $this->documentModel->rejectDocument($documentId, $_SESSION['user_id'], $reason);
        $this->notificationService->notifyDocumentRejected($document['seller_id'], $document['document_type'], $reason);

        $_SESSION['success'] = 'Documento rejeitado';

        if ($this->isAjaxRequest()) {
            http_response_code(200);
            echo json_encode(['success' => true, 'message' => 'Documento rejeitado']);
            exit;
        }

        header('Location: /admin/documents/view/' . $documentId);
        exit;
    }

    public function transactions() {
        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $perPage = 50;
        $offset = ($page - 1) * $perPage;

        $type = $_GET['type'] ?? 'all';
        $status = $_GET['status'] ?? '';
        $search = $_GET['search'] ?? '';

        if (!empty($search)) {
            if ($type === 'cashin' || $type === 'all') {
                $cashin = $this->cashinModel->search($search, $status, $perPage, $offset);
            } else {
                $cashin = [];
            }

            if ($type === 'cashout' || $type === 'all') {
                $cashout = $this->cashoutModel->search($search, $status, $perPage, $offset);
            } else {
                $cashout = [];
            }
        } else {
            if ($type === 'cashin' || $type === 'all') {
                $cashin = $this->cashinModel->all('created_at DESC', $perPage, $offset);
            } else {
                $cashin = [];
            }

            if ($type === 'cashout' || $type === 'all') {
                $cashout = $this->cashoutModel->all('created_at DESC', $perPage, $offset);
            } else {
                $cashout = [];
            }
        }

        require __DIR__ . '/../../views/admin/transactions.php';
    }

    public function transactionDetails($transactionId, $type) {
        if ($type === 'cashin') {
            $transaction = $this->cashinModel->find($transactionId);
        } else {
            $transaction = $this->cashoutModel->find($transactionId);
        }

        if (!$transaction) {
            $_SESSION['error'] = 'Transação não encontrada';
            header('Location: /admin/transactions');
            exit;
        }

        $seller = $this->sellerModel->find($transaction['seller_id']);

        // Get account information if available
        $account = null;
        if (!empty($transaction['acquirer_account_id'])) {
            $account = $this->accountModel->getAccountWithAcquirer($transaction['acquirer_account_id']);
        }

        require __DIR__ . '/../../views/admin/transaction-details.php';
    }

    public function viewReceipt() {
        $transactionId = $_GET['transaction_id'] ?? null;
        $type = $_GET['type'] ?? 'cashout';
        if (!$transactionId) {
            http_response_code(400);
            echo 'transaction_id is required';
            exit;
        }
        $transaction = $type === 'cashin'
            ? $this->cashinModel->findByTransactionId($transactionId)
            : $this->cashoutModel->findByTransactionId($transactionId);
        if (!$transaction || empty($transaction['receipt_url'])) {
            http_response_code(404);
            echo 'Receipt not available';
            exit;
        }
        $url = $transaction['receipt_url'];
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_HTTPHEADER => ['Accept: application/pdf']
        ]);
        $body = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($httpCode >= 200 && $httpCode < 300 && $body) {
            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="receipt.pdf"');
            echo $body;
            exit;
        }
        http_response_code(502);
        echo 'Failed to fetch receipt';
        exit;
    }

    public function acquirers() {
        $acquirers = $this->acquirerModel->getAllWithAccountCount();
        require __DIR__ . '/../../views/admin/acquirers.php';
    }

    public function getAcquirer($acquirerId) {
        header('Content-Type: application/json');

        $acquirer = $this->acquirerModel->find($acquirerId);

        if (!$acquirer) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Adquirente não encontrada']);
            exit;
        }

        echo json_encode(['success' => true, 'acquirer' => $acquirer]);
        exit;
    }

    public function createAcquirer() {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Método não permitido']);
            exit;
        }

        $name = trim($_POST['name'] ?? '');
        $code = strtolower(trim($_POST['code'] ?? ''));
        $apiUrl = trim($_POST['api_url'] ?? '');
        $apiKey = trim($_POST['api_key'] ?? '');
        $apiSecret = trim($_POST['api_secret'] ?? '');
        $withdrawKey = trim($_POST['withdraw_key'] ?? '');
        $priorityOrder = intval($_POST['priority_order'] ?? 1);
        $status = $_POST['status'] ?? 'active';

        if (empty($name) || empty($code) || empty($apiUrl)) {
            echo json_encode(['success' => false, 'error' => 'Nome, código e URL são obrigatórios']);
            exit;
        }

        if (!preg_match('/^[a-z0-9_-]+$/', $code)) {
            echo json_encode(['success' => false, 'error' => 'Código deve conter apenas letras minúsculas, números, hífen e underscore']);
            exit;
        }

        if (!filter_var($apiUrl, FILTER_VALIDATE_URL)) {
            echo json_encode(['success' => false, 'error' => 'URL da API inválida']);
            exit;
        }

        $existing = $this->acquirerModel->findByCode($code);
        if ($existing) {
            echo json_encode(['success' => false, 'error' => 'Código já existe']);
            exit;
        }

        $config = [];
        if (!empty($withdrawKey)) {
            $config['withdraw_key'] = $withdrawKey;
        }

        $data = [
            'name' => $name,
            'code' => $code,
            'api_url' => $apiUrl,
            'api_key' => $apiKey ?: null,
            'api_secret' => $apiSecret ?: null,
            'priority_order' => $priorityOrder,
            'status' => $status,
            'success_rate' => 100.00,
            'avg_response_time' => 0,
            'config' => !empty($config) ? json_encode($config) : null
        ];

        $acquirerId = $this->acquirerModel->create($data);

        $this->logModel->info('admin', 'Adquirente criada', [
            'acquirer_id' => $acquirerId,
            'code' => $code,
            'admin_id' => $_SESSION['user_id']
        ]);

        echo json_encode(['success' => true, 'message' => 'Adquirente criada com sucesso!', 'id' => $acquirerId]);
        exit;
    }

    public function updateAcquirer($acquirerId) {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Método não permitido']);
            exit;
        }

        $acquirer = $this->acquirerModel->find($acquirerId);
        if (!$acquirer) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Adquirente não encontrada']);
            exit;
        }

        $name = trim($_POST['name'] ?? '');
        $code = strtolower(trim($_POST['code'] ?? ''));
        $apiUrl = trim($_POST['api_url'] ?? '');
        $apiKey = trim($_POST['api_key'] ?? '');
        $apiSecret = trim($_POST['api_secret'] ?? '');
        $withdrawKey = trim($_POST['withdraw_key'] ?? '');
        $priorityOrder = intval($_POST['priority_order'] ?? 1);
        $status = $_POST['status'] ?? 'active';

        if (empty($name) || empty($code) || empty($apiUrl)) {
            echo json_encode(['success' => false, 'error' => 'Nome, código e URL são obrigatórios']);
            exit;
        }

        if (!preg_match('/^[a-z0-9_-]+$/', $code)) {
            echo json_encode(['success' => false, 'error' => 'Código deve conter apenas letras minúsculas, números, hífen e underscore']);
            exit;
        }

        if (!filter_var($apiUrl, FILTER_VALIDATE_URL)) {
            echo json_encode(['success' => false, 'error' => 'URL da API inválida']);
            exit;
        }

        if ($code !== $acquirer['code']) {
            $existing = $this->acquirerModel->findByCode($code);
            if ($existing) {
                echo json_encode(['success' => false, 'error' => 'Código já existe']);
                exit;
            }
        }

        $existingConfig = json_decode($acquirer['config'] ?? '{}', true) ?? [];
        if (!empty($withdrawKey)) {
            $existingConfig['withdraw_key'] = $withdrawKey;
        } else {
            unset($existingConfig['withdraw_key']);
        }

        $data = [
            'name' => $name,
            'code' => $code,
            'api_url' => $apiUrl,
            'priority_order' => $priorityOrder,
            'status' => $status,
            'config' => !empty($existingConfig) ? json_encode($existingConfig) : null
        ];

        if (!empty($apiKey)) {
            $data['api_key'] = $apiKey;
        }

        if (!empty($apiSecret)) {
            $data['api_secret'] = $apiSecret;
        }

        $this->acquirerModel->update($acquirerId, $data);

        $this->logModel->info('admin', 'Adquirente atualizada', [
            'acquirer_id' => $acquirerId,
            'code' => $code,
            'admin_id' => $_SESSION['user_id']
        ]);

        echo json_encode(['success' => true, 'message' => 'Adquirente atualizada com sucesso!']);
        exit;
    }

    public function toggleAcquirerStatus($acquirerId) {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Método não permitido']);
            exit;
        }

        $acquirer = $this->acquirerModel->find($acquirerId);
        if (!$acquirer) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Adquirente não encontrada']);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $newStatus = $input['status'] ?? '';

        if (!in_array($newStatus, ['active', 'inactive', 'maintenance'])) {
            echo json_encode(['success' => false, 'error' => 'Status inválido']);
            exit;
        }

        $this->acquirerModel->update($acquirerId, ['status' => $newStatus]);

        $this->logModel->info('admin', 'Status da adquirente alterado', [
            'acquirer_id' => $acquirerId,
            'old_status' => $acquirer['status'],
            'new_status' => $newStatus,
            'admin_id' => $_SESSION['user_id']
        ]);

        echo json_encode(['success' => true, 'message' => 'Status alterado com sucesso!']);
        exit;
    }

    public function deleteAcquirer($acquirerId) {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Método não permitido']);
            exit;
        }

        $acquirer = $this->acquirerModel->find($acquirerId);
        if (!$acquirer) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Adquirente não encontrada']);
            exit;
        }

        $activeCashin = $this->cashinModel->count(['acquirer_id' => $acquirerId, 'status' => 'pending']);
        $activeCashout = $this->cashoutModel->count(['acquirer_id' => $acquirerId, 'status' => 'pending']);

        if ($activeCashin > 0 || $activeCashout > 0) {
            echo json_encode(['success' => false, 'error' => 'Não é possível excluir adquirente com transações pendentes']);
            exit;
        }

        $this->acquirerModel->delete($acquirerId);

        $this->logModel->warning('admin', 'Adquirente excluída', [
            'acquirer_id' => $acquirerId,
            'code' => $acquirer['code'],
            'admin_id' => $_SESSION['user_id']
        ]);

        echo json_encode(['success' => true, 'message' => 'Adquirente excluída com sucesso!']);
        exit;
    }

    public function updateSellerFees($sellerId) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /admin/sellers/view/' . $sellerId);
            exit;
        }

        $seller = $this->sellerModel->find($sellerId);

        if (!$seller) {
            $_SESSION['error'] = 'Seller não encontrado';
            header('Location: /admin/sellers');
            exit;
        }

        // Converte valores com vírgula para decimal
        $feePercentageCashin = $this->parseDecimal($_POST['fee_percentage_cashin'] ?? 0) / 100;
        $feeFixedCashin = $this->parseDecimal($_POST['fee_fixed_cashin'] ?? 0);
        $feePercentageCashout = $this->parseDecimal($_POST['fee_percentage_cashout'] ?? 0) / 100;
        $feeFixedCashout = $this->parseDecimal($_POST['fee_fixed_cashout'] ?? 0);

        $balanceRetention = isset($_POST['balance_retention']) ? 1 : 0;
        $revenueRetentionPercentage = $this->parseDecimal($_POST['revenue_retention_percentage'] ?? 0);
        $retentionReason = $_POST['retention_reason'] ?? '';

        // Se retenção de saldo está desativada, zerar percentual e motivo
        if (!$balanceRetention) {
            $revenueRetentionPercentage = 0;
            $retentionReason = '';
        }

        if ($feePercentageCashin < 0) {
            $_SESSION['error'] = 'Taxa percentual de cash-in não pode ser negativa';
            header('Location: /admin/sellers/view/' . $sellerId);
            exit;
        }

        if ($feePercentageCashout < 0) {
            $_SESSION['error'] = 'Taxa percentual de cash-out não pode ser negativa';
            header('Location: /admin/sellers/view/' . $sellerId);
            exit;
        }

        if ($revenueRetentionPercentage < 0 || $revenueRetentionPercentage > 100) {
            $_SESSION['error'] = 'Percentual de retenção deve estar entre 0% e 100%';
            header('Location: /admin/sellers/view/' . $sellerId);
            exit;
        }

        $updateData = [
            'fee_percentage_cashin' => $feePercentageCashin,
            'fee_fixed_cashin' => $feeFixedCashin,
            'fee_percentage_cashout' => $feePercentageCashout,
            'fee_fixed_cashout' => $feeFixedCashout,
            'balance_retention' => $balanceRetention,
            'revenue_retention_percentage' => $revenueRetentionPercentage,
            'retention_reason' => $retentionReason
        ];

        if ($balanceRetention && $revenueRetentionPercentage > 0) {
            if (!$seller['retention_started_at']) {
                $updateData['retention_started_at'] = date('Y-m-d H:i:s');
                $updateData['retention_started_by'] = $_SESSION['user_id'];
            }
        } else {
            $updateData['retention_started_at'] = null;
            $updateData['retention_started_by'] = null;
        }

        $this->sellerModel->update($sellerId, $updateData);

        $this->logModel->create([
            'level' => 'info',
            'category' => 'admin',
            'message' => 'Taxas e retenção atualizadas para seller ID ' . $sellerId,
            'context' => json_encode([
                'seller_id' => $sellerId,
                'fee_percentage_cashin' => $feePercentageCashin,
                'fee_fixed_cashin' => $feeFixedCashin,
                'fee_percentage_cashout' => $feePercentageCashout,
                'fee_fixed_cashout' => $feeFixedCashout,
                'balance_retention' => $balanceRetention,
                'revenue_retention_percentage' => $revenueRetentionPercentage,
                'updated_by' => $_SESSION['user_id']
            ]),
            'user_id' => $_SESSION['user_id']
        ]);

        $_SESSION['success'] = 'Configurações atualizadas com sucesso!';
        header('Location: /admin/sellers/view/' . $sellerId);
        exit;
    }

    public function updateSellerLimits($sellerId) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /admin/sellers/view/' . $sellerId);
            exit;
        }

        $seller = $this->sellerModel->find($sellerId);

        if (!$seller) {
            $_SESSION['error'] = 'Seller não encontrado';
            header('Location: /admin/sellers');
            exit;
        }

        $minCashinAmount = isset($_POST['min_cashin_amount']) && $_POST['min_cashin_amount'] !== ''
            ? $this->parseDecimal($_POST['min_cashin_amount']) : null;
        $maxCashinAmount = isset($_POST['max_cashin_amount']) && $_POST['max_cashin_amount'] !== ''
            ? $this->parseDecimal($_POST['max_cashin_amount']) : null;
        $minCashoutAmount = isset($_POST['min_cashout_amount']) && $_POST['min_cashout_amount'] !== ''
            ? $this->parseDecimal($_POST['min_cashout_amount']) : null;
        $maxCashoutAmount = isset($_POST['max_cashout_amount']) && $_POST['max_cashout_amount'] !== ''
            ? $this->parseDecimal($_POST['max_cashout_amount']) : null;
        $cashoutDailyLimit = isset($_POST['cashout_daily_limit']) && $_POST['cashout_daily_limit'] !== ''
            ? $this->parseDecimal($_POST['cashout_daily_limit']) : null;

        if ($minCashinAmount !== null && $minCashinAmount < 0) {
            $_SESSION['error'] = 'Valor mínimo de cash-in não pode ser negativo';
            header('Location: /admin/sellers/view/' . $sellerId);
            exit;
        }

        if ($maxCashinAmount !== null && $maxCashinAmount < 0) {
            $_SESSION['error'] = 'Valor máximo de cash-in não pode ser negativo';
            header('Location: /admin/sellers/view/' . $sellerId);
            exit;
        }

        if ($minCashinAmount !== null && $maxCashinAmount !== null && $minCashinAmount > $maxCashinAmount) {
            $_SESSION['error'] = 'Valor mínimo de cash-in não pode ser maior que o máximo';
            header('Location: /admin/sellers/view/' . $sellerId);
            exit;
        }

        if ($minCashoutAmount !== null && $minCashoutAmount < 0) {
            $_SESSION['error'] = 'Valor mínimo de cash-out não pode ser negativo';
            header('Location: /admin/sellers/view/' . $sellerId);
            exit;
        }

        if ($maxCashoutAmount !== null && $maxCashoutAmount < 0) {
            $_SESSION['error'] = 'Valor máximo de cash-out não pode ser negativo';
            header('Location: /admin/sellers/view/' . $sellerId);
            exit;
        }

        if ($minCashoutAmount !== null && $maxCashoutAmount !== null && $minCashoutAmount > $maxCashoutAmount) {
            $_SESSION['error'] = 'Valor mínimo de cash-out não pode ser maior que o máximo';
            header('Location: /admin/sellers/view/' . $sellerId);
            exit;
        }

        if ($cashoutDailyLimit !== null && $cashoutDailyLimit < 0) {
            $_SESSION['error'] = 'Limite diário de cash-out não pode ser negativo';
            header('Location: /admin/sellers/view/' . $sellerId);
            exit;
        }

        $updateData = [
            'min_cashin_amount' => $minCashinAmount,
            'max_cashin_amount' => $maxCashinAmount,
            'min_cashout_amount' => $minCashoutAmount,
            'max_cashout_amount' => $maxCashoutAmount,
            'cashout_daily_limit' => $cashoutDailyLimit
        ];

        $this->sellerModel->update($sellerId, $updateData);

        $this->logModel->create([
            'level' => 'info',
            'category' => 'admin',
            'message' => 'Limites de transação atualizados para seller ID ' . $sellerId,
            'context' => json_encode([
                'seller_id' => $sellerId,
                'min_cashin_amount' => $minCashinAmount,
                'max_cashin_amount' => $maxCashinAmount,
                'min_cashout_amount' => $minCashoutAmount,
                'max_cashout_amount' => $maxCashoutAmount,
                'cashout_daily_limit' => $cashoutDailyLimit,
                'updated_by' => $_SESSION['user_id']
            ]),
            'user_id' => $_SESSION['user_id']
        ]);

        $_SESSION['success'] = 'Limites atualizados com sucesso!';
        header('Location: /admin/sellers/view/' . $sellerId);
        exit;
    }

    public function reports() {
        $period = $_GET['period'] ?? '7days';

        $dateFrom = match($period) {
            '7days' => date('Y-m-d', strtotime('-7 days')),
            '30days' => date('Y-m-d', strtotime('-30 days')),
            '90days' => date('Y-m-d', strtotime('-90 days')),
            default => date('Y-m-d', strtotime('-7 days'))
        };

        $cashinStats = $this->cashinModel->getStats($dateFrom);
        $cashoutStats = $this->cashoutModel->getStats($dateFrom);
        $dailyStats = $this->cashinModel->getDailyStats($dateFrom);
        $topSellers = $this->sellerModel->getTopSellers(10, $dateFrom);

        $stats = [
            'period' => $period,
            'date_from' => $dateFrom,
            'total_sellers' => $this->sellerModel->count(['status' => 'active']),
            'cashin' => $cashinStats,
            'cashout' => $cashoutStats,
            'daily' => $dailyStats,
            'top_sellers' => $topSellers,
            'total_revenue' => ($cashinStats['total_fees'] ?? 0) + ($cashoutStats['total_fees'] ?? 0),
            'cashin_fees' => ($cashinStats['total_fees'] ?? 0),
            'cashout_fees' => ($cashoutStats['total_fees'] ?? 0),
            'total_transactions' => ($cashinStats['total_transactions'] ?? 0) + ($cashoutStats['total_transactions'] ?? 0),
            'total_volume' => ($cashinStats['total_volume'] ?? 0) + ($cashoutStats['total_volume'] ?? 0),
            'success_rate' => $this->calculateSuccessRate($cashinStats)
        ];

        require __DIR__ . '/../../views/admin/reports.php';
    }

    private function calculateSuccessRate($stats) {
        $total = ($stats['total_transactions'] ?? 0);
        if ($total == 0) return 0;
        $successful = ($stats['successful_transactions'] ?? 0);
        return round(($successful / $total) * 100, 2);
    }

    public function logs() {
        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $perPage = 50;
        $offset = ($page - 1) * $perPage;

        $level = $_GET['level'] ?? '';
        $category = $_GET['category'] ?? '';

        $conditions = [];
        if ($level) $conditions['level'] = $level;
        if ($category) $conditions['category'] = $category;

        if (!empty($conditions)) {
            $logs = $this->logModel->where($conditions, 'created_at DESC', $perPage, $offset);
        } else {
            $logs = $this->logModel->all('created_at DESC', $perPage, $offset);
        }

        require __DIR__ . '/../../views/admin/logs.php';
    }

    private function isAjaxRequest() {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    }

    public function toggleCashin($sellerId) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /admin/sellers/view/' . $sellerId);
            exit;
        }

        $seller = $this->sellerModel->find($sellerId);
        if (!$seller) {
            $_SESSION['error'] = 'Seller não encontrado';
            header('Location: /admin/sellers');
            exit;
        }

        $enabled = !$seller['cashin_enabled'];
        $this->sellerModel->update($sellerId, ['cashin_enabled' => $enabled]);

        $this->logModel->create([
            'level' => 'info',
            'category' => 'admin',
            'message' => ($enabled ? 'Cash-in ativado' : 'Cash-in desativado') . ' para seller ID ' . $sellerId,
            'context' => json_encode([
                'seller_id' => $sellerId,
                'cashin_enabled' => $enabled,
                'changed_by' => $_SESSION['user_id']
            ])
        ]);

        $_SESSION['success'] = 'Cash-in ' . ($enabled ? 'ativado' : 'desativado') . ' com sucesso!';
        header('Location: /admin/sellers/view/' . $sellerId);
        exit;
    }

    public function toggleCashout($sellerId) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /admin/sellers/view/' . $sellerId);
            exit;
        }

        $seller = $this->sellerModel->find($sellerId);
        if (!$seller) {
            $_SESSION['error'] = 'Seller não encontrado';
            header('Location: /admin/sellers');
            exit;
        }

        $enabled = !$seller['cashout_enabled'];
        $this->sellerModel->update($sellerId, ['cashout_enabled' => $enabled]);

        $this->logModel->create([
            'level' => 'info',
            'category' => 'admin',
            'message' => ($enabled ? 'Cash-out ativado' : 'Cash-out desativado') . ' para seller ID ' . $sellerId,
            'context' => json_encode([
                'seller_id' => $sellerId,
                'cashout_enabled' => $enabled,
                'changed_by' => $_SESSION['user_id']
            ])
        ]);

        $_SESSION['success'] = 'Cash-out ' . ($enabled ? 'ativado' : 'desativado') . ' com sucesso!';
        header('Location: /admin/sellers/view/' . $sellerId);
        exit;
    }

    public function blockSeller($sellerId) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /admin/sellers/view/' . $sellerId);
            exit;
        }

        $seller = $this->sellerModel->find($sellerId);
        if (!$seller) {
            $_SESSION['error'] = 'Seller não encontrado';
            header('Location: /admin/sellers');
            exit;
        }

        $blockType = $_POST['block_type'] ?? 'temporary';
        $reason = $_POST['reason'] ?? '';

        $updateData = [
            'blocked_reason' => $reason,
            'blocked_at' => date('Y-m-d H:i:s'),
            'blocked_by' => $_SESSION['user_id']
        ];

        if ($blockType === 'permanent') {
            $updateData['permanently_blocked'] = true;
            $updateData['temporarily_blocked'] = false;
            $updateData['status'] = 'blocked';
        } else {
            $updateData['temporarily_blocked'] = true;
            $updateData['permanently_blocked'] = false;
        }

        $this->sellerModel->update($sellerId, $updateData);

        $this->logModel->create([
            'level' => 'warning',
            'category' => 'admin',
            'message' => 'Seller bloqueado ' . ($blockType === 'permanent' ? 'permanentemente' : 'temporariamente'),
            'context' => json_encode([
                'seller_id' => $sellerId,
                'block_type' => $blockType,
                'reason' => $reason,
                'blocked_by' => $_SESSION['user_id']
            ])
        ]);

        $this->notificationService->notifySellerBlocked($sellerId, $blockType, $reason);

        $_SESSION['success'] = 'Seller bloqueado com sucesso!';
        header('Location: /admin/sellers/view/' . $sellerId);
        exit;
    }

    public function unblockSeller($sellerId) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /admin/sellers/view/' . $sellerId);
            exit;
        }

        $seller = $this->sellerModel->find($sellerId);
        if (!$seller) {
            $_SESSION['error'] = 'Seller não encontrado';
            header('Location: /admin/sellers');
            exit;
        }

        $this->sellerModel->update($sellerId, [
            'temporarily_blocked' => false,
            'permanently_blocked' => false,
            'blocked_reason' => null,
            'blocked_at' => null,
            'blocked_by' => null,
            'status' => 'active'
        ]);

        $this->logModel->create([
            'level' => 'info',
            'category' => 'admin',
            'message' => 'Seller desbloqueado',
            'context' => json_encode([
                'seller_id' => $sellerId,
                'unblocked_by' => $_SESSION['user_id']
            ])
        ]);

        $_SESSION['success'] = 'Seller desbloqueado com sucesso!';
        header('Location: /admin/sellers/view/' . $sellerId);
        exit;
    }

    public function settings() {
        $settings = $this->settingsModel->getSettings();

        if (!$settings) {
            // Cria configuração padrão se não existir
            $this->settingsModel->create([
                'default_fee_percentage_cashin' => 0,
                'default_fee_fixed_cashin' => 0,
                'default_fee_percentage_cashout' => 0,
                'default_fee_fixed_cashout' => 0
            ]);
            $settings = $this->settingsModel->getSettings();
        }

        require __DIR__ . '/../../views/admin/settings.php';
    }

    public function updateSettings() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /admin/settings');
            exit;
        }

        $feePercentageCashin = $this->parseDecimal($_POST['default_fee_percentage_cashin'] ?? 0) / 100;
        $feeFixedCashin = $this->parseDecimal($_POST['default_fee_fixed_cashin'] ?? 0);
        $feePercentageCashout = $this->parseDecimal($_POST['default_fee_percentage_cashout'] ?? 0) / 100;
        $feeFixedCashout = $this->parseDecimal($_POST['default_fee_fixed_cashout'] ?? 0);

        $this->settingsModel->updateSettings([
            'default_fee_percentage_cashin' => $feePercentageCashin,
            'default_fee_fixed_cashin' => $feeFixedCashin,
            'default_fee_percentage_cashout' => $feePercentageCashout,
            'default_fee_fixed_cashout' => $feeFixedCashout,
            'updated_at' => date('Y-m-d H:i:s'),
            'updated_by' => $_SESSION['user_id']
        ]);

        $this->logModel->create([
            'level' => 'info',
            'category' => 'admin',
            'message' => 'Configurações do sistema atualizadas',
            'context' => json_encode([
                'default_fee_percentage_cashin' => $feePercentageCashin,
                'default_fee_fixed_cashin' => $feeFixedCashin,
                'default_fee_percentage_cashout' => $feePercentageCashout,
                'default_fee_fixed_cashout' => $feeFixedCashout,
                'updated_by' => $_SESSION['user_id']
            ]),
            'user_id' => $_SESSION['user_id']
        ]);

        $_SESSION['success'] = 'Configurações atualizadas com sucesso!';
        header('Location: /admin/settings');
        exit;
    }

    public function getAcquirerAccounts($acquirerId) {
        header('Content-Type: application/json');

        $accounts = $this->accountModel->getByAcquirer($acquirerId);

        echo json_encode(['success' => true, 'accounts' => $accounts]);
        exit;
    }

    public function acquirerAccounts($acquirerId) {
        $acquirer = $this->acquirerModel->find($acquirerId);

        if (!$acquirer) {
            $_SESSION['error'] = 'Adquirente não encontrada';
            header('Location: /admin/acquirers');
            exit;
        }

        $accounts = $this->accountModel->getAccountsByAcquirer($acquirerId);

        require __DIR__ . '/../../views/admin/acquirer-accounts.php';
    }

    public function getAcquirerAccount($accountId) {
        header('Content-Type: application/json');

        $account = $this->accountModel->find($accountId);

        if (!$account) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Conta não encontrada']);
            exit;
        }

        echo json_encode(['success' => true, 'account' => $account]);
        exit;
    }

    public function createAcquirerAccount() {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Método não permitido']);
            exit;
        }

        try {
            $data = [
                'acquirer_id' => $_POST['acquirer_id'],
                'name' => $_POST['name'],
                'client_id' => $_POST['client_id'],
                'client_secret' => $_POST['client_secret'],
                'merchant_id' => $_POST['merchant_id'],
                'is_active' => isset($_POST['is_active']) && $_POST['is_active'] === 'on',
                'is_default' => isset($_POST['is_default']) && $_POST['is_default'] === 'on',
                'max_cashin_per_transaction' => isset($_POST['max_cashin_per_transaction']) && $_POST['max_cashin_per_transaction'] !== ''
                    ? $this->parseDecimal($_POST['max_cashin_per_transaction']) : null,
                'max_cashout_per_transaction' => isset($_POST['max_cashout_per_transaction']) && $_POST['max_cashout_per_transaction'] !== ''
                    ? $this->parseDecimal($_POST['max_cashout_per_transaction']) : null,
                'min_cashin_per_transaction' => isset($_POST['min_cashin_per_transaction']) && $_POST['min_cashin_per_transaction'] !== ''
                    ? $this->parseDecimal($_POST['min_cashin_per_transaction']) : 0.01,
                'min_cashout_per_transaction' => isset($_POST['min_cashout_per_transaction']) && $_POST['min_cashout_per_transaction'] !== ''
                    ? $this->parseDecimal($_POST['min_cashout_per_transaction']) : 0.01
            ];

            if ($data['is_default']) {
                $this->accountModel->unsetDefaultForAcquirer($data['acquirer_id']);
            }

            $accountId = $this->accountModel->create($data);

            $this->logModel->info('admin', 'Nova conta de adquirente criada', [
                'account_id' => $accountId,
                'acquirer_id' => $data['acquirer_id'],
                'name' => $data['name'],
                'admin_id' => $_SESSION['user_id']
            ]);

            echo json_encode(['success' => true, 'message' => 'Conta criada com sucesso!', 'account_id' => $accountId]);
        } catch (Exception $e) {
            error_log("Error creating acquirer account: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Erro ao criar conta: ' . $e->getMessage()]);
        }
        exit;
    }

    public function updateAcquirerAccount($accountId) {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Método não permitido']);
            exit;
        }

        try {
            $account = $this->accountModel->find($accountId);

            if (!$account) {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Conta não encontrada']);
                exit;
            }

            $data = [
                'name' => $_POST['name'],
                'client_id' => $_POST['client_id'],
                'merchant_id' => $_POST['merchant_id'],
                'is_active' => isset($_POST['is_active']) && $_POST['is_active'] === 'on',
                'is_default' => isset($_POST['is_default']) && $_POST['is_default'] === 'on'
            ];

            if (!empty($_POST['client_secret'])) {
                $data['client_secret'] = $_POST['client_secret'];
            }

            if (isset($_POST['max_cashin_per_transaction'])) {
                $data['max_cashin_per_transaction'] = $_POST['max_cashin_per_transaction'] !== ''
                    ? $this->parseDecimal($_POST['max_cashin_per_transaction']) : null;
            }

            if (isset($_POST['max_cashout_per_transaction'])) {
                $data['max_cashout_per_transaction'] = $_POST['max_cashout_per_transaction'] !== ''
                    ? $this->parseDecimal($_POST['max_cashout_per_transaction']) : null;
            }

            if (isset($_POST['min_cashin_per_transaction'])) {
                $data['min_cashin_per_transaction'] = $_POST['min_cashin_per_transaction'] !== ''
                    ? $this->parseDecimal($_POST['min_cashin_per_transaction']) : 0.01;
            }

            if (isset($_POST['min_cashout_per_transaction'])) {
                $data['min_cashout_per_transaction'] = $_POST['min_cashout_per_transaction'] !== ''
                    ? $this->parseDecimal($_POST['min_cashout_per_transaction']) : 0.01;
            }

            if ($data['is_default']) {
                $this->accountModel->unsetDefaultForAcquirer($account['acquirer_id']);
            }

            $this->accountModel->update($accountId, $data);

            $this->logModel->info('admin', 'Conta de adquirente atualizada', [
                'account_id' => $accountId,
                'name' => $data['name'],
                'admin_id' => $_SESSION['user_id']
            ]);

            echo json_encode(['success' => true, 'message' => 'Conta atualizada com sucesso!']);
        } catch (Exception $e) {
            error_log("Error updating acquirer account: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Erro ao atualizar conta: ' . $e->getMessage()]);
        }
        exit;
    }

    public function toggleAcquirerAccount($accountId) {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Método não permitido']);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $isActive = $input['is_active'] ?? true;

        $this->accountModel->update($accountId, ['is_active' => $isActive]);

        $this->logModel->info('admin', 'Status da conta alterado', [
            'account_id' => $accountId,
            'is_active' => $isActive,
            'admin_id' => $_SESSION['user_id']
        ]);

        echo json_encode(['success' => true, 'message' => 'Status atualizado com sucesso!']);
        exit;
    }

    public function resetAcquirerAccountLimit($accountId) {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Método não permitido']);
            exit;
        }

        try {
            $this->accountModel->update($accountId, [
                'daily_used_limit' => 0,
                'last_reset_date' => date('Y-m-d')
            ]);

            $this->logModel->info('admin', 'Limite diário resetado', [
                'account_id' => $accountId,
                'admin_id' => $_SESSION['user_id']
            ]);

            echo json_encode(['success' => true, 'message' => 'Limite diário resetado com sucesso!']);
        } catch (Exception $e) {
            error_log("Error resetting account limit: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Erro ao resetar limite: ' . $e->getMessage()]);
        }
        exit;
    }

    public function viewAcquirerAccountDetails($accountId) {
        $account = $this->accountModel->find($accountId);

        if (!$account) {
            header('Location: /admin/acquirers');
            exit;
        }

        $acquirer = $this->acquirerModel->find($account['acquirer_id']);

        $apiBalance = null;
        try {
            require_once __DIR__ . '/../../services/PodPayService.php';

            $accountWithAcquirerData = array_merge($account, [
                'api_url' => $acquirer['api_url'],
                'code' => $acquirer['code']
            ]);

            $podpayService = new PodPayService($accountWithAcquirerData);
            $balanceResult = $podpayService->getAvailableBalance();

            if ($balanceResult['success']) {
                $apiBalance = $balanceResult['data'];
            }
        } catch (Exception $e) {
            error_log("Error fetching balance: " . $e->getMessage());
        }

        $sql = "
            SELECT
                DATE(created_at) as date,
                COUNT(*) as total_transactions,
                SUM(CASE WHEN status IN ('approved', 'paid') THEN 1 ELSE 0 END) as successful_transactions,
                SUM(CASE WHEN status IN ('approved', 'paid') THEN amount ELSE 0 END) as total_volume
            FROM pix_cashin
            WHERE acquirer_account_id = ?
                AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY DATE(created_at)
            ORDER BY date DESC
        ";

        $dailyStats = $this->cashinModel->query($sql, [$accountId]);

        $recentTransactionsSql = "
            SELECT
                pc.*,
                s.name as seller_name,
                s.email as seller_email
            FROM pix_cashin pc
            LEFT JOIN sellers s ON pc.seller_id = s.id
            WHERE pc.acquirer_account_id = ?
            ORDER BY pc.created_at DESC
            LIMIT 50
        ";

        $recentTransactions = $this->cashinModel->query($recentTransactionsSql, [$accountId]);

        $statsSql = "
            SELECT
                COUNT(*) as total_transactions,
                SUM(CASE WHEN status IN ('approved', 'paid') THEN 1 ELSE 0 END) as successful_transactions,
                SUM(CASE WHEN status IN ('failed', 'cancelled', 'expired') THEN 1 ELSE 0 END) as failed_transactions,
                SUM(CASE WHEN status IN ('approved', 'paid') THEN amount ELSE 0 END) as total_volume,
                AVG(CASE WHEN status IN ('approved', 'paid') THEN amount ELSE NULL END) as avg_transaction_value,
                MAX(CASE WHEN status IN ('approved', 'paid') THEN amount ELSE NULL END) as max_transaction_value,
                MIN(CASE WHEN status IN ('approved', 'paid') THEN amount ELSE NULL END) as min_transaction_value
            FROM pix_cashin
            WHERE acquirer_account_id = ?
        ";

        $statsResult = $this->cashinModel->query($statsSql, [$accountId]);
        $stats = $statsResult[0] ?? [];

        require_once __DIR__ . '/../../views/admin/acquirer-account-details.php';
    }

    private function getAccountStatistics() {
        $sql = "
            SELECT
                aa.id,
                aa.name as account_name,
                aa.is_active,
                aa.balance,
                COUNT(DISTINCT pc.id) as transaction_count,
                COALESCE(SUM(pc.amount), 0) as total_volume,
                COALESCE(AVG(CASE WHEN pc.status IN ('approved', 'paid') THEN 100 ELSE 0 END), 0) as success_rate
            FROM acquirer_accounts aa
            LEFT JOIN pix_cashin pc ON pc.acquirer_account_id = aa.id
                AND pc.created_at >= CURRENT_DATE
            WHERE aa.is_active = 1
            GROUP BY aa.id, aa.name, aa.is_active, aa.balance
            ORDER BY total_volume DESC
            LIMIT 6
        ";

        return $this->accountModel->query($sql);
    }

    public function getSellerAcquirerAccounts($sellerId) {
        header('Content-Type: application/json');

        $accounts = $this->sellerAccountModel->getBySellerWithDetails($sellerId);

        echo json_encode(['success' => true, 'accounts' => $accounts]);
        exit;
    }

    public function assignAccountToSeller($sellerId) {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Método não permitido']);
            exit;
        }

        $data = json_decode(file_get_contents('php://input'), true);

        $accountId = $data['account_id'] ?? null;
        $priority = $data['priority'] ?? 1;
        $isActive = $data['is_active'] ?? true;

        if (!$accountId) {
            echo json_encode(['success' => false, 'error' => 'Account ID é obrigatório']);
            exit;
        }

        $result = $this->sellerAccountModel->assignAccountToSeller($sellerId, $accountId, $priority, $isActive);

        if ($result) {
            $this->logModel->info('admin', 'Conta atribuída ao seller', [
                'seller_id' => $sellerId,
                'account_id' => $accountId,
                'priority' => $priority,
                'admin_id' => $_SESSION['user_id']
            ]);

            echo json_encode(['success' => true, 'message' => 'Conta atribuída com sucesso!']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Falha ao atribuir conta']);
        }
        exit;
    }

    public function removeAccountFromSeller($sellerId, $accountId) {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Método não permitido']);
            exit;
        }

        $result = $this->sellerAccountModel->removeAccountFromSeller($sellerId, $accountId);

        if ($result) {
            $this->logModel->info('admin', 'Conta removida do seller', [
                'seller_id' => $sellerId,
                'account_id' => $accountId,
                'admin_id' => $_SESSION['user_id']
            ]);

            echo json_encode(['success' => true, 'message' => 'Conta removida com sucesso!']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Falha ao remover conta']);
        }
        exit;
    }

    public function updateSellerAccountPriority() {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Método não permitido']);
            exit;
        }

        $data = json_decode(file_get_contents('php://input'), true);

        $sellerId = $data['seller_id'] ?? null;
        $accountIds = $data['account_ids'] ?? [];

        if (!$sellerId || empty($accountIds)) {
            echo json_encode(['success' => false, 'error' => 'Dados inválidos']);
            exit;
        }

        $result = $this->sellerAccountModel->reorderPriorities($sellerId, $accountIds);

        if ($result) {
            $this->logModel->info('admin', 'Prioridades das contas atualizadas', [
                'seller_id' => $sellerId,
                'account_ids' => $accountIds,
                'admin_id' => $_SESSION['user_id']
            ]);

            echo json_encode(['success' => true, 'message' => 'Prioridades atualizadas com sucesso!']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Falha ao atualizar prioridades']);
        }
        exit;
    }

    public function toggleSellerAccountStatus($sellerId, $accountId) {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Método não permitido']);
            exit;
        }

        $sellerAccount = $this->sellerAccountModel->where([
            'seller_id' => $sellerId,
            'acquirer_account_id' => $accountId
        ]);

        if (empty($sellerAccount)) {
            echo json_encode(['success' => false, 'error' => 'Associação não encontrada']);
            exit;
        }

        $result = $this->sellerAccountModel->toggleActive($sellerAccount[0]['id']);

        if ($result) {
            $this->logModel->info('admin', 'Status da conta do seller alterado', [
                'seller_id' => $sellerId,
                'account_id' => $accountId,
                'admin_id' => $_SESSION['user_id']
            ]);

            echo json_encode(['success' => true, 'message' => 'Status atualizado com sucesso!']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Falha ao atualizar status']);
        }
        exit;
    }

    public function getAvailableAccounts() {
        header('Content-Type: application/json');

        try {
            $accounts = $this->accountModel->query("
                SELECT aa.id, aa.name as account_name, aa.account_identifier,
                       a.name as acquirer_name, a.code as acquirer_code
                FROM acquirer_accounts aa
                JOIN acquirers a ON a.id = aa.acquirer_id
                WHERE aa.is_active = 1 AND a.status = 'active'
                ORDER BY a.name, aa.name
            ");

            echo json_encode(['success' => true, 'accounts' => $accounts]);
        } catch (Exception $e) {
            error_log("Error in getAvailableAccounts: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }

}
