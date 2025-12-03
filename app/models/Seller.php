<?php

require_once __DIR__ . '/BaseModel.php';

class Seller extends BaseModel {
    protected $table = 'sellers';

    public function findByApiKey($apiKey) {
        return $this->findBy('api_key', $apiKey);
    }

    public function updateBalance($sellerId, $amount) {
        $sql = "UPDATE sellers SET balance = balance + ?, updated_at = NOW() WHERE id = ?";
        return $this->execute($sql, [$amount, $sellerId]);
    }

    public function checkDailyLimit($sellerId, $amount) {
        $seller = $this->find($sellerId);

        if (!$seller) {
            return false;
        }

        if ($seller['daily_reset_at'] < date('Y-m-d')) {
            $this->execute(
                "UPDATE sellers SET daily_used = 0, daily_reset_at = ? WHERE id = ?",
                [date('Y-m-d'), $sellerId]
            );
            $seller['daily_used'] = 0;
        }

        $newUsed = $seller['daily_used'] + $amount;

        return $newUsed <= $seller['daily_limit'];
    }

    public function incrementDailyUsed($sellerId, $amount) {
        $sql = "UPDATE sellers SET daily_used = daily_used + ?, updated_at = NOW() WHERE id = ?";
        return $this->execute($sql, [$amount, $sellerId]);
    }

    public function getStatistics($sellerId, $startDate = null, $endDate = null) {
        $params = [$sellerId];
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
                COALESCE(SUM(fee_amount), 0) as total_fees,
                COALESCE(SUM(net_amount), 0) as total_net
            FROM pix_cashin
            WHERE seller_id = ? AND status = 'paid'
            {$dateFilter}
        ";

        $cashin = $this->query($sql, $params)[0] ?? [];

        $sql = "
            SELECT
                COUNT(*) as total_cashouts,
                COALESCE(SUM(amount), 0) as total_cashout_amount
            FROM pix_cashout
            WHERE seller_id = ? AND status = 'completed'
            {$dateFilter}
        ";

        $cashout = $this->query($sql, $params)[0] ?? [];

        return array_merge($cashin, $cashout);
    }

    public function createSeller($data) {
        $data['api_key'] = generateApiKey();
        $data['api_secret'] = hash('sha256', generateApiSecret());
        $data['daily_reset_at'] = date('Y-m-d');

        return $this->create($data);
    }

    public function regenerateApiCredentials($sellerId) {
        $newApiKey = generateApiKey();
        $newApiSecret = generateApiSecret();

        $this->update($sellerId, [
            'api_key' => $newApiKey,
            'api_secret' => hash('sha256', $newApiSecret)
        ]);

        return [
            'api_key' => $newApiKey,
            'api_secret' => $newApiSecret
        ];
    }

    public function getTopSellers($limit = 10, $dateFrom = null) {
        $params = [];
        $dateFilter = '';

        if ($dateFrom) {
            $dateFilter = "AND pc.created_at >= ?";
            $params[] = $dateFrom;
        }

        $sql = "
            SELECT
                s.id,
                s.name,
                s.email,
                COALESCE(SUM(CASE WHEN pc.status = 'paid' THEN pc.amount ELSE 0 END), 0) as total_volume,
                COUNT(CASE WHEN pc.status = 'paid' THEN 1 END) as total_transactions,
                COALESCE(SUM(CASE WHEN pc.status = 'paid' THEN pc.fee_amount ELSE 0 END), 0) as total_fees,
                s.fee_percentage_cashin as avg_fee_percentage
            FROM sellers s
            LEFT JOIN pix_cashin pc ON s.id = pc.seller_id {$dateFilter}
            WHERE s.status = 'active'
            GROUP BY s.id, s.name, s.email, s.fee_percentage_cashin
            ORDER BY total_volume DESC
            LIMIT ?
        ";

        $params[] = $limit;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }
}
