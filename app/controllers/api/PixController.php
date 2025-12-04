<?php

require_once __DIR__ . '/../../services/AuthService.php';
require_once __DIR__ . '/../../services/AntiFraudService.php';
require_once __DIR__ . '/../../services/AcquirerService.php';
require_once __DIR__ . '/../../services/SplitService.php';
require_once __DIR__ . '/../../services/WebhookService.php';
require_once __DIR__ . '/../../models/PixCashin.php';
require_once __DIR__ . '/../../models/Seller.php';
require_once __DIR__ . '/../../models/Acquirer.php';
require_once __DIR__ . '/../../models/Log.php';
require_once __DIR__ . '/../../models/SystemSettings.php';

class PixController {
    private $authService;
    private $antiFraudService;
    private $acquirerService;
    private $splitService;
    private $webhookService;
    private $pixCashinModel;
    private $sellerModel;
    private $acquirerModel;
    private $logModel;
    private $systemSettings;

    public function __construct() {
        $this->authService = new AuthService();
        $this->antiFraudService = new AntiFraudService();
        $this->acquirerService = new AcquirerService();
        $this->splitService = new SplitService();
        $this->webhookService = new WebhookService();
        $this->pixCashinModel = new PixCashin();
        $this->sellerModel = new Seller();
        $this->acquirerModel = new Acquirer();
        $this->logModel = new Log();
        $this->systemSettings = new SystemSettings();
    }

    public function create() {
        $seller = $this->authService->authenticateApiRequest();
        $this->authService->checkRateLimit($seller['api_key'], '/api/pix/create');

        $input = json_decode(file_get_contents('php://input'), true);

        if (!$input) {
            errorResponse('Invalid JSON payload', 400);
        }

        $required = ['amount'];
        foreach ($required as $field) {
            if (!isset($input[$field])) {
                errorResponse("Field '{$field}' is required", 400);
            }
        }

        $amount = (float) $input['amount'];

        if ($amount <= 0) {
            errorResponse('Amount must be greater than zero', 400);
        }

        if (!$this->sellerModel->checkDailyLimit($seller['id'], $amount)) {
            errorResponse('Daily limit exceeded', 403);
        }

        $fraudAnalysis = $this->antiFraudService->analyzeTransaction(
            $seller['id'],
            $amount,
            $input['payer_document'] ?? null
        );

        if (!$fraudAnalysis['approved']) {
            $this->logModel->warning('api', 'Transaction blocked by antifraud', [
                'seller_id' => $seller['id'],
                'amount' => $amount,
                'fraud_score' => $fraudAnalysis['score'],
                'risks' => $fraudAnalysis['risks']
            ]);

            errorResponse('Transaction blocked by security policies', 403, [
                'fraud_analysis' => $fraudAnalysis
            ]);
        }

        if (isset($input['external_id']) && !empty($input['external_id'])) {
            $existingTransaction = $this->pixCashinModel->findByExternalId($seller['id'], $input['external_id']);
            if ($existingTransaction) {
                errorResponse('Duplicate external_id detected. A transaction with this external_id already exists', 409, [
                    'existing_transaction_id' => $existingTransaction['transaction_id'],
                    'existing_status' => $existingTransaction['status'],
                    'created_at' => $existingTransaction['created_at']
                ]);
            }
        }

        if (isset($input['splits']) && !empty($input['splits'])) {
            $splitValidation = $this->splitService->validateSplits($amount, $input['splits']);
            if (!$splitValidation['valid']) {
                errorResponse($splitValidation['error'], 400);
            }
        }

        $settings = $this->systemSettings->getSettings();
        $feePercentage = $seller['fee_percentage_cashin'] ?? $settings['default_fee_percentage_cashin'] ?? 0;
        $feeFixed = $seller['fee_fixed_cashin'] ?? $settings['default_fee_fixed_cashin'] ?? 0;

        $feeAmount = calculateFee($amount, $feePercentage, $feeFixed);
        $netAmount = calculateNetAmount($amount, $feeAmount);

        $transactionId = generateTransactionId('CASHIN');

        $expiresInMinutes = $input['expires_in_minutes'] ?? PIX_EXPIRATION_MINUTES;
        $pixType = $input['pix_type'] ?? 'dynamic';

        $customerData = [
            'name' => $input['customer']['name'] ?? $seller['name'],
            'email' => $input['customer']['email'] ?? $seller['email'],
            'document' => $input['customer']['document'] ?? $seller['document']
        ];

        $acquirerData = [
            'transaction_id' => $transactionId,
            'amount' => $amount,
            'pix_type' => $pixType,
            'expires_at' => date('Y-m-d H:i:s', strtotime("+{$expiresInMinutes} minutes")),
            'metadata' => $input['metadata'] ?? [],
            'customer' => $customerData
        ];

        $acquirerResponse = $this->acquirerService->createPixCashinWithFallback($seller['id'], $acquirerData);

        if (!$acquirerResponse['success']) {
            $this->logModel->error('api', 'Failed to create PIX with all accounts', [
                'seller_id' => $seller['id'],
                'transaction_id' => $transactionId,
                'error' => $acquirerResponse['error']
            ]);

            errorResponse('Failed to create PIX transaction', 500, [
                'error' => $acquirerResponse['error']
            ]);
        }

        $accountId = $acquirerResponse['account_id'];

        $account = $this->acquirerService->getAccountForTransaction($accountId);
        if (!$account) {
            errorResponse('Account not found', 500);
        }

        try {
            Database::getInstance()->beginTransaction();

            $cashinData = [
                'seller_id' => $seller['id'],
                'acquirer_id' => $account['acquirer_id'],
                'acquirer_account_id' => $accountId,
                'transaction_id' => $transactionId,
                'external_id' => $input['external_id'] ?? null,
                'amount' => $amount,
                'fee_amount' => $feeAmount,
                'net_amount' => $netAmount,
                'pix_type' => $pixType,
                'metadata' => isset($input['metadata']) ? json_encode($input['metadata']) : null,
                'expires_in_minutes' => $expiresInMinutes,
                'acquirer_transaction_id' => $acquirerResponse['data']['acquirer_transaction_id'] ?? null,
                'qrcode' => $acquirerResponse['data']['qrcode'] ?? null,
                'qrcode_base64' => $acquirerResponse['data']['qrcode_base64'] ?? null,
                'customer_name' => $customerData['name'],
                'customer_document' => preg_replace('/[^0-9]/', '', $customerData['document']),
                'customer_email' => $customerData['email'],
                'status' => 'pending'
            ];

            $cashinId = $this->pixCashinModel->createTransaction($cashinData);

            if (isset($input['splits']) && !empty($input['splits'])) {
                $this->splitService->createSplits($cashinId, $netAmount, $input['splits']);
            }

            $this->sellerModel->incrementDailyUsed($seller['id'], $amount);

            Database::getInstance()->commit();

        } catch (Exception $e) {
            Database::getInstance()->rollback();

            $this->logModel->error('api', 'Failed to save transaction', [
                'transaction_id' => $transactionId,
                'error' => $e->getMessage()
            ]);

            errorResponse('Failed to save transaction', 500, [
                'error' => $e->getMessage()
            ]);
        }

        $this->logModel->info('api', 'PIX cashin created', [
            'seller_id' => $seller['id'],
            'transaction_id' => $transactionId,
            'amount' => $amount
        ]);

        $response = [
            'transaction_id' => $transactionId,
            'amount' => $amount,
            'fee_amount' => $feeAmount,
            'net_amount' => $netAmount,
            'qrcode' => $acquirerResponse['data']['qrcode'] ?? null,
            'qrcode_base64' => $acquirerResponse['data']['qrcode_base64'] ?? null,
            'expires_at' => $acquirerResponse['data']['expiration_date'] ?? date('Y-m-d H:i:s', strtotime("+{$expiresInMinutes} minutes")),
            'status' => $acquirerResponse['data']['status'] ?? 'pending'
        ];

        if (isset($input['external_id'])) {
            $response['external_id'] = $input['external_id'];
        }

        successResponse($response, 'PIX transaction created successfully');
    }

    public function consult() {
        $seller = $this->authService->authenticateApiRequest();
        $this->authService->checkRateLimit($seller['api_key'], '/api/pix/consult');

        $transactionId = $_GET['transaction_id'] ?? null;

        if (!$transactionId) {
            errorResponse('transaction_id is required', 400);
        }

        $transaction = $this->pixCashinModel->findByTransactionId($transactionId);

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
            'qrcode' => $transaction['qrcode'],
            'qrcode_base64' => $transaction['qrcode_base64'],
            'paid_at' => $transaction['paid_at'],
            'expires_at' => $transaction['expires_at'],
            'created_at' => $transaction['created_at']
        ];

        if (!empty($transaction['external_id'])) {
            $response['external_id'] = $transaction['external_id'];
        }

        successResponse($response);
    }

    public function listTransactions() {
        $seller = $this->authService->authenticateApiRequest();
        $this->authService->checkRateLimit($seller['api_key'], '/api/pix/list');

        $filters = [
            'status' => $_GET['status'] ?? null,
            'start_date' => $_GET['start_date'] ?? null,
            'end_date' => $_GET['end_date'] ?? null,
            'limit' => min((int)($_GET['limit'] ?? 50), 100)
        ];

        $filters = array_filter($filters, function($value) {
            return $value !== null;
        });

        $transactions = $this->pixCashinModel->getTransactionsBySeller($seller['id'], $filters);

        $response = array_map(function($tx) {
            return [
                'transaction_id' => $tx['transaction_id'],
                'amount' => $tx['amount'],
                'fee_amount' => $tx['fee_amount'],
                'net_amount' => $tx['net_amount'],
                'status' => $tx['status'],
                'paid_at' => $tx['paid_at'],
                'created_at' => $tx['created_at']
            ];
        }, $transactions);

        successResponse(['transactions' => $response]);
    }
}
