<?php

require_once __DIR__ . '/../../middleware/CheckSellerActive.php';
require_once __DIR__ . '/../../models/Seller.php';
require_once __DIR__ . '/../../models/SellerDocument.php';
require_once __DIR__ . '/../../models/PixCashin.php';
require_once __DIR__ . '/../../models/PixCashout.php';
require_once __DIR__ . '/../../models/Notification.php';
require_once __DIR__ . '/../../services/FileUploadService.php';

class SellerController {
    private $sellerModel;
    private $documentModel;
    private $cashinModel;
    private $cashoutModel;
    private $notificationModel;
    private $fileUploadService;
    private $sellerId;

    public function __construct() {
        CheckSellerActive::handle();

        $this->sellerModel = new Seller();
        $this->documentModel = new SellerDocument();
        $this->cashinModel = new PixCashin();
        $this->cashoutModel = new PixCashout();
        $this->notificationModel = new Notification();
        $this->fileUploadService = new FileUploadService();
        $this->sellerId = CheckAuth::sellerId();
    }

    public function dashboard() {
        $seller = $this->sellerModel->find($this->sellerId);

        $stats = [
            'balance' => $seller['balance'],
            'total_cashin' => $this->cashinModel->getTotalAmountBySeller($this->sellerId),
            'total_cashin_net' => $this->cashinModel->getTotalNetAmountBySeller($this->sellerId),
            'total_cashout' => $this->cashoutModel->getTotalAmountBySeller($this->sellerId),
            'pending_cashin' => $this->cashinModel->countByStatus($this->sellerId, 'waiting_payment'),
            'approved_cashin' => $this->cashinModel->countByStatus($this->sellerId, 'approved'),
        ];

        $recentCashin = $this->cashinModel->getRecentBySeller($this->sellerId, 10);
        $recentCashout = $this->cashoutModel->getRecentBySeller($this->sellerId, 10);
        $notifications = $this->notificationModel->getRecentBySeller($this->sellerId, 5);

        require __DIR__ . '/../../views/seller/dashboard.php';
    }

    public function documents() {
        $seller = $this->sellerModel->find($this->sellerId);
        $documents = $this->documentModel->getDocumentsBySeller($this->sellerId);
        $requiredDocs = $this->documentModel->getRequiredDocumentTypes($seller['person_type']);

        require __DIR__ . '/../../views/seller/documents.php';
    }

    public function uploadDocument() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /seller/documents');
            exit;
        }

        $documentType = $_POST['document_type'] ?? '';

        if (empty($documentType) || !isset($_FILES['document'])) {
            $_SESSION['error'] = 'Tipo de documento e arquivo são obrigatórios';
            header('Location: /seller/documents');
            exit;
        }

        $result = $this->fileUploadService->uploadDocument(
            $_FILES['document'],
            $this->sellerId,
            $documentType
        );

        if ($result['success']) {
            $_SESSION['success'] = 'Documento enviado com sucesso!';
        } else {
            $_SESSION['error'] = $result['error'];
        }

        header('Location: /seller/documents');
        exit;
    }

    public function profile() {
        $seller = $this->sellerModel->find($this->sellerId);
        require __DIR__ . '/../../views/seller/profile.php';
    }

    public function updateProfile() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /seller/profile');
            exit;
        }

        $allowedFields = ['phone'];
        $updateData = [];

        foreach ($allowedFields as $field) {
            if (isset($_POST[$field])) {
                $updateData[$field] = trim($_POST[$field]);
            }
        }

        if (!empty($updateData)) {
            $this->sellerModel->update($this->sellerId, $updateData);
            $_SESSION['success'] = 'Perfil atualizado com sucesso!';
        }

        header('Location: /seller/profile');
        exit;
    }

    public function webhooks() {
        $seller = $this->sellerModel->find($this->sellerId);
        require __DIR__ . '/../../views/seller/webhooks.php';
    }

    public function updateWebhooks() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /seller/webhooks');
            exit;
        }

        $allowedFields = ['webhook_url', 'webhook_secret'];
        $updateData = [];

        foreach ($allowedFields as $field) {
            if (isset($_POST[$field])) {
                $updateData[$field] = trim($_POST[$field]);
            }
        }

        if (!empty($updateData)) {
            $this->sellerModel->update($this->sellerId, $updateData);
            $_SESSION['success'] = 'Configurações de webhook atualizadas com sucesso!';
        }

        header('Location: /seller/webhooks');
        exit;
    }

    public function transactions() {
        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $perPage = 20;
        $offset = ($page - 1) * $perPage;

        $type = $_GET['type'] ?? 'all';
        $status = $_GET['status'] ?? '';
        $search = $_GET['search'] ?? '';

        if (!empty($search)) {
            if ($type === 'cashin' || $type === 'all') {
                $cashin = $this->cashinModel->searchBySeller($this->sellerId, $search, $status, $perPage, $offset);
            } else {
                $cashin = [];
            }

            if ($type === 'cashout' || $type === 'all') {
                $cashout = $this->cashoutModel->searchBySeller($this->sellerId, $search, $status, $perPage, $offset);
            } else {
                $cashout = [];
            }
        } else {
            if ($type === 'cashin' || $type === 'all') {
                $cashin = $this->cashinModel->getBySeller($this->sellerId, $status, $perPage, $offset);
            } else {
                $cashin = [];
            }

            if ($type === 'cashout' || $type === 'all') {
                $cashout = $this->cashoutModel->getBySeller($this->sellerId, $status, $perPage, $offset);
            } else {
                $cashout = [];
            }
        }

        require __DIR__ . '/../../views/seller/transactions.php';
    }

    public function transactionDetails($transactionId, $type) {
        if ($type === 'cashin') {
            $transaction = $this->cashinModel->find($transactionId);
        } else {
            $transaction = $this->cashoutModel->find($transactionId);
        }

        if (!$transaction || $transaction['seller_id'] != $this->sellerId) {
            $_SESSION['error'] = 'Transação não encontrada';
            header('Location: /seller/transactions');
            exit;
        }

        require __DIR__ . '/../../views/seller/transaction-details.php';
    }

    public function apiCredentials() {
        $seller = $this->sellerModel->find($this->sellerId);

        if ($seller['status'] !== 'active') {
            $_SESSION['error'] = 'Sua conta ainda não está ativa. Aguarde a aprovação.';
            header('Location: /seller/dashboard');
            exit;
        }

        require __DIR__ . '/../../views/seller/api-credentials.php';
    }

    public function regenerateApiKey() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /seller/api-credentials');
            exit;
        }

        $seller = $this->sellerModel->find($this->sellerId);

        if ($seller['status'] !== 'active') {
            $_SESSION['error'] = 'Sua conta não está ativa';
            header('Location: /seller/dashboard');
            exit;
        }

        $newApiSecret = bin2hex(random_bytes(32));
        $hashedSecret = hash('sha256', $newApiSecret);

        $this->sellerModel->update($this->sellerId, [
            'api_secret' => $hashedSecret
        ]);

        $_SESSION['new_api_secret'] = $newApiSecret;
        $_SESSION['success'] = 'Novo API Secret gerado com sucesso! Anote o secret, ele não será mostrado novamente.';

        header('Location: /seller/api-credentials');
        exit;
    }

    public function notifications() {
        $notifications = $this->notificationModel->getRecentBySeller($this->sellerId, 50);
        require __DIR__ . '/../../views/seller/notifications.php';
    }

    public function markNotificationAsRead($notificationId) {
        $notification = $this->notificationModel->find($notificationId);

        if ($notification && $notification['seller_id'] == $this->sellerId) {
            $this->notificationModel->markAsRead($notificationId);
        }

        header('Location: /seller/notifications');
        exit;
    }
}
