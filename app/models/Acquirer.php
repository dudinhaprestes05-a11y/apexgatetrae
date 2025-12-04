<?php

require_once __DIR__ . '/BaseModel.php';

class Acquirer extends BaseModel {
    protected $table = 'acquirers';

    public function findByCode($code) {
        return $this->findBy('code', $code);
    }

    public function getActiveAcquirers() {
        return $this->where(['status' => 'active']);
    }

    public function getAllWithAccountCount() {
        $sql = "
            SELECT a.*,
                COUNT(aa.id) as account_count,
                COUNT(CASE WHEN aa.is_active = 1 THEN 1 END) as active_account_count
            FROM acquirers a
            LEFT JOIN acquirer_accounts aa ON aa.acquirer_id = a.id
            GROUP BY a.id
            ORDER BY a.name ASC
        ";

        return $this->query($sql);
    }

    public function getNextAvailableAcquirer($excludeIds = []) {
        $sql = "
            SELECT * FROM acquirers
            WHERE status = 'active'
        ";

        $params = [];

        if (!empty($excludeIds)) {
            $placeholders = implode(',', array_fill(0, count($excludeIds), '?'));
            $sql .= " AND id NOT IN ({$placeholders})";
            $params = $excludeIds;
        }

        $sql .= " ORDER BY priority_order ASC, success_rate DESC LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetch();
    }

    public function updateSuccessRate($acquirerId) {
        $sql = "
            UPDATE acquirers a
            SET success_rate = (
                SELECT
                    ROUND(
                        (COUNT(CASE WHEN status IN ('paid', 'completed') THEN 1 END) * 100.0) /
                        NULLIF(COUNT(*), 0),
                        2
                    )
                FROM (
                    SELECT status FROM pix_cashin WHERE acquirer_id = a.id
                    UNION ALL
                    SELECT status FROM pix_cashout WHERE acquirer_id = a.id
                ) AS transactions
            )
            WHERE a.id = ?
        ";

        return $this->execute($sql, [$acquirerId]);
    }

    public function updateAvgResponseTime($acquirerId, $responseTime) {
        $sql = "
            UPDATE acquirers
            SET avg_response_time = (
                (avg_response_time * 9 + ?) / 10
            )
            WHERE id = ?
        ";

        return $this->execute($sql, [$responseTime, $acquirerId]);
    }

    public function getStatistics($acquirerId, $startDate = null, $endDate = null) {
        $params = [$acquirerId];
        $dateFilter = '';

        if ($startDate && $endDate) {
            $dateFilter = " AND created_at BETWEEN ? AND ?";
            $params[] = $startDate;
            $params[] = $endDate;
        }

        $sql = "
            SELECT
                COUNT(*) as total_transactions,
                COALESCE(SUM(amount), 0) as total_amount,
                COUNT(CASE WHEN status = 'paid' THEN 1 END) as successful_transactions,
                COUNT(CASE WHEN status = 'failed' THEN 1 END) as failed_transactions
            FROM pix_cashin
            WHERE acquirer_id = ?
            {$dateFilter}
        ";

        return $this->query($sql, $params)[0] ?? [];
    }
}
