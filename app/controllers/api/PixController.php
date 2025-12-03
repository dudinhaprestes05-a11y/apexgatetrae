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

        if (isset($input['splits']) && !empty($input['splits'])) {
            $splitValidation = $this->splitService->validateSplits($amount, $input['splits']);
            if (!$splitValidation['valid']) {
                errorResponse($splitValidation['error'], 400);
            }
        }

        $acquirer = $this->acquirerService->selectAcquirer($amount);

        if (!$acquirer) {
            errorResponse('No acquirer available at the moment', 503);
        }

        $feeAmount = calculateFee($amount, $seller['fee_percentage_cashin'], $seller['fee_fixed_cashin']);
        $netAmount = calculateNetAmount($amount, $feeAmount);

        $transactionId = generateTransactionId('CASHIN');

        $cashinData = [
            'seller_id' => $seller['id'],
            'acquirer_id' => $acquirer['id'],
            'transaction_id' => $transactionId,
            'amount' => $amount,
            'fee_amount' => $feeAmount,
            'net_amount' => $netAmount,
            'pix_type' => $input['pix_type'] ?? 'dynamic',
            'metadata' => isset($input['metadata']) ? json_encode($input['metadata']) : null,
            'expires_in_minutes' => $input['expires_in_minutes'] ?? PIX_EXPIRATION_MINUTES
        ];

        $cashinId = $this->pixCashinModel->createTransaction($cashinData);

        $acquirerData = [
            'transaction_id' => $transactionId,
            'amount' => $amount,
            'pix_type' => $cashinData['pix_type'],
            'expires_at' => date('Y-m-d H:i:s', strtotime("+{$cashinData['expires_in_minutes']} minutes")),
            'metadata' => $input['metadata'] ?? [],
            'customer' => [
                'name' => $input['customer']['name'] ?? $seller['name'],
                'email' => $input['customer']['email'] ?? $seller['email'],
                'document' => $input['customer']['document'] ?? $seller['document']
            ]
        ];

        $acquirerResponse = $this->acquirerService->createPixCashin($acquirer, $acquirerData);

        if (!$acquirerResponse['success']) {
            $this->pixCashinModel->updateStatus($transactionId, 'failed', [
                'error_message' => $acquirerResponse['error']
            ]);

            errorResponse('Failed to create PIX transaction', 500, [
                'error' => $acquirerResponse['error']
            ]);
        }

        $this->pixCashinModel->update($cashinId, [
            'acquirer_transaction_id' => $acquirerResponse['data']['acquirer_transaction_id'] ?? null,
            'qrcode' => $acquirerResponse['data']['qrcode'] ?? null,
            'qrcode_base64' => $acquirerResponse['data']['qrcode_base64'] ?? null,
            'pix_key' => $acquirerResponse['data']['pix_key'] ?? null
        ]);

        if (isset($input['splits']) && !empty($input['splits'])) {
            try {
                $this->splitService->createSplits($cashinId, $netAmount, $input['splits']);
            } catch (Exception $e) {
                $this->logModel->error('api', 'Failed to create splits', [
                    'transaction_id' => $transactionId,
                    'error' => $e->getMessage()
                ]);
            }
        }

        $this->sellerModel->incrementDailyUsed($seller['id'], $amount);
        $this->acquirerModel->incrementDailyUsed($acquirer['id'], $amount);

        $this->logModel->info('api', 'PIX cashin created', [
            'seller_id' => $seller['id'],
            'transaction_id' => $transactionId,
            'amount' => $amount
        ]);

        $transaction = $this->pixCashinModel->findByTransactionId($transactionId);

        successResponse([
            'transaction_id' => $transactionId,
            'amount' => $amount,
            'fee_amount' => $feeAmount,
            'net_amount' => $netAmount,
            'qrcode' => $transaction['qrcode'],
            'qrcode_base64' => $transaction['qrcode_base64'],
            'pix_key' => $transaction['pix_key'],
            'expires_at' => $transaction['expires_at'],
            'status' => $transaction['status']
        ], 'PIX transaction created successfully');
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

        successResponse([
            'transaction_id' => $transaction['transaction_id'],
            'amount' => $transaction['amount'],
            'fee_amount' => $transaction['fee_amount'],
            'net_amount' => $transaction['net_amount'],
            'status' => $transaction['status'],
            'pix_key' => $transaction['pix_key'],
            'qrcode' => $transaction['qrcode'],
            'paid_at' => $transaction['paid_at'],
            'expires_at' => $transaction['expires_at'],
            'created_at' => $transaction['created_at']
        ]);
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
