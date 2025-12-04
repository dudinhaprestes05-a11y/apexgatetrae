<?php

require_once __DIR__ . '/BaseModel.php';

class WebhookQueue extends BaseModel {
    protected $table = 'webhooks_queue';

    public function enqueue($sellerId, $transactionId, $transactionType, $webhookUrl, $payload, $secret) {
        $data = [
            'seller_id' => $sellerId,
            'transaction_id' => $transactionId,
            'transaction_type' => $transactionType,
            'webhook_url' => $webhookUrl,
            'payload' => json_encode($payload),
            'signature' => $secret,
            'status' => 'pending',
            'next_retry_at' => date('Y-m-d H:i:s')
        ];

        return $this->create($data);
    }

    public function getPendingWebhooks($limit = 50) {
        $sql = "
            SELECT * FROM {$this->table}
            WHERE status IN ('pending', 'failed')
            AND attempts < max_attempts
            AND (next_retry_at IS NULL OR next_retry_at <= NOW())
            ORDER BY created_at ASC
            LIMIT ?
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$limit]);

        return $stmt->fetchAll();
    }

    public function markAsProcessing($id) {
        return $this->update($id, ['status' => 'processing']);
    }

    public function markAsSent($id) {
        return $this->update($id, [
            'status' => 'sent',
            'sent_at' => date('Y-m-d H:i:s')
        ]);
    }

    public function markAsFailed($id, $error) {
        $webhook = $this->find($id);

        if (!$webhook) {
            return false;
        }

        $attempts = $webhook['attempts'] + 1;
        $nextRetry = $this->calculateNextRetry($attempts);

        return $this->update($id, [
            'status' => 'failed',
            'attempts' => $attempts,
            'last_error' => $error,
            'next_retry_at' => $nextRetry
        ]);
    }

    private function calculateNextRetry($attempts) {
        $delays = [60, 300, 900, 3600, 7200];
        $delayIndex = min($attempts - 1, count($delays) - 1);
        $delay = $delays[$delayIndex];

        return date('Y-m-d H:i:s', time() + $delay);
    }

    public function getFailedWebhooks($limit = 100) {
        $sql = "
            SELECT * FROM {$this->table}
            WHERE status = 'failed'
            AND attempts >= max_attempts
            ORDER BY created_at DESC
            LIMIT ?
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$limit]);

        return $stmt->fetchAll();
    }

    public function retryWebhook($id) {
        return $this->update($id, [
            'status' => 'pending',
            'next_retry_at' => date('Y-m-d H:i:s')
        ]);
    }

    public function getWebhooksBySeller($sellerId, $limit = 100) {
        return $this->where(['seller_id' => $sellerId], 'created_at DESC', $limit);
    }

    public function cleanOldWebhooks($days = 30) {
        $sql = "DELETE FROM {$this->table} WHERE status = 'sent' AND created_at < DATE_SUB(NOW(), INTERVAL ? DAY)";
        return $this->execute($sql, [$days]);
    }
}
