<?php

require_once __DIR__ . '/../../services/AuthService.php';
require_once __DIR__ . '/../../services/AntiFraudService.php';
require_once __DIR__ . '/../../services/AcquirerService.php';
require_once __DIR__ . '/../../services/WebhookService.php';
require_once __DIR__ . '/../../models/PixCashout.php';
require_once __DIR__ . '/../../models/Seller.php';
require_once __DIR__ . '/../../models/Log.php';
require_once __DIR__ . '/../../models/SystemSettings.php';

class CashoutController {
    private $authService;
    private $antiFraudService;
    private $acquirerService;
    private $webhookService;
    private $pixCashoutModel;
    private $sellerModel;
    private $logModel;
    private $systemSettings;

    public function __construct() {
        $this->authService = new AuthService();
        $this->antiFraudService = new AntiFraudService();
        $this->acquirerService = new AcquirerService();
        $this->webhookService = new WebhookService();
        $this->pixCashoutModel = new PixCashout();
        $this->sellerModel = new Seller();
        $this->logModel = new Log();
        $this->systemSettings = new SystemSettings();
    }

    public function create() {
        $seller = $this->authService->authenticateApiRequest();
        $this->authService->checkRateLimit($seller['api_key'], '/api/cashout/create');

        $input = json_decode(file_get_contents('php://input'), true);

        if (!$input) {
            errorResponse('Invalid JSON payload', 400);
        }

        $required = ['amount', 'pix_key', 'pix_key_type', 'beneficiary_name', 'beneficiary_document'];
        foreach ($required as $field) {
            if (!isset($input[$field])) {
                errorResponse("Field '{$field}' is required", 400);
            }
        }

        $amount = (float) $input['amount'];
        $pixKey = $input['pix_key'];
        $pixKeyType = $input['pix_key_type'];
        $beneficiaryName = $input['beneficiary_name'];
        $beneficiaryDocument = sanitizeDocument($input['beneficiary_document']);

        if ($amount <= 0) {
            errorResponse('Amount must be greater than zero', 400);
        }

        if (!$this->antiFraudService->validatePixKey($pixKey, $pixKeyType)) {
            errorResponse('Invalid PIX key format', 400);
        }

        if (!validateCpfCnpj($beneficiaryDocument)) {
            errorResponse('Invalid beneficiary document', 400);
        }

        if (isset($input['external_id']) && !empty($input['external_id'])) {
            $existingTransaction = $this->pixCashoutModel->findByExternalId($seller['id'], $input['external_id']);
            if ($existingTransaction) {
                errorResponse('Duplicate external_id detected. A transaction with this external_id already exists', 409, [
                    'existing_transaction_id' => $existingTransaction['transaction_id'],
                    'existing_status' => $existingTransaction['status'],
                    'created_at' => $existingTransaction['created_at']
                ]);
            }
        }

        $duplicate = $this->pixCashoutModel->checkDuplicate($seller['id'], $pixKey, $beneficiaryDocument);
        if ($duplicate) {
            errorResponse('Duplicate transaction detected. There is already a pending or processing transaction with this PIX key or document', 409);
        }

        $settings = $this->systemSettings->getSettings();
        $feePercentage = $seller['fee_percentage_cashout'] ?? $settings['default_fee_percentage_cashout'] ?? 0;
        $feeFixed = $seller['fee_fixed_cashout'] ?? $settings['default_fee_fixed_cashout'] ?? 0;

        $feeAmount = calculateFee($amount, $feePercentage, $feeFixed);
        $totalAmount = $amount + $feeAmount;

        if ($seller['balance'] < $totalAmount) {
            errorResponse('Insufficient balance. Required: ' . $totalAmount . ', Available: ' . $seller['balance'], 400);
        }

        $this->sellerModel->updateBalance($seller['id'], -$totalAmount);

        $updatedSeller = $this->sellerModel->findById($seller['id']);

        $transactionId = generateTransactionId('CASHOUT');

        $acquirerData = [
            'transaction_id' => $transactionId,
            'amount' => $amount,
            'pix_key' => $pixKey,
            'pix_key_type' => $pixKeyType,
            'beneficiary_name' => $beneficiaryName,
            'beneficiary_document' => $beneficiaryDocument
        ];

        $acquirerResponse = $this->acquirerService->createPixCashoutWithFallback($seller['id'], $acquirerData);

        if (!$acquirerResponse['success']) {
            $this->sellerModel->updateBalance($seller['id'], $totalAmount);

            $this->logModel->error('api', 'PIX cashout failed with all accounts', [
                'seller_id' => $seller['id'],
                'transaction_id' => $transactionId,
                'amount' => $amount,
                'error' => $acquirerResponse['error']
            ]);

            errorResponse('Failed to create cashout transaction', 500, [
                'error' => $acquirerResponse['error']
            ]);
        }

        $accountId = $acquirerResponse['account_id'];

        $account = $this->acquirerService->getAccountForTransaction($accountId);
        if (!$account) {
            errorResponse('Account not found', 500);
        }

        $cashoutData = [
            'seller_id' => $seller['id'],
            'acquirer_id' => $account['acquirer_id'],
            'acquirer_account_id' => $accountId,
            'transaction_id' => $transactionId,
            'external_id' => $input['external_id'] ?? null,
            'amount' => $amount,
            'fee_amount' => $feeAmount,
            'net_amount' => $amount,
            'pix_key' => $pixKey,
            'pix_key_type' => $pixKeyType,
            'beneficiary_name' => $beneficiaryName,
            'beneficiary_document' => $beneficiaryDocument,
            'acquirer_transaction_id' => $acquirerResponse['data']['acquirer_transaction_id'] ?? null,
            'end_to_end_id' => $acquirerResponse['data']['end_to_end_id'] ?? null,
            'status' => 'processing',
            'metadata' => isset($input['metadata']) ? json_encode($input['metadata']) : null
        ];

        $cashoutId = $this->pixCashoutModel->create($cashoutData);

        $this->logModel->info('api', 'PIX cashout created', [
            'seller_id' => $seller['id'],
            'transaction_id' => $transactionId,
            'amount' => $amount,
            'new_balance' => $updatedSeller['balance']
        ]);

        $transaction = $this->pixCashoutModel->findByTransactionId($transactionId);

        $response = [
            'transaction_id' => $transactionId,
            'amount' => $amount,
            'fee_amount' => $feeAmount,
            'total_charged' => $totalAmount,
            'pix_key' => $pixKey,
            'beneficiary_name' => $beneficiaryName,
            'status' => $transaction['status']
        ];

        if (isset($input['external_id'])) {
            $response['external_id'] = $input['external_id'];
        }

        successResponse($response, 'Cashout transaction created successfully');
    }

    public function consult() {
        $seller = $this->authService->authenticateApiRequest();
        $this->authService->checkRateLimit($seller['api_key'], '/api/cashout/consult');

        $transactionId = $_GET['transaction_id'] ?? null;

        if (!$transactionId) {
            errorResponse('transaction_id is required', 400);
        }

        $transaction = $this->pixCashoutModel->findByTransactionId($transactionId);

        if (!$transaction) {
            errorResponse('Transaction not found', 404);
        }

        if ($transaction['seller_id'] != $seller['id']) {
            errorResponse('Unauthorized', 403);
        }

        $response = [
            'transaction_id' => $transaction['transaction_id'],
            'amount' => $transaction['amount'],
            'fee_amount' => $transaction['fee_amount'],
            'net_amount' => $transaction['net_amount'],
            'status' => $transaction['status'],
            'pix_key' => $transaction['pix_key'],
            'beneficiary_name' => $transaction['beneficiary_name'],
            'processed_at' => $transaction['processed_at'],
            'created_at' => $transaction['created_at']
        ];

        if (!empty($transaction['external_id'])) {
            $response['external_id'] = $transaction['external_id'];
        }

        successResponse($response);
    }

    public function listTransactions() {
        $seller = $this->authService->authenticateApiRequest();
        $this->authService->checkRateLimit($seller['api_key'], '/api/cashout/list');

        $filters = [
            'status' => $_GET['status'] ?? null,
            'start_date' => $_GET['start_date'] ?? null,
            'end_date' => $_GET['end_date'] ?? null,
            'limit' => min((int)($_GET['limit'] ?? 50), 100)
        ];

        $filters = array_filter($filters, function($value) {
            return $value !== null;
        });

        $transactions = $this->pixCashoutModel->getTransactionsBySeller($seller['id'], $filters);

        $response = array_map(function($tx) {
            return [
                'transaction_id' => $tx['transaction_id'],
                'amount' => $tx['amount'],
                'fee_amount' => $tx['fee_amount'],
                'net_amount' => $tx['net_amount'],
                'status' => $tx['status'],
                'beneficiary_name' => $tx['beneficiary_name'],
                'processed_at' => $tx['processed_at'],
                'created_at' => $tx['created_at']
            ];
        }, $transactions);

        successResponse(['transactions' => $response]);
    }
}
