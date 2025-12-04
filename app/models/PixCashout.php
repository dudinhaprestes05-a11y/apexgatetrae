<?php

require_once __DIR__ . '/BaseModel.php';

class PixCashout extends BaseModel {
    protected $table = 'pix_cashout';

    public function findByTransactionId($transactionId) {
        return $this->findBy('transaction_id', $transactionId);
    }

    public function findByAcquirerTransactionId($acquirerTransactionId) {
        return $this->findBy('acquirer_transaction_id', $acquirerTransactionId);
    }

    public function checkDuplicate($sellerId, $pixKey, $beneficiaryDocument) {
        $sql = "
            SELECT * FROM {$this->table}
            WHERE seller_id = ?
            AND (pix_key = ? OR beneficiary_document = ?)
            AND status IN ('pending', 'processing')
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$sellerId, $pixKey, $beneficiaryDocument]);

        return $stmt->fetch();
    }

    public function createTransaction($data) {
        $data['transaction_id'] = generateTransactionId('CASHOUT');
        $data['status'] = 'pending';
        $data['created_at'] = date('Y-m-d H:i:s');

        return $this->create($data);
    }

    public function updateStatus($transactionId, $status, $additionalData = []) {
        $data = array_merge(['status' => $status], $additionalData);

        if ($status === 'completed' && !isset($data['processed_at'])) {
            $data['processed_at'] = date('Y-m-d H:i:s');
        }

        $transaction = $this->findByTransactionId($transactionId);

        if ($transaction) {
            return $this->update($transaction['id'], $data);
        }

        return false;
    }

    public function getPendingTransactions($limit = 100) {
        $sql = "
            SELECT * FROM {$this->table}
            WHERE status = 'pending'
            ORDER BY created_at ASC
            LIMIT ?
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$limit]);

        return $stmt->fetchAll();
    }

    public function getPendingWebhooks($limit = 100) {
        $sql = "
            SELECT * FROM {$this->table}
            WHERE status IN ('completed', 'failed', 'cancelled')
            AND webhook_sent = 0
            LIMIT ?
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$limit]);

        return $stmt->fetchAll();
    }

    public function markWebhookSent($transactionId) {
        $transaction = $this->findByTransactionId($transactionId);

        if ($transaction) {
            return $this->update($transaction['id'], [
                'webhook_sent' => 1,
                'webhook_sent_at' => date('Y-m-d H:i:s')
            ]);
        }

        return false;
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

    public function getTotalAmount($sellerId = null, $status = null, $startDate = null, $endDate = null) {
        if ($sellerId === null && $status === null) {
            $sql = "
                SELECT COALESCE(SUM(amount), 0) as total
                FROM {$this->table}
                WHERE status = 'completed'
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute();

            $result = $stmt->fetch();
            return $result['total'] ?? 0;
        }

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

    public function getTotalAmountBySeller($sellerId) {
        $sql = "
            SELECT COALESCE(SUM(amount), 0) as total
            FROM {$this->table}
            WHERE seller_id = ? AND status = 'completed'
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
                COALESCE(SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END), 0) as total_completed,
                COALESCE(SUM(CASE WHEN status = 'pending' THEN amount ELSE 0 END), 0) as total_pending,
                COUNT(CASE WHEN status = 'completed' THEN 1 END) as count_completed,
                COUNT(CASE WHEN status = 'pending' THEN 1 END) as count_pending,
                COUNT(CASE WHEN status = 'failed' THEN 1 END) as count_failed,
                COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as count_cancelled
            FROM {$this->table}
            WHERE seller_id = ?
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$sellerId]);

        return $stmt->fetch();
    }

    public function search($search, $status = '', $limit = 20, $offset = 0) {
        $params = [];
        $whereClause = '1=1';

        $searchPattern = "%{$search}%";
        $whereClause .= ' AND (transaction_id LIKE ? OR pix_key LIKE ?)';
        $params[] = $searchPattern;
        $params[] = $searchPattern;

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

    public function searchBySeller($sellerId, $search, $status = '', $limit = 20, $offset = 0) {
        $params = [$sellerId];
        $whereClause = 'seller_id = ?';

        $searchPattern = "%{$search}%";
        $whereClause .= ' AND (transaction_id LIKE ? OR pix_key LIKE ?)';
        $params[] = $searchPattern;
        $params[] = $searchPattern;

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

    public function getTotalFees() {
        $sql = "
            SELECT COALESCE(SUM(fee_amount), 0) as total
            FROM {$this->table}
            WHERE status = 'completed'
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        $result = $stmt->fetch();
        return $result['total'] ?? 0;
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
                COALESCE(SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END), 0) as total_volume,
                COALESCE(SUM(CASE WHEN status = 'completed' THEN fee_amount ELSE 0 END), 0) as total_fees,
                COUNT(CASE WHEN status = 'completed' THEN 1 END) as successful_transactions,
                COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_transactions,
                COUNT(CASE WHEN status IN ('cancelled', 'failed') THEN 1 END) as failed_transactions,
                COALESCE(AVG(CASE WHEN status = 'completed' THEN amount END), 0) as avg_ticket
            FROM {$this->table}
            {$dateFilter}
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetch();
    }
}
