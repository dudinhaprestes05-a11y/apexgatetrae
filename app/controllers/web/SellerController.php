<?php

require_once __DIR__ . '/../../middleware/CheckSellerActive.php';
require_once __DIR__ . '/../../models/Seller.php';
require_once __DIR__ . '/../../models/SellerDocument.php';
require_once __DIR__ . '/../../models/PixCashin.php';
require_once __DIR__ . '/../../models/PixCashout.php';
require_once __DIR__ . '/../../models/Notification.php';
require_once __DIR__ . '/../../models/SystemSettings.php';
require_once __DIR__ . '/../../services/FileUploadService.php';
require_once __DIR__ . '/../../services/AntiFraudService.php';
require_once __DIR__ . '/../../services/AcquirerService.php';

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

    public function personalInfo() {
        $seller = $this->sellerModel->find($this->sellerId);
        require __DIR__ . '/../../views/seller/personal-info.php';
    }

    public function savePersonalInfo() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /seller/personal-info');
            exit;
        }

        // Verificar se as informações já foram completadas
        $seller = $this->sellerModel->find($this->sellerId);
        if ($seller['personal_info_completed']) {
            $_SESSION['error'] = 'Suas informações pessoais já foram registradas e não podem ser alteradas. Entre em contato com o suporte se precisar fazer alterações.';
            header('Location: /seller/personal-info');
            exit;
        }

        $documentType = $_POST['personal_document_type'] ?? '';
        $birthDate = $_POST['birth_date'] ?? '';

        if (empty($documentType) || empty($birthDate)) {
            $_SESSION['error'] = 'Tipo de documento e data de nascimento são obrigatórios';
            header('Location: /seller/personal-info');
            exit;
        }

        $updateData = [
            'personal_document_type' => $documentType,
            'birth_date' => $birthDate,
            'address_zipcode' => trim($_POST['address_zipcode'] ?? ''),
            'address_street' => trim($_POST['address_street'] ?? ''),
            'address_number' => trim($_POST['address_number'] ?? ''),
            'address_complement' => trim($_POST['address_complement'] ?? ''),
            'address_neighborhood' => trim($_POST['address_neighborhood'] ?? ''),
            'address_city' => trim($_POST['address_city'] ?? ''),
            'address_state' => strtoupper(trim($_POST['address_state'] ?? '')),
            'personal_info_completed' => 1
        ];

        if ($documentType === 'rg') {
            $updateData['rg_number'] = trim($_POST['rg_number'] ?? '');
            $updateData['rg_issuer'] = trim($_POST['rg_issuer'] ?? '');
            $updateData['rg_issue_date'] = $_POST['rg_issue_date'] ?? null;

            if (empty($updateData['rg_number']) || empty($updateData['rg_issuer'])) {
                $_SESSION['error'] = 'Número do RG e órgão emissor são obrigatórios';
                header('Location: /seller/personal-info');
                exit;
            }
        } else {
            $updateData['cnh_number'] = trim($_POST['cnh_number'] ?? '');
            $updateData['cnh_expiry_date'] = $_POST['cnh_expiry_date'] ?? null;

            if (empty($updateData['cnh_number']) || empty($updateData['cnh_expiry_date'])) {
                $_SESSION['error'] = 'Número e data de validade da CNH são obrigatórios';
                header('Location: /seller/personal-info');
                exit;
            }
        }

        if (empty($updateData['address_zipcode']) || empty($updateData['address_street']) ||
            empty($updateData['address_number']) || empty($updateData['address_neighborhood']) ||
            empty($updateData['address_city']) || empty($updateData['address_state'])) {
            $_SESSION['error'] = 'Todos os campos de endereço são obrigatórios';
            header('Location: /seller/personal-info');
            exit;
        }

        $this->sellerModel->update($this->sellerId, $updateData);
        $_SESSION['success'] = 'Informações pessoais salvas com sucesso!';

        header('Location: /seller/documents');
        exit;
    }

    public function documents() {
        $seller = $this->sellerModel->find($this->sellerId);

        if (!$seller['personal_info_completed']) {
            $_SESSION['error'] = 'Complete suas informações pessoais antes de enviar os documentos';
            header('Location: /seller/personal-info');
            exit;
        }

        $documents = $this->documentModel->getDocumentsBySeller($this->sellerId);
        $requiredDocs = $this->documentModel->getRequiredDocumentTypes($seller['person_type'], $seller['personal_document_type'] ?? 'rg');

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

    public function ipWhitelist() {
        $seller = $this->sellerModel->find($this->sellerId);
        $whitelist = $this->sellerModel->getIpWhitelist($this->sellerId);
        $clientIp = getClientIp();

        require __DIR__ . '/../../views/seller/ip-whitelist.php';
    }

    public function getIpWhitelistJson() {
        header('Content-Type: application/json');
        $whitelist = $this->sellerModel->getIpWhitelist($this->sellerId);
        $seller = $this->sellerModel->find($this->sellerId);

        echo json_encode([
            'success' => true,
            'whitelist' => $whitelist,
            'enabled' => (bool)$seller['ip_whitelist_enabled']
        ]);
        exit;
    }

    public function addIpToWhitelist() {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'error' => 'Invalid request method']);
            exit;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $ip = trim($data['ip'] ?? '');
        $description = trim($data['description'] ?? '');

        if (empty($ip)) {
            echo json_encode(['success' => false, 'error' => 'IP address is required']);
            exit;
        }

        $result = $this->sellerModel->addIpToWhitelist($this->sellerId, $ip, $description);
        echo json_encode($result);
        exit;
    }

    public function removeIpFromWhitelist() {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'error' => 'Invalid request method']);
            exit;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $ip = trim($data['ip'] ?? '');

        if (empty($ip)) {
            echo json_encode(['success' => false, 'error' => 'IP address is required']);
            exit;
        }

        $result = $this->sellerModel->removeIpFromWhitelist($this->sellerId, $ip);
        echo json_encode($result);
        exit;
    }

    public function toggleIpWhitelist() {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'error' => 'Invalid request method']);
            exit;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $enabled = isset($data['enabled']) ? (bool)$data['enabled'] : false;

        $result = $this->sellerModel->toggleIpWhitelist($this->sellerId, $enabled);
        echo json_encode($result);
        exit;
    }

    public function cashout() {
        $seller = $this->sellerModel->find($this->sellerId);
        $systemSettings = new SystemSettings();
        $settings = $systemSettings->getSettings();

        $feePercentage = $seller['fee_percentage_cashout'] ?? $settings['default_fee_percentage_cashout'] ?? 0;
        $feeFixed = $seller['fee_fixed_cashout'] ?? $settings['default_fee_fixed_cashout'] ?? 0;

        $recentCashouts = $this->cashoutModel->getRecentBySeller($this->sellerId, 10);

        require __DIR__ . '/../../views/seller/cashout.php';
    }

    public function processCashout() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /seller/cashout');
            exit;
        }

        $seller = $this->sellerModel->find($this->sellerId);

        if ($seller['temporarily_blocked'] || $seller['permanently_blocked']) {
            $_SESSION['error'] = 'Sua conta está bloqueada e não pode realizar saques';
            header('Location: /seller/cashout');
            exit;
        }

        if (!$seller['cashout_enabled']) {
            $_SESSION['error'] = 'Saque não está habilitado para sua conta';
            header('Location: /seller/cashout');
            exit;
        }

        if ($seller['status'] !== 'active') {
            $_SESSION['error'] = 'Sua conta precisa estar ativa para realizar saques';
            header('Location: /seller/cashout');
            exit;
        }

        $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
        $pixKey = trim($_POST['pix_key'] ?? '');
        $pixKeyType = $_POST['pix_key_type'] ?? '';
        $beneficiaryName = trim($_POST['beneficiary_name'] ?? '');
        $beneficiaryDocument = sanitizeDocument($_POST['beneficiary_document'] ?? '');

        if ($amount <= 0) {
            $_SESSION['error'] = 'Valor do saque deve ser maior que zero';
            header('Location: /seller/cashout');
            exit;
        }

        $limitCheck = $this->sellerModel->checkTransactionLimits($seller['id'], $amount, 'cashout');
        if (!$limitCheck['valid']) {
            $_SESSION['error'] = $limitCheck['error'];
            header('Location: /seller/cashout');
            exit;
        }

        if (empty($pixKey) || empty($pixKeyType) || empty($beneficiaryName) || empty($beneficiaryDocument)) {
            $_SESSION['error'] = 'Todos os campos são obrigatórios';
            header('Location: /seller/cashout');
            exit;
        }

        $antiFraudService = new AntiFraudService();
        if (!$antiFraudService->validatePixKey($pixKey, $pixKeyType)) {
            $_SESSION['error'] = 'Chave PIX inválida para o tipo selecionado';
            header('Location: /seller/cashout');
            exit;
        }

        if (!validateCpfCnpj($beneficiaryDocument)) {
            $_SESSION['error'] = 'CPF/CNPJ do beneficiário inválido';
            header('Location: /seller/cashout');
            exit;
        }

        $systemSettings = new SystemSettings();
        $settings = $systemSettings->getSettings();
        $feePercentage = $seller['fee_percentage_cashout'] ?? $settings['default_fee_percentage_cashout'] ?? 0;
        $feeFixed = $seller['fee_fixed_cashout'] ?? $settings['default_fee_fixed_cashout'] ?? 0;

        $feeAmount = calculateFee($amount, $feePercentage, $feeFixed);
        $totalAmount = $amount + $feeAmount;

        if ($seller['balance'] < $totalAmount) {
            $_SESSION['error'] = 'Saldo insuficiente. Necessário: R$ ' . number_format($totalAmount, 2, ',', '.') . ', Disponível: R$ ' . number_format($seller['balance'], 2, ',', '.');
            header('Location: /seller/cashout');
            exit;
        }

        $duplicate = $this->cashoutModel->checkDuplicate($seller['id'], $pixKey, $beneficiaryDocument);
        if ($duplicate) {
            $_SESSION['error'] = 'Já existe uma transação de saque pendente ou em processamento com esta chave PIX ou documento';
            header('Location: /seller/cashout');
            exit;
        }

        $this->sellerModel->updateBalance($seller['id'], -$totalAmount);

        $transactionId = generateTransactionId('CASHOUT');

        $acquirerService = new AcquirerService();
        $acquirerData = [
            'transaction_id' => $transactionId,
            'amount' => $amount,
            'pix_key' => $pixKey,
            'pix_key_type' => $pixKeyType,
            'beneficiary_name' => $beneficiaryName,
            'beneficiary_document' => $beneficiaryDocument
        ];

        $acquirerResponse = $acquirerService->createPixCashoutWithFallback($seller['id'], $acquirerData);

        if (!$acquirerResponse['success']) {
            $this->sellerModel->updateBalance($seller['id'], $totalAmount);

            if ($acquirerResponse['account_id'] === null) {
                $_SESSION['error'] = 'Valor da transação excede o limite permitido';
            } else {
                $_SESSION['error'] = 'Falha ao processar o saque. Tente novamente mais tarde.';
            }

            header('Location: /seller/cashout');
            exit;
        }

        $accountId = $acquirerResponse['account_id'];
        $account = $acquirerService->getAccountForTransaction($accountId);

        if (!$account) {
            $this->sellerModel->updateBalance($seller['id'], $totalAmount);
            $_SESSION['error'] = 'Erro ao processar saque';
            header('Location: /seller/cashout');
            exit;
        }

        $cashoutData = [
            'seller_id' => $seller['id'],
            'acquirer_id' => $account['acquirer_id'],
            'acquirer_account_id' => $accountId,
            'transaction_id' => $transactionId,
            'amount' => $amount,
            'fee_amount' => $feeAmount,
            'net_amount' => $amount,
            'pix_key' => $pixKey,
            'pix_key_type' => $pixKeyType,
            'beneficiary_name' => $beneficiaryName,
            'beneficiary_document' => $beneficiaryDocument,
            'acquirer_transaction_id' => $acquirerResponse['data']['acquirer_transaction_id'] ?? null,
            'end_to_end_id' => $acquirerResponse['data']['end_to_end_id'] ?? null,
            'status' => 'processing'
        ];

        $this->cashoutModel->create($cashoutData);

        $_SESSION['success'] = 'Saque solicitado com sucesso! ID da transação: ' . $transactionId;
        header('Location: /seller/cashout');
        exit;
    }
}
