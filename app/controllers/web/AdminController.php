<?php

require_once __DIR__ . '/../../middleware/CheckAdmin.php';
require_once __DIR__ . '/../../models/Seller.php';
require_once __DIR__ . '/../../models/SellerDocument.php';
require_once __DIR__ . '/../../models/PixCashin.php';
require_once __DIR__ . '/../../models/PixCashout.php';
require_once __DIR__ . '/../../models/Acquirer.php';
require_once __DIR__ . '/../../models/User.php';
require_once __DIR__ . '/../../models/Log.php';
require_once __DIR__ . '/../../services/NotificationService.php';
require_once __DIR__ . '/../../services/FileUploadService.php';

class AdminController {
    private $sellerModel;
    private $documentModel;
    private $cashinModel;
    private $cashoutModel;
    private $acquirerModel;
    private $userModel;
    private $logModel;
    private $notificationService;
    private $fileUploadService;

    public function __construct() {
        CheckAdmin::handle();

        $this->sellerModel = new Seller();
        $this->documentModel = new SellerDocument();
        $this->cashinModel = new PixCashin();
        $this->cashoutModel = new PixCashout();
        $this->acquirerModel = new Acquirer();
        $this->userModel = new User();
        $this->logModel = new Log();
        $this->notificationService = new NotificationService();
        $this->fileUploadService = new FileUploadService();
    }

    public function dashboard() {
        $stats = [
            'total_sellers' => $this->sellerModel->count(['status' => 'active']),
            'pending_sellers' => $this->sellerModel->count(['status' => 'pending']),
            'total_cashin' => $this->cashinModel->getTotalAmount(),
            'total_cashout' => $this->cashoutModel->getTotalAmount(),
            'pending_documents' => $this->documentModel->count(['status' => 'pending']),
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

        require __DIR__ . '/../../views/admin/seller-details.php';
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
            header('Location: /admin/documents');
            exit;
        }

        $this->documentModel->approveDocument($documentId, $_SESSION['user_id']);
        $this->notificationService->notifyDocumentApproved($document['seller_id'], $document['document_type']);

        $_SESSION['success'] = 'Documento aprovado!';
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
            header('Location: /admin/documents');
            exit;
        }

        $this->documentModel->rejectDocument($documentId, $_SESSION['user_id'], $reason);
        $this->notificationService->notifyDocumentRejected($document['seller_id'], $document['document_type'], $reason);

        $_SESSION['success'] = 'Documento rejeitado';
        header('Location: /admin/documents/view/' . $documentId);
        exit;
    }

    public function transactions() {
        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $perPage = 50;
        $offset = ($page - 1) * $perPage;

        $type = $_GET['type'] ?? 'all';
        $status = $_GET['status'] ?? '';

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

        require __DIR__ . '/../../views/admin/transactions.php';
    }

    public function acquirers() {
        $acquirers = $this->acquirerModel->all('priority_order ASC');
        require __DIR__ . '/../../views/admin/acquirers.php';
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
}
