<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../models/PixCashout.php';
require_once __DIR__ . '/../models/Log.php';
require_once __DIR__ . '/../services/AcquirerService.php';

$pixCashoutModel = new PixCashout();
$logModel = new Log();
$acquirerService = new AcquirerService();

$logModel->info('worker', 'Payout worker started');

try {
    $pendingPayouts = $pixCashoutModel->getPendingTransactions(50);

    $processed = 0;
    $failed = 0;

    foreach ($pendingPayouts as $payout) {
        try {
            $acquirer = (new \Acquirer())->find($payout['acquirer_id']);

            if (!$acquirer) {
                throw new Exception('Acquirer not found');
            }

            $acquirerTransactionId = $payout['acquirer_transaction_id'];

            if (!$acquirerTransactionId) {
                $logModel->warning('worker', 'Payout without acquirer transaction ID', [
                    'transaction_id' => $payout['transaction_id']
                ]);
                continue;
            }

            $result = $acquirerService->consultTransaction($acquirer, $acquirerTransactionId, true);

            if ($result['success']) {
                $status = $result['data']['status'] ?? 'processing';

                $updateData = [];
                if (isset($result['data']['net_amount'])) {
                    $updateData['net_amount'] = $result['data']['net_amount'];
                }
                if (isset($result['data']['fee'])) {
                    $updateData['fee_amount'] = $result['data']['fee'];
                }

                $pixCashoutModel->updateStatus($payout['transaction_id'], $status, $updateData);

                $logModel->info('worker', 'Payout status updated', [
                    'transaction_id' => $payout['transaction_id'],
                    'old_status' => $payout['status'],
                    'new_status' => $status
                ]);

                $processed++;
            } else {
                $logModel->warning('worker', 'Failed to consult payout', [
                    'transaction_id' => $payout['transaction_id'],
                    'error' => $result['error']
                ]);
                $failed++;
            }

        } catch (Exception $e) {
            $logModel->error('worker', 'Failed to process payout', [
                'transaction_id' => $payout['transaction_id'],
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
