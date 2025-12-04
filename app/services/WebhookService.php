<?php

require_once __DIR__ . '/../models/WebhookQueue.php';
require_once __DIR__ . '/../models/Seller.php';
require_once __DIR__ . '/../models/Log.php';

class WebhookService {
    private $webhookQueue;
    private $sellerModel;
    private $logModel;

    public function __construct() {
        $this->webhookQueue = new WebhookQueue();
        $this->sellerModel = new Seller();
        $this->logModel = new Log();
    }

    public function enqueueWebhook($sellerId, $transactionId, $transactionType, $transactionData, $sendImmediately = true) {
        $seller = $this->sellerModel->find($sellerId);

        if (!$seller || !$seller['webhook_url']) {
            $this->logModel->info('webhook', 'No webhook URL configured', [
                'seller_id' => $sellerId,
                'transaction_id' => $transactionId
            ]);
            return false;
        }

        if ($transactionType === 'cashin') {
            $payload = [
                'type' => 'pix.cashin',
                'transaction_id' => $transactionData['transaction_id'] ?? null,
                'external_id' => $transactionData['external_id'] ?? null,
                'status' => $transactionData['status'],
                'amount' => $transactionData['amount'],
                'paid_at' => $transactionData['paid_at'] ?? null
            ];
        } else {
            $payload = [
                'type' => 'pix.cashout',
                'transaction_id' => $transactionData['transaction_id'] ?? null,
                'external_id' => $transactionData['external_id'] ?? null,
                'status' => $transactionData['status'],
                'net_amount' => $transactionData['net_amount'],
                'fee' => $transactionData['fee_amount']
            ];
        }

        $webhookSecret = $seller['webhook_secret'] ?? $seller['api_secret'];

        $webhookId = $this->webhookQueue->enqueue(
            $sellerId,
            $transactionId,
            $transactionType,
            $seller['webhook_url'],
            $payload,
            $webhookSecret
        );

        $this->logModel->info('webhook', 'Webhook enqueued', [
            'seller_id' => $sellerId,
            'transaction_id' => $transactionId,
            'transaction_type' => $transactionType,
            'external_id' => $transactionData['external_id'] ?? null,
            'webhook_id' => $webhookId
        ]);

        if ($sendImmediately && $webhookId) {
            $webhook = $this->webhookQueue->find($webhookId);

            if ($webhook) {
                $this->logModel->debug('webhook', 'Attempting immediate webhook delivery', [
                    'webhook_id' => $webhookId,
                    'transaction_id' => $transactionId
                ]);

                $success = $this->sendWebhook($webhook);

                if ($success) {
                    $this->logModel->info('webhook', 'Webhook sent immediately', [
                        'webhook_id' => $webhookId,
                        'transaction_id' => $transactionId
                    ]);
                } else {
                    $this->logModel->warning('webhook', 'Immediate webhook failed, will retry via cron', [
                        'webhook_id' => $webhookId,
                        'transaction_id' => $transactionId
                    ]);
                }
            }
        }

        return true;
    }

    public function sendWebhook($webhook) {
        $this->webhookQueue->markAsProcessing($webhook['id']);

        $this->logModel->debug('webhook', 'Sending webhook', [
            'webhook_id' => $webhook['id'],
            'transaction_id' => $webhook['transaction_id'],
            'url' => $webhook['webhook_url']
        ]);

        $ch = curl_init($webhook['webhook_url']);

        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $webhook['payload'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'X-Webhook-Secret: ' . $webhook['signature'],
                'X-Transaction-Id: ' . $webhook['transaction_id'],
                'User-Agent: Gateway-PIX-Webhook/1.0'
            ],
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);

        curl_close($ch);

        if ($httpCode >= 200 && $httpCode < 300) {
            $this->webhookQueue->markAsSent($webhook['id']);

            $this->logModel->info('webhook', 'Webhook sent successfully', [
                'webhook_id' => $webhook['id'],
                'transaction_id' => $webhook['transaction_id'],
                'http_code' => $httpCode
            ]);

            return true;
        } else {
            $errorMessage = $error ?: "HTTP {$httpCode}: {$response}";
            $this->webhookQueue->markAsFailed($webhook['id'], $errorMessage);

            $this->logModel->warning('webhook', 'Webhook failed', [
                'webhook_id' => $webhook['id'],
                'transaction_id' => $webhook['transaction_id'],
                'http_code' => $httpCode,
                'error' => $errorMessage
            ]);

            return false;
        }
    }

    public function processPendingWebhooks($limit = 50) {
        $webhooks = $this->webhookQueue->getPendingWebhooks($limit);

        $this->logModel->debug('webhook', 'Processing pending webhooks', [
            'count' => count($webhooks)
        ]);

        $results = [
            'processed' => 0,
            'success' => 0,
            'failed' => 0
        ];

        foreach ($webhooks as $webhook) {
            $results['processed']++;

            if ($this->sendWebhook($webhook)) {
                $results['success']++;
            } else {
                $results['failed']++;
            }

            usleep(100000);
        }

        return $results;
    }

    public function retryFailedWebhook($webhookId) {
        return $this->webhookQueue->retryWebhook($webhookId);
    }

    public function verifyIncomingWebhook($payload, $signature, $secret) {
        return verifyHmacSignature($payload, $signature, $secret);
    }
}
