<?php

require_once __DIR__ . '/../../middleware/CheckAdmin.php';
require_once __DIR__ . '/../../models/Seller.php';
require_once __DIR__ . '/../../models/SellerDocument.php';
require_once __DIR__ . '/../../models/PixCashin.php';
require_once __DIR__ . '/../../models/PixCashout.php';
require_once __DIR__ . '/../../models/Acquirer.php';
require_once __DIR__ . '/../../models/AcquirerAccount.php';
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
            'approved_at' => date('Y-m-d H:i:s'),
            'daily_reset_at' => date('Y-m-d')
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
        $dailyLimit = floatval($_POST['daily_limit'] ?? 100000.00);

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
            'daily_limit' => $dailyLimit,
            'daily_used' => 0,
            'daily_reset_at' => date('Y-m-d'),
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
        $dailyLimit = floatval($_POST['daily_limit'] ?? 100000.00);

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
            'daily_limit' => $dailyLimit,
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

    public function resetAcquirerLimit($acquirerId) {
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

        $this->acquirerModel->update($acquirerId, [
            'daily_used' => 0,
            'daily_reset_at' => date('Y-m-d')
        ]);

        $this->logModel->info('admin', 'Limite diário da adquirente resetado', [
            'acquirer_id' => $acquirerId,
            'admin_id' => $_SESSION['user_id']
        ]);

        echo json_encode(['success' => true, 'message' => 'Limite diário resetado!']);
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

    public function createAcquirerAccount($acquirerId) {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Método não permitido']);
            exit;
        }

        $name = trim($_POST['name'] ?? '');
        $clientId = trim($_POST['client_id'] ?? '');
        $clientSecret = trim($_POST['client_secret'] ?? '');
        $merchantId = trim($_POST['merchant_id'] ?? '');
        $balance = floatval($_POST['balance'] ?? 0);
        $isActive = isset($_POST['is_active']) ? (bool)$_POST['is_active'] : true;

        if (empty($name) || empty($clientId) || empty($clientSecret) || empty($merchantId)) {
            echo json_encode(['success' => false, 'error' => 'Todos os campos são obrigatórios']);
            exit;
        }

        $data = [
            'acquirer_id' => $acquirerId,
            'name' => $name,
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'merchant_id' => $merchantId,
            'balance' => $balance,
            'is_active' => $isActive
        ];

        $accountId = $this->accountModel->create($data);

        $this->logModel->info('admin', 'Conta de adquirente criada', [
            'account_id' => $accountId,
            'acquirer_id' => $acquirerId,
            'name' => $name,
            'admin_id' => $_SESSION['user_id']
        ]);

        echo json_encode(['success' => true, 'message' => 'Conta criada com sucesso!', 'id' => $accountId]);
        exit;
    }

    public function updateAcquirerAccount($accountId) {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Método não permitido']);
            exit;
        }

        $account = $this->accountModel->find($accountId);
        if (!$account) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Conta não encontrada']);
            exit;
        }

        $name = trim($_POST['name'] ?? '');
        $clientId = trim($_POST['client_id'] ?? '');
        $clientSecret = trim($_POST['client_secret'] ?? '');
        $merchantId = trim($_POST['merchant_id'] ?? '');
        $balance = floatval($_POST['balance'] ?? 0);
        $isActive = isset($_POST['is_active']) ? (bool)$_POST['is_active'] : true;

        if (empty($name) || empty($clientId) || empty($clientSecret) || empty($merchantId)) {
            echo json_encode(['success' => false, 'error' => 'Todos os campos são obrigatórios']);
            exit;
        }

        $data = [
            'name' => $name,
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'merchant_id' => $merchantId,
            'balance' => $balance,
            'is_active' => $isActive,
            'updated_at' => date('Y-m-d H:i:s')
        ];

        $this->accountModel->update($accountId, $data);

        $this->logModel->info('admin', 'Conta de adquirente atualizada', [
            'account_id' => $accountId,
            'name' => $name,
            'admin_id' => $_SESSION['user_id']
        ]);

        echo json_encode(['success' => true, 'message' => 'Conta atualizada com sucesso!']);
        exit;
    }

    public function deleteAcquirerAccount($accountId) {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Método não permitido']);
            exit;
        }

        $account = $this->accountModel->find($accountId);
        if (!$account) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Conta não encontrada']);
            exit;
        }

        $this->accountModel->delete($accountId);

        $this->logModel->info('admin', 'Conta de adquirente excluída', [
            'account_id' => $accountId,
            'admin_id' => $_SESSION['user_id']
        ]);

        echo json_encode(['success' => true, 'message' => 'Conta excluída com sucesso!']);
        exit;
    }

    public function getSellerAcquirerAccounts($sellerId) {
        header('Content-Type: application/json');

        $accounts = $this->accountModel->getAccountsBySellerWithStats($sellerId);

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

        $accountId = intval($_POST['account_id'] ?? 0);
        $priority = intval($_POST['priority'] ?? 1);
        $strategy = $_POST['strategy'] ?? 'priority_only';
        $percentage = intval($_POST['percentage'] ?? 0);

        if (!$accountId) {
            echo json_encode(['success' => false, 'error' => 'Conta é obrigatória']);
            exit;
        }

        $this->accountModel->assignToSeller($sellerId, $accountId, $priority, $strategy, $percentage);

        $this->logModel->info('admin', 'Conta atribuída ao seller', [
            'seller_id' => $sellerId,
            'account_id' => $accountId,
            'priority' => $priority,
            'strategy' => $strategy,
            'admin_id' => $_SESSION['user_id']
        ]);

        echo json_encode(['success' => true, 'message' => 'Conta atribuída com sucesso!']);
        exit;
    }

    public function removeAccountFromSeller($sellerId, $accountId) {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Método não permitido']);
            exit;
        }

        $this->accountModel->removeFromSeller($sellerId, $accountId);

        $this->logModel->info('admin', 'Conta removida do seller', [
            'seller_id' => $sellerId,
            'account_id' => $accountId,
            'admin_id' => $_SESSION['user_id']
        ]);

        echo json_encode(['success' => true, 'message' => 'Conta removida com sucesso!']);
        exit;
    }

    public function toggleSellerAccountStatus($sellerId, $accountId) {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Método não permitido']);
            exit;
        }

        $isActive = isset($_POST['is_active']) ? (bool)$_POST['is_active'] : true;

        $this->accountModel->toggleSellerAccountStatus($sellerId, $accountId, $isActive);

        $this->logModel->info('admin', 'Status da conta do seller alterado', [
            'seller_id' => $sellerId,
            'account_id' => $accountId,
            'is_active' => $isActive,
            'admin_id' => $_SESSION['user_id']
        ]);

        echo json_encode(['success' => true, 'message' => 'Status atualizado com sucesso!']);
        exit;
    }

}
