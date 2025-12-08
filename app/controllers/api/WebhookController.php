<?php

require_once __DIR__ . '/../../models/Acquirer.php';
require_once __DIR__ . '/../../models/PixCashin.php';
require_once __DIR__ . '/../../models/PixCashout.php';
require_once __DIR__ . '/../../models/Seller.php';
require_once __DIR__ . '/../../models/Log.php';
require_once __DIR__ . '/../../services/WebhookService.php';
require_once __DIR__ . '/../../services/SplitService.php';

class WebhookController {
    private $acquirerModel;
    private $pixCashinModel;
    private $pixCashoutModel;
    private $sellerModel;
    private $logModel;
    private $webhookService;
    private $splitService;

    public function __construct() {
        $this->acquirerModel = new Acquirer();
        $this->pixCashinModel = new PixCashin();
        $this->pixCashoutModel = new PixCashout();
        $this->sellerModel = new Seller();
        $this->logModel = new Log();
        $this->webhookService = new WebhookService();
        $this->splitService = new SplitService();
    }

    public function receiveFromAcquirer() {
        $acquirerCode = $_GET['acquirer'] ?? null;

        if (!$acquirerCode) {
            errorResponse('Acquirer code is required', 400);
        }

        $acquirer = $this->acquirerModel->findByCode($acquirerCode);

        if (!$acquirer) {
            errorResponse('Acquirer not found', 404);
        }

        $payload = file_get_contents('php://input');
        $data = json_decode($payload, true);

        if (!$data) {
            errorResponse('Invalid JSON payload', 400);
        }

        $headers = getAllHeadersCaseInsensitive();
        $signature = $headers['X-Signature'] ?? null;

        if ($signature && $acquirer['api_secret']) {
            if (!verifyHmacSignature($payload, $signature, $acquirer['api_secret'])) {
                $this->logModel->warning('webhook', 'Invalid signature from acquirer', [
                    'acquirer_id' => $acquirer['id'],
                    'ip' => getClientIp()
                ]);
                errorResponse('Invalid signature', 401);
            }
        }

        $callbackId = $this->logCallback($acquirer['id'], $data, $headers);

        $this->processAcquirerWebhook($acquirer, $data, $callbackId);

        successResponse(null, 'Webhook received successfully');
    }

    private function logCallback($acquirerId, $data, $headers) {
        $db = db();

        $stmt = $db->prepare("
            INSERT INTO callbacks_acquirers (acquirer_id, transaction_id, acquirer_transaction_id, payload, headers, ip_address)
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $acquirerId,
            $data['transaction_id'] ?? null,
            $data['acquirer_transaction_id'] ?? null,
            json_encode($data),
            json_encode($headers),
            getClientIp()
        ]);

        return $db->lastInsertId();
    }

    private function processAcquirerWebhook($acquirer, $data, $callbackId) {
        try {
            // Parse PodPay webhook format
            if ($acquirer['code'] === 'podpay') {
                require_once __DIR__ . '/../../services/PodPayService.php';
                $podpay = new PodPayService($acquirer);
                $parsedData = $podpay->parseWebhook($data);

                if (!$parsedData) {
                    throw new Exception('Failed to parse PodPay webhook');
                }

                // Get transaction_id from our database using acquirer_transaction_id
                $transactionId = $this->findTransactionIdByAcquirerId(
                    $parsedData['acquirer_transaction_id'],
                    $parsedData['transaction_type']
                );

                if (!$transactionId) {
                    throw new Exception('Transaction not found for acquirer_transaction_id: ' . $parsedData['acquirer_transaction_id']);
                }

                $parsedData['transaction_id'] = $transactionId;
                $data = $parsedData;
            } elseif ($acquirer['code'] === 'velana') {
                require_once __DIR__ . '/../../services/VelanaService.php';
                $velana = new VelanaService($acquirer);
                $parsedData = $velana->parseWebhook($data);

                if (!$parsedData) {
                    throw new Exception('Failed to parse Velana webhook');
                }

                $transactionId = $this->findTransactionIdByAcquirerId(
                    $parsedData['acquirer_transaction_id'],
                    $parsedData['transaction_type']
                );

                if (!$transactionId) {
                    throw new Exception('Transaction not found for acquirer_transaction_id: ' . $parsedData['acquirer_transaction_id']);
                }

                $parsedData['transaction_id'] = $transactionId;
                $data = $parsedData;
            }

            $transactionId = $data['transaction_id'] ?? null;
            $transactionType = $data['transaction_type'] ?? 'cashin';
            $status = $data['status'] ?? null;

            if (!$transactionId || !$status) {
                throw new Exception('Missing required fields: transaction_id or status');
            }

            if ($transactionType === 'cashin') {
                $this->processCashinWebhook($transactionId, $data);
            } elseif ($transactionType === 'cashout') {
                $this->processCashoutWebhook($transactionId, $data);
            } else {
                throw new Exception('Invalid transaction type');
            }

            $this->markCallbackProcessed($callbackId);

        } catch (Exception $e) {
            $this->markCallbackError($callbackId, $e->getMessage());

            $this->logModel->error('webhook', 'Failed to process acquirer webhook', [
                'acquirer_id' => $acquirer['id'],
                'callback_id' => $callbackId,
                'error' => $e->getMessage()
            ]);
        }
    }

    private function findTransactionIdByAcquirerId($acquirerTransactionId, $transactionType) {
        if ($transactionType === 'cashin') {
            $transaction = $this->pixCashinModel->findByAcquirerTransactionId($acquirerTransactionId);
        } else {
            $transaction = $this->pixCashoutModel->findByAcquirerTransactionId($acquirerTransactionId);
        }

        return $transaction ? $transaction['transaction_id'] : null;
    }

    private function processCashinWebhook($transactionId, $data) {
        $transaction = $this->pixCashinModel->findByTransactionId($transactionId);

        if (!$transaction) {
            throw new Exception("Transaction not found: {$transactionId}");
        }

        if (!isset($data['acquirer_transaction_id'])) {
            throw new Exception("Missing acquirer_transaction_id in webhook data");
        }

        $verified = $this->verifyWebhookWithAcquirer($transaction, $data);
        if (!$verified) {
            $this->logModel->warning('webhook', 'Webhook verification failed', [
                'transaction_id' => $transactionId,
                'acquirer_transaction_id' => $data['acquirer_transaction_id']
            ]);
            throw new Exception("Webhook verification failed - data mismatch");
        }

        $updateData = [
            'status' => $data['status']
        ];

        if (isset($data['end_to_end_id'])) {
            $updateData['end_to_end_id'] = $data['end_to_end_id'];
        }

        if (isset($data['payer_name'])) {
            $updateData['payer_name'] = $data['payer_name'];
        }

        if (isset($data['payer_document'])) {
            $updateData['payer_document'] = sanitizeDocument($data['payer_document']);
        }

        if (isset($data['payer_bank'])) {
            $updateData['payer_bank'] = $data['payer_bank'];
        }

        if ($data['status'] === 'paid') {
            $this->sellerModel->updateBalance($transaction['seller_id'], $transaction['net_amount']);

            $this->splitService->processSplits($transaction['id']);
        }

        $this->pixCashinModel->updateStatus($transactionId, $data['status'], $updateData);

        $updatedTransaction = $this->pixCashinModel->findByTransactionId($transactionId);
        $this->webhookService->enqueueWebhook(
            $transaction['seller_id'],
            $transactionId,
            'cashin',
            $updatedTransaction
        );

        $this->logModel->info('webhook', 'Cashin webhook processed', [
            'transaction_id' => $transactionId,
            'status' => $data['status']
        ]);
    }

    private function processCashoutWebhook($transactionId, $data) {
        $transaction = $this->pixCashoutModel->findByTransactionId($transactionId);

        if (!$transaction) {
            throw new Exception("Transaction not found: {$transactionId}");
        }

        if (!isset($data['acquirer_transaction_id'])) {
            throw new Exception("Missing acquirer_transaction_id in webhook data");
        }

        $verified = $this->verifyCashoutWebhookWithAcquirer($transaction, $data);
        if (!$verified) {
            $this->logModel->warning('webhook', 'Cashout webhook verification failed', [
                'transaction_id' => $transactionId,
                'acquirer_transaction_id' => $data['acquirer_transaction_id']
            ]);
            throw new Exception("Cashout webhook verification failed - data mismatch");
        }

        $updateData = [
            'status' => $data['status']
        ];

        if (isset($data['end_to_end_id'])) {
            $updateData['end_to_end_id'] = $data['end_to_end_id'];
        }

        if (isset($data['receipt_url'])) {
            $updateData['receipt_url'] = $data['receipt_url'];
        }

        if ($data['status'] === 'failed') {
            $this->sellerModel->updateBalance($transaction['seller_id'], $transaction['amount']);

            if (isset($data['error_message'])) {
                $updateData['error_message'] = $data['error_message'];
            }
        }

        $this->pixCashoutModel->updateStatus($transactionId, $data['status'], $updateData);

        $updatedTransaction = $this->pixCashoutModel->findByTransactionId($transactionId);
        $this->webhookService->enqueueWebhook(
            $transaction['seller_id'],
            $transactionId,
            'cashout',
            $updatedTransaction
        );

        $this->logModel->info('webhook', 'Cashout webhook processed', [
            'transaction_id' => $transactionId,
            'status' => $data['status']
        ]);
    }

    private function verifyWebhookWithAcquirer($transaction, $webhookData) {
        try {
            if (!isset($transaction['acquirer_account_id'])) {
                $this->logModel->warning('webhook', 'Transaction has no acquirer_account_id', [
                    'transaction_id' => $transaction['transaction_id']
                ]);
                return false;
            }

            $db = db();
            $stmt = $db->prepare("
                SELECT
                    acc.*,
                    acq.code as acquirer_code,
                    acq.api_url,
                    acq.api_key,
                    acq.api_secret
                FROM acquirer_accounts acc
                INNER JOIN acquirers acq ON acc.acquirer_id = acq.id
                WHERE acc.id = ?
            ");
            $stmt->execute([$transaction['acquirer_account_id']]);
            $account = $stmt->fetch();

            if (!$account) {
                $this->logModel->error('webhook', 'Acquirer account not found', [
                    'acquirer_account_id' => $transaction['acquirer_account_id']
                ]);
                return false;
            }

            if ($account['acquirer_code'] === 'podpay') {
                require_once __DIR__ . '/../../services/PodPayService.php';
                $podpay = new PodPayService($account);

                $consultResult = $podpay->consultTransaction($webhookData['acquirer_transaction_id']);

                if (!$consultResult['success']) {
                    $this->logModel->error('webhook', 'Failed to consult transaction with acquirer', [
                        'transaction_id' => $transaction['transaction_id'],
                        'acquirer_transaction_id' => $webhookData['acquirer_transaction_id'],
                        'error' => $consultResult['error']
                    ]);
                    return false;
                }

                $consultedData = $consultResult['data'];
                $mappedStatus = $this->mapPodPayTransactionStatus($consultedData['status']);
            } elseif ($account['acquirer_code'] === 'velana') {
                require_once __DIR__ . '/../../services/VelanaService.php';
                $velana = new VelanaService($account);

                $consultResult = $velana->consultTransaction($webhookData['acquirer_transaction_id']);

                if (!$consultResult['success']) {
                    $this->logModel->error('webhook', 'Failed to consult Velana transaction', [
                        'transaction_id' => $transaction['transaction_id'],
                        'acquirer_transaction_id' => $webhookData['acquirer_transaction_id'],
                        'error' => $consultResult['error']
                    ]);
                    return false;
                }

                $consultedData = $consultResult['data'];
                $mappedStatus = $this->mapVelanaTransactionStatus($consultedData['status']);
            } else {
                $this->logModel->info('webhook', 'Webhook verification skipped for unsupported acquirer', [
                    'acquirer_code' => $account['acquirer_code']
                ]);
                return true;
            }

            if ($mappedStatus !== $webhookData['status']) {
                $this->logModel->error('webhook', 'Status mismatch between webhook and API consultation', [
                    'transaction_id' => $transaction['transaction_id'],
                    'webhook_status' => $webhookData['status'],
                    'consulted_status_raw' => $consultedData['status'],
                    'consulted_status_mapped' => $mappedStatus
                ]);
                return false;
            }

            if (isset($webhookData['amount']) && abs($consultedData['amount'] - $webhookData['amount']) > 0.01) {
                $this->logModel->error('webhook', 'Amount mismatch between webhook and API consultation', [
                    'transaction_id' => $transaction['transaction_id'],
                    'webhook_amount' => $webhookData['amount'],
                    'consulted_amount' => $consultedData['amount']
                ]);
                return false;
            }

            $this->logModel->info('webhook', 'Webhook verified successfully with acquirer API', [
                'transaction_id' => $transaction['transaction_id'],
                'acquirer_transaction_id' => $webhookData['acquirer_transaction_id'],
                'status' => $consultedData['status']
            ]);

            return true;

        } catch (Exception $e) {
            $this->logModel->error('webhook', 'Exception during webhook verification', [
                'transaction_id' => $transaction['transaction_id'],
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    private function verifyCashoutWebhookWithAcquirer($transaction, $webhookData) {
        try {
            if (!isset($transaction['acquirer_account_id'])) {
                $this->logModel->warning('webhook', 'Transaction has no acquirer_account_id', [
                    'transaction_id' => $transaction['transaction_id']
                ]);
                return false;
            }

            $db = db();
            $stmt = $db->prepare("
                SELECT
                    acc.*,
                    acq.code as acquirer_code,
                    acq.api_url,
                    acq.api_key,
                    acq.api_secret
                FROM acquirer_accounts acc
                INNER JOIN acquirers acq ON acc.acquirer_id = acq.id
                WHERE acc.id = ?
            ");
            $stmt->execute([$transaction['acquirer_account_id']]);
            $account = $stmt->fetch();

            if (!$account) {
                $this->logModel->error('webhook', 'Acquirer account not found', [
                    'acquirer_account_id' => $transaction['acquirer_account_id']
                ]);
                return false;
            }

            if ($account['acquirer_code'] === 'podpay') {
                require_once __DIR__ . '/../../services/PodPayService.php';
                $podpay = new PodPayService($account);

                $consultResult = $podpay->consultTransfer($webhookData['acquirer_transaction_id']);

                if (!$consultResult['success']) {
                    $this->logModel->error('webhook', 'Failed to consult transfer with acquirer', [
                        'transaction_id' => $transaction['transaction_id'],
                        'acquirer_transaction_id' => $webhookData['acquirer_transaction_id'],
                        'error' => $consultResult['error']
                    ]);
                    return false;
                }

                $consultedData = $consultResult['data'];
                $mappedStatus = $this->mapPodPayWithdrawStatus($consultedData['status']);
            } elseif ($account['acquirer_code'] === 'velana') {
                require_once __DIR__ . '/../../services/VelanaService.php';
                $velana = new VelanaService($account);

                $consultResult = $velana->consultTransfer($webhookData['acquirer_transaction_id']);

                if (!$consultResult['success']) {
                    $this->logModel->error('webhook', 'Failed to consult Velana transfer', [
                        'transaction_id' => $transaction['transaction_id'],
                        'acquirer_transaction_id' => $webhookData['acquirer_transaction_id'],
                        'error' => $consultResult['error']
                    ]);
                    return false;
                }

                $consultedData = $consultResult['data'];
                $mappedStatus = $this->mapVelanaWithdrawStatus($consultedData['status']);

                if (isset($consultedData['receipt_url']) && $consultedData['receipt_url']) {
                    $webhookData['receipt_url'] = $consultedData['receipt_url'];
                }
            } else {
                $this->logModel->info('webhook', 'Webhook verification skipped for unsupported acquirer', [
                    'acquirer_code' => $account['acquirer_code']
                ]);
                return true;
            }

            if ($mappedStatus !== $webhookData['status']) {
                $this->logModel->error('webhook', 'Status mismatch between webhook and API consultation', [
                    'transaction_id' => $transaction['transaction_id'],
                    'webhook_status' => $webhookData['status'],
                    'consulted_status_raw' => $consultedData['status'],
                    'consulted_status_mapped' => $mappedStatus
                ]);
                return false;
            }

            if (isset($webhookData['amount']) && abs($consultedData['amount'] - $webhookData['amount']) > 0.01) {
                $this->logModel->error('webhook', 'Amount mismatch between webhook and API consultation', [
                    'transaction_id' => $transaction['transaction_id'],
                    'webhook_amount' => $webhookData['amount'],
                    'consulted_amount' => $consultedData['amount']
                ]);
                return false;
            }

            $this->logModel->info('webhook', 'Cashout webhook verified successfully with acquirer API', [
                'transaction_id' => $transaction['transaction_id'],
                'acquirer_transaction_id' => $webhookData['acquirer_transaction_id'],
                'status' => $consultedData['status']
            ]);

            return true;

        } catch (Exception $e) {
            $this->logModel->error('webhook', 'Exception during cashout webhook verification', [
                'transaction_id' => $transaction['transaction_id'],
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    private function markCallbackProcessed($callbackId) {
        $db = db();
        $stmt = $db->prepare("UPDATE callbacks_acquirers SET status = 'processed', processed_at = NOW() WHERE id = ?");
        $stmt->execute([$callbackId]);
    }

    private function markCallbackError($callbackId, $error) {
        $db = db();
        $stmt = $db->prepare("UPDATE callbacks_acquirers SET status = 'error', error_message = ? WHERE id = ?");
        $stmt->execute([$error, $callbackId]);
    }

    private function mapPodPayTransactionStatus($podpayStatus) {
        $statusMap = [
            'waiting_payment' => 'waiting_payment',
            'pending' => 'pending',
            'approved' => 'paid',
            'paid' => 'paid',
            'refused' => 'failed',
            'cancelled' => 'cancelled',
            'expired' => 'expired'
        ];

        return $statusMap[$podpayStatus] ?? 'pending';
    }

    private function mapPodPayWithdrawStatus($podpayStatus) {
        $statusMap = [
            'PENDING_QUEUE' => 'processing',
            'pending' => 'processing',
            'processing' => 'processing',
            'COMPLETED' => 'completed',
            'completed' => 'completed',
            'failed' => 'failed',
            'cancelled' => 'cancelled'
        ];

        return $statusMap[$podpayStatus] ?? 'processing';
    }

    private function mapVelanaTransactionStatus($velanaStatus) {
        $statusMap = [
            'waiting_payment' => 'waiting_payment',
            'paid' => 'paid',
            'refused' => 'failed',
            'cancelled' => 'cancelled',
            'expired' => 'expired'
        ];

        return $statusMap[$velanaStatus] ?? 'waiting_payment';
    }

    private function mapVelanaWithdrawStatus($velanaStatus) {
        $statusMap = [
            'in_analysis' => 'processing',
            'pending' => 'processing',
            'processing' => 'processing',
            'success' => 'completed',
            'failed' => 'failed',
            'cancelled' => 'cancelled'
        ];

        return $statusMap[$velanaStatus] ?? 'processing';
    }
}
