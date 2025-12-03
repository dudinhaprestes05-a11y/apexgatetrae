<?php

require_once __DIR__ . '/BaseModel.php';

class PixCashin extends BaseModel {
    protected $table = 'pix_cashin';

    public function findByTransactionId($transactionId) {
        return $this->findBy('transaction_id', $transactionId);
    }

    public function findByEndToEndId($endToEndId) {
        return $this->findBy('end_to_end_id', $endToEndId);
    }

    public function createTransaction($data) {
        $data['transaction_id'] = generateTransactionId('CASHIN');
        $data['status'] = 'pending';
        $data['created_at'] = date('Y-m-d H:i:s');

        if (isset($data['expires_in_minutes'])) {
            $data['expires_at'] = date('Y-m-d H:i:s', strtotime("+{$data['expires_in_minutes']} minutes"));
            unset($data['expires_in_minutes']);
        }

        return $this->create($data);
    }

    public function updateStatus($transactionId, $status, $additionalData = []) {
        $data = array_merge(['status' => $status], $additionalData);

        if ($status === 'paid' && !isset($data['paid_at'])) {
            $data['paid_at'] = date('Y-m-d H:i:s');
        }

        $transaction = $this->findByTransactionId($transactionId);

        if ($transaction) {
            return $this->update($transaction['id'], $data);
        }

        return false;
    }

    public function getExpiredTransactions($limit = 100) {
        $sql = "
            SELECT * FROM {$this->table}
            WHERE status = 'pending'
            AND expires_at < NOW()
            LIMIT ?
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$limit]);

        return $stmt->fetchAll();
    }

    public function getPendingWebhooks($limit = 100) {
        $sql = "
            SELECT * FROM {$this->table}
            WHERE status IN ('paid', 'expired', 'cancelled', 'failed')
            AND webhook_sent = 0
            LIMIT ?
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$limit]);

        return $stmt->fetchAll();
    }

    public function markWebhookSent($transactionId) {
        return $this->updateStatus($transactionId, null, [
            'webhook_sent' => 1,
            'webhook_sent_at' => date('Y-m-d H:i:s')
        ]);
    }

    public function getTransactionsBySeller($sellerId, $filters = []) {
        $params = [$sellerId];
        $whereClauses = ['seller_id = ?'];

        if (isset($filters['status'])) {
            $whereClauses[] = 'status = ?';
            $params[] = $filters['status'];
        }

        if (isset($filters['start_date'])) {
            $whereClauses[] = 'created_at >= ?';
            $params[] = $filters['start_date'];
        }

        if (isset($filters['end_date'])) {
            $whereClauses[] = 'created_at <= ?';
            $params[] = $filters['end_date'];
        }

        $sql = "
            SELECT * FROM {$this->table}
            WHERE " . implode(' AND ', $whereClauses) . "
            ORDER BY created_at DESC
        ";

        if (isset($filters['limit'])) {
            $sql .= " LIMIT " . (int)$filters['limit'];
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function getTotalAmountByStatus($sellerId, $status, $startDate = null, $endDate = null) {
        $params = [$sellerId, $status];
        $dateFilter = '';

        if ($startDate && $endDate) {
            $dateFilter = " AND created_at BETWEEN ? AND ?";
            $params[] = $startDate;
            $params[] = $endDate;
        }

        $sql = "
            SELECT COALESCE(SUM(amount), 0) as total
            FROM {$this->table}
            WHERE seller_id = ? AND status = ?
            {$dateFilter}
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        $result = $stmt->fetch();
        return $result['total'] ?? 0;
    }

    public function getRecentTransactions($limit = 10) {
        $sql = "
            SELECT c.*, s.name as seller_name, a.name as acquirer_name
            FROM {$this->table} c
            LEFT JOIN sellers s ON c.seller_id = s.id
            LEFT JOIN acquirers a ON c.acquirer_id = a.id
            ORDER BY c.created_at DESC
            LIMIT ?
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$limit]);

        return $stmt->fetchAll();
    }

    public function getTotalAmount() {
        $sql = "
            SELECT COALESCE(SUM(amount), 0) as total
            FROM {$this->table}
            WHERE status = 'paid'
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        $result = $stmt->fetch();
        return $result['total'] ?? 0;
    }

    public function getTotalAmountBySeller($sellerId) {
        $sql = "
            SELECT COALESCE(SUM(amount), 0) as total
            FROM {$this->table}
            WHERE seller_id = ? AND status = 'paid'
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$sellerId]);

        $result = $stmt->fetch();
        return $result['total'] ?? 0;
    }

    public function countByStatus($sellerId, $status) {
        $sql = "
            SELECT COUNT(*) as total
            FROM {$this->table}
            WHERE seller_id = ? AND status = ?
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$sellerId, $status]);

        return $stmt->fetchColumn();
    }

    public function getRecentBySeller($sellerId, $limit = 10) {
        $sql = "
            SELECT * FROM {$this->table}
            WHERE seller_id = ?
            ORDER BY created_at DESC
            LIMIT ?
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$sellerId, $limit]);

        return $stmt->fetchAll();
    }

    public function getStatsBySeller($sellerId) {
        $sql = "
            SELECT
                COUNT(*) as total_transactions,
                COALESCE(SUM(CASE WHEN status = 'paid' THEN amount ELSE 0 END), 0) as total_paid,
                COALESCE(SUM(CASE WHEN status = 'waiting_payment' THEN amount ELSE 0 END), 0) as total_pending,
                COUNT(CASE WHEN status = 'paid' THEN 1 END) as count_paid,
                COUNT(CASE WHEN status = 'waiting_payment' THEN 1 END) as count_pending,
                COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as count_cancelled,
                COUNT(CASE WHEN status = 'expired' THEN 1 END) as count_expired
            FROM {$this->table}
            WHERE seller_id = ?
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$sellerId]);

        return $stmt->fetch();
    }

    public function getBySeller($sellerId, $status = '', $limit = 20, $offset = 0) {
        $params = [$sellerId];
        $whereClause = 'seller_id = ?';

        if (!empty($status)) {
            $whereClause .= ' AND status = ?';
            $params[] = $status;
        }

        $sql = "
            SELECT * FROM {$this->table}
            WHERE {$whereClause}
            ORDER BY created_at DESC
            LIMIT ? OFFSET ?
        ";

        $params[] = $limit;
        $params[] = $offset;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function getStats($dateFrom = null) {
        $params = [];
        $dateFilter = '';

        if ($dateFrom) {
            $dateFilter = "WHERE created_at >= ?";
            $params[] = $dateFrom;
        }

        $sql = "
            SELECT
                COUNT(*) as total_transactions,
                COALESCE(SUM(CASE WHEN status = 'paid' THEN amount ELSE 0 END), 0) as total_volume,
                COALESCE(SUM(CASE WHEN status = 'paid' THEN fee_amount ELSE 0 END), 0) as total_fees,
                COUNT(CASE WHEN status = 'paid' THEN 1 END) as successful_transactions,
                COUNT(CASE WHEN status IN ('waiting_payment', 'pending') THEN 1 END) as pending_transactions,
                COUNT(CASE WHEN status IN ('cancelled', 'expired', 'failed') THEN 1 END) as failed_transactions,
                COALESCE(AVG(CASE WHEN status = 'paid' THEN amount END), 0) as avg_ticket
            FROM {$this->table}
            {$dateFilter}
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetch();
    }

    public function getDailyStats($dateFrom, $days = 7) {
        $sql = "
            SELECT
                DATE(created_at) as date,
                COUNT(*) as transactions,
                COALESCE(SUM(CASE WHEN status = 'paid' THEN amount ELSE 0 END), 0) as volume,
                COALESCE(SUM(CASE WHEN status = 'paid' THEN fee_amount ELSE 0 END), 0) as fees
            FROM {$this->table}
            WHERE created_at >= ?
            GROUP BY DATE(created_at)
            ORDER BY date ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$dateFrom]);

        return $stmt->fetchAll();
    }
}
