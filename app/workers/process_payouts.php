<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../models/PixCashout.php';
require_once __DIR__ . '/../models/Log.php';
require_once __DIR__ . '/../services/AcquirerService.php';
require_once __DIR__ . '/../services/WebhookService.php';

$pixCashoutModel = new PixCashout();
$logModel = new Log();
$acquirerService = new AcquirerService();
$webhookService = new WebhookService();

$logModel->info('worker', 'Payout worker started');

try {
    $pendingPayouts = $pixCashoutModel->getPendingTransactions(50);

    $processed = 0;
    $failed = 0;

    foreach ($pendingPayouts as $payout) {
        try {
            if (!isset($payout['acquirer_account_id'])) {
                $logModel->warning('worker', 'Payout without acquirer_account_id', [
                    'transaction_id' => $payout['transaction_id']
                ]);
                continue;
            }

            if (!isset($payout['acquirer_code'])) {
                $logModel->warning('worker', 'Payout without acquirer_code', [
                    'transaction_id' => $payout['transaction_id'],
                    'account_id' => $payout['acquirer_account_id']
                ]);
                continue;
            }

            $acquirerTransactionId = $payout['acquirer_transaction_id'];

            if (!$acquirerTransactionId) {
                $logModel->warning('worker', 'Payout without acquirer transaction ID', [
                    'transaction_id' => $payout['transaction_id']
                ]);
                continue;
            }

            $result = $acquirerService->consultTransactionByAccount($payout, true);

            if ($result['success']) {
                $status = $result['data']['status'] ?? 'processing';
                $oldStatus = $payout['status'];

                $pixCashoutModel->updateStatus($payout['transaction_id'], $status);

                $logModel->info('worker', 'Payout status updated', [
                    'transaction_id' => $payout['transaction_id'],
                    'account_id' => $payout['acquirer_account_id'],
                    'account_name' => $payout['account_name'] ?? 'Unknown',
                    'acquirer_code' => $payout['acquirer_code'],
                    'old_status' => $oldStatus,
                    'new_status' => $status
                ]);

                $statusLower = strtolower($status);
                $oldStatusLower = strtolower($oldStatus);

                if (($statusLower === 'completed' || $statusLower === 'paid') && $oldStatusLower !== $statusLower) {
                    $updatedPayout = $pixCashoutModel->findByTransactionId($payout['transaction_id']);

                    if ($updatedPayout) {
                        $webhookData = [
                            'transaction_id' => $updatedPayout['transaction_id'],
                            'external_id' => $updatedPayout['external_id'] ?? null,
                            'status' => $status,
                            'net_amount' => $updatedPayout['net_amount'],
                            'fee_amount' => $updatedPayout['fee_amount'],
                            'processed_at' => $updatedPayout['processed_at'] ?? date('Y-m-d H:i:s')
                        ];

                        $webhookService->enqueueWebhook(
                            $updatedPayout['seller_id'],
                            $updatedPayout['transaction_id'],
                            'cashout',
                            $webhookData,
                            true
                        );

                        $logModel->info('worker', 'Cashout webhook enqueued', [
                            'transaction_id' => $updatedPayout['transaction_id'],
                            'seller_id' => $updatedPayout['seller_id'],
                            'status' => $status
                        ]);
                    }
                }

                $processed++;
            } else {
                $logModel->warning('worker', 'Failed to consult payout', [
                    'transaction_id' => $payout['transaction_id'],
                    'account_id' => $payout['acquirer_account_id'] ?? null,
                    'account_name' => $payout['account_name'] ?? 'Unknown',
                    'acquirer_code' => $payout['acquirer_code'] ?? 'Unknown',
                    'error' => $result['error']
                ]);
                $failed++;
            }

        } catch (Exception $e) {
            $logModel->error('worker', 'Failed to process payout', [
                'transaction_id' => $payout['transaction_id'],
                'account_id' => $payout['acquirer_account_id'] ?? null,
                'account_name' => $payout['account_name'] ?? 'Unknown',
                'error' => $e->getMessage()
            ]);
            $failed++;
        }

        usleep(200000);
    }

    $logModel->info('worker', 'Payout worker completed', [
        'processed' => $processed,
        'failed' => $failed
    ]);

    echo "Processed: {$processed}, Failed: {$failed}\n";

} catch (Exception $e) {
    $logModel->error('worker', 'Payout worker error', [
        'error' => $e->getMessage()
    ]);

    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
