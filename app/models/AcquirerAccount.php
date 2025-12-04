<?php

require_once __DIR__ . '/BaseModel.php';

class AcquirerAccount extends BaseModel {
    protected $table = 'acquirer_accounts';

    public function getByAcquirer($acquirerId) {
        return $this->where(['acquirer_id' => $acquirerId], 'name ASC');
    }

    public function getActiveByAcquirer($acquirerId) {
        return $this->where([
            'acquirer_id' => $acquirerId,
            'is_active' => true
        ], 'name ASC');
    }

    public function getAccountWithAcquirer($accountId) {
        $sql = "
            SELECT aa.*, a.name as acquirer_name, a.code as acquirer_code,
                   a.base_url, a.supports_cashin, a.supports_cashout
            FROM acquirer_accounts aa
            JOIN acquirers a ON a.id = aa.acquirer_id
            WHERE aa.id = ?
        ";

        $result = $this->query($sql, [$accountId]);
        return $result[0] ?? null;
    }

    public function getAccountsBySeller($sellerId, $transactionType = null) {
        $sql = "
            SELECT aa.*, a.name as acquirer_name, a.code as acquirer_code,
                   a.base_url, saa.priority, saa.distribution_strategy,
                   saa.percentage_allocation, saa.total_transactions,
                   saa.total_volume, saa.last_used_at
            FROM seller_acquirer_accounts saa
            JOIN acquirer_accounts aa ON aa.id = saa.acquirer_account_id
            JOIN acquirers a ON a.id = aa.acquirer_id
            WHERE saa.seller_id = ?
                AND saa.is_active = true
                AND aa.is_active = true
                AND a.is_active = true
        ";

        $params = [$sellerId];

        if ($transactionType === 'cashin') {
            $sql .= " AND a.supports_cashin = true";
        } elseif ($transactionType === 'cashout') {
            $sql .= " AND a.supports_cashout = true";
        }

        $sql .= " ORDER BY saa.priority ASC, saa.last_used_at ASC";

        return $this->query($sql, $params);
    }

    public function getNextAccountForSeller($sellerId, $transactionType = 'cashin', $excludeAccountIds = []) {
        $sql = "
            SELECT aa.*, a.name as acquirer_name, a.code as acquirer_code,
                   a.base_url, saa.priority, saa.distribution_strategy,
                   saa.percentage_allocation, saa.total_transactions
            FROM seller_acquirer_accounts saa
            JOIN acquirer_accounts aa ON aa.id = saa.acquirer_account_id
            JOIN acquirers a ON a.id = aa.acquirer_id
            WHERE saa.seller_id = ?
                AND saa.is_active = true
                AND aa.is_active = true
                AND a.is_active = true
        ";

        $params = [$sellerId];

        if ($transactionType === 'cashin') {
            $sql .= " AND a.supports_cashin = true";
        } elseif ($transactionType === 'cashout') {
            $sql .= " AND a.supports_cashout = true";
        }

        if (!empty($excludeAccountIds)) {
            $placeholders = implode(',', array_fill(0, count($excludeAccountIds), '?'));
            $sql .= " AND aa.id NOT IN ({$placeholders})";
            $params = array_merge($params, $excludeAccountIds);
        }

        $sql .= "
            ORDER BY
                CASE
                    WHEN saa.distribution_strategy = 'priority_only' THEN saa.priority
                    WHEN saa.distribution_strategy = 'least_used' THEN saa.total_transactions
                    ELSE saa.priority
                END ASC,
                saa.last_used_at ASC
            LIMIT 1
        ";

        $result = $this->query($sql, $params);
        return $result[0] ?? null;
    }

    public function updateBalance($accountId, $balance) {
        return $this->execute(
            "UPDATE acquirer_accounts SET balance = ?, updated_at = NOW() WHERE id = ?",
            [$balance, $accountId]
        );
    }

    public function markAccountUsed($accountId, $amount) {
        $sql = "
            UPDATE seller_acquirer_accounts
            SET total_transactions = total_transactions + 1,
                total_volume = total_volume + ?,
                last_used_at = NOW(),
                updated_at = NOW()
            WHERE acquirer_account_id = ?
        ";

        return $this->execute($sql, [$amount, $accountId]);
    }

    public function getAccountsBySellerWithStats($sellerId) {
        $sql = "
            SELECT saa.*, aa.name as account_name, aa.balance,
                   aa.is_active as account_active, a.name as acquirer_name,
                   a.code as acquirer_code
            FROM seller_acquirer_accounts saa
            JOIN acquirer_accounts aa ON aa.id = saa.acquirer_account_id
            JOIN acquirers a ON a.id = aa.acquirer_id
            WHERE saa.seller_id = ?
            ORDER BY saa.priority ASC
        ";

        return $this->query($sql, [$sellerId]);
    }

    public function assignToSeller($sellerId, $accountId, $priority = 1, $strategy = 'priority_only', $percentage = 0) {
        $sql = "
            INSERT INTO seller_acquirer_accounts
                (seller_id, acquirer_account_id, priority, distribution_strategy, percentage_allocation)
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                priority = VALUES(priority),
                distribution_strategy = VALUES(distribution_strategy),
                percentage_allocation = VALUES(percentage_allocation),
                updated_at = NOW()
        ";

        return $this->execute($sql, [$sellerId, $accountId, $priority, $strategy, $percentage]);
    }

    public function removeFromSeller($sellerId, $accountId) {
        return $this->execute(
            "DELETE FROM seller_acquirer_accounts WHERE seller_id = ? AND acquirer_account_id = ?",
            [$sellerId, $accountId]
        );
    }

    public function toggleSellerAccountStatus($sellerId, $accountId, $isActive) {
        return $this->execute(
            "UPDATE seller_acquirer_accounts SET is_active = ?, updated_at = NOW() WHERE seller_id = ? AND acquirer_account_id = ?",
            [$isActive, $sellerId, $accountId]
        );
    }

    public function getAccountsByAcquirer($acquirerId) {
        $sql = "
            SELECT aa.*,
                COUNT(DISTINCT pc.id) as total_transactions,
                COALESCE(AVG(CASE WHEN pc.status = 'approved' THEN 1 ELSE 0 END) * 100, 0) as success_rate,
                COALESCE(SUM(pc.amount), 0) as total_volume
            FROM acquirer_accounts aa
            LEFT JOIN pix_cashin pc ON pc.acquirer_account_id = aa.id
            WHERE aa.acquirer_id = ?
            GROUP BY aa.id
            ORDER BY aa.priority ASC, aa.created_at ASC
        ";

        return $this->query($sql, [$acquirerId]);
    }

    public function unsetDefaultForAcquirer($acquirerId) {
        return $this->execute(
            "UPDATE acquirer_accounts SET is_default = false, updated_at = NOW() WHERE acquirer_id = ?",
            [$acquirerId]
        );
    }
}
