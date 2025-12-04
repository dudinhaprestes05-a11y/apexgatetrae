<?php

require_once __DIR__ . '/BaseModel.php';

class SellerAcquirerAccount extends BaseModel {
    protected $table = 'seller_acquirer_accounts';

    public function getBySellerWithDetails($sellerId) {
        $sql = "SELECT
                    saa.*,
                    aa.name as account_name,
                    aa.merchant_id as account_identifier,
                    aa.is_active as account_active,
                    aa.balance as account_balance,
                    a.name as acquirer_name,
                    a.code as acquirer_type
                FROM {$this->table} saa
                INNER JOIN acquirer_accounts aa ON saa.acquirer_account_id = aa.id
                INNER JOIN acquirers a ON aa.acquirer_id = a.id
                WHERE saa.seller_id = ?
                ORDER BY saa.priority ASC, saa.id ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $sellerId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getActiveAccountsForSeller($sellerId, $acquirerType = null) {
        $sql = "SELECT
                    saa.*,
                    aa.name as account_name,
                    aa.merchant_id as account_identifier,
                    aa.client_id,
                    aa.client_secret,
                    aa.balance,
                    a.name as acquirer_name,
                    a.code as acquirer_type,
                    a.base_url,
                    a.supports_cashin,
                    a.supports_cashout
                FROM {$this->table} saa
                INNER JOIN acquirer_accounts aa ON saa.acquirer_account_id = aa.id
                INNER JOIN acquirers a ON aa.acquirer_id = a.id
                WHERE saa.seller_id = ?
                AND saa.is_active = 1
                AND aa.is_active = 1";

        if ($acquirerType) {
            $sql .= " AND a.code = ?";
        }

        $sql .= " ORDER BY saa.priority ASC, saa.id ASC";

        $stmt = $this->db->prepare($sql);

        if ($acquirerType) {
            $stmt->bind_param('is', $sellerId, $acquirerType);
        } else {
            $stmt->bind_param('i', $sellerId);
        }

        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function assignAccountToSeller($sellerId, $acquirerAccountId, $priority = 1, $isActive = true) {
        $sql = "INSERT INTO {$this->table}
                (seller_id, acquirer_account_id, priority, is_active)
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                priority = VALUES(priority),
                is_active = VALUES(is_active),
                updated_at = CURRENT_TIMESTAMP";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('iiii', $sellerId, $acquirerAccountId, $priority, $isActive);
        return $stmt->execute();
    }

    public function removeAccountFromSeller($sellerId, $acquirerAccountId) {
        $sql = "DELETE FROM {$this->table}
                WHERE seller_id = ? AND acquirer_account_id = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('ii', $sellerId, $acquirerAccountId);
        return $stmt->execute();
    }

    public function updatePriority($id, $priority) {
        return $this->update($id, ['priority' => $priority]);
    }

    public function toggleActive($id) {
        $item = $this->findById($id);
        if (!$item) return false;

        return $this->update($id, ['is_active' => !$item['is_active']]);
    }

    public function reorderPriorities($sellerId, $accountIds) {
        $this->db->begin_transaction();

        try {
            foreach ($accountIds as $priority => $accountId) {
                $sql = "UPDATE {$this->table}
                        SET priority = ?, updated_at = CURRENT_TIMESTAMP
                        WHERE seller_id = ? AND acquirer_account_id = ?";

                $stmt = $this->db->prepare($sql);
                $priorityValue = $priority + 1;
                $stmt->bind_param('iii', $priorityValue, $sellerId, $accountId);
                $stmt->execute();
            }

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollback();
            return false;
        }
    }

    public function hasSellerAccounts($sellerId) {
        $sql = "SELECT COUNT(*) as count
                FROM {$this->table}
                WHERE seller_id = ? AND is_active = 1";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $sellerId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();

        return $result['count'] > 0;
    }

    public function getAccountsBySeller($sellerId) {
        $sql = "SELECT acquirer_account_id
                FROM {$this->table}
                WHERE seller_id = ? AND is_active = 1";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $sellerId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        return array_column($result, 'acquirer_account_id');
    }
}
