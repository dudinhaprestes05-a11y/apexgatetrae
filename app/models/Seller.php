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

    public function checkDailyCashoutLimit($sellerId, $amount) {
        $seller = $this->find($sellerId);

        if (!$seller) {
            return false;
        }

        if ($seller['cashout_daily_reset_at'] < date('Y-m-d')) {
            $this->execute(
                "UPDATE sellers SET cashout_daily_used = 0, cashout_daily_reset_at = ? WHERE id = ?",
                [date('Y-m-d'), $sellerId]
            );
            $seller['cashout_daily_used'] = 0;
        }

        if ($seller['cashout_daily_limit'] === null) {
            return true;
        }

        $newUsed = $seller['cashout_daily_used'] + $amount;

        return $newUsed <= $seller['cashout_daily_limit'];
    }

    public function incrementDailyCashoutUsed($sellerId, $amount) {
        $sql = "UPDATE sellers SET cashout_daily_used = cashout_daily_used + ?, updated_at = NOW() WHERE id = ?";
        return $this->execute($sql, [$amount, $sellerId]);
    }

    public function checkTransactionLimits($sellerId, $amount, $type = 'cashin') {
        $seller = $this->find($sellerId);

        if (!$seller) {
            return [
                'valid' => false,
                'error' => 'Seller não encontrado'
            ];
        }

        $minField = $type === 'cashin' ? 'min_cashin_amount' : 'min_cashout_amount';
        $maxField = $type === 'cashin' ? 'max_cashin_amount' : 'max_cashout_amount';

        $minAmount = $seller[$minField];
        $maxAmount = $seller[$maxField];

        if ($minAmount !== null && $amount < $minAmount) {
            return [
                'valid' => false,
                'error' => 'Valor abaixo do mínimo permitido',
                'min_amount' => $minAmount
            ];
        }

        if ($maxAmount !== null && $amount > $maxAmount) {
            return [
                'valid' => false,
                'error' => 'Valor acima do máximo permitido',
                'max_amount' => $maxAmount
            ];
        }

        return ['valid' => true];
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

    public function getIpWhitelist($sellerId) {
        $seller = $this->find($sellerId);
        if (!$seller) {
            return [];
        }

        $whitelist = json_decode($seller['ip_whitelist'] ?? '[]', true);
        return is_array($whitelist) ? $whitelist : [];
    }

    public function addIpToWhitelist($sellerId, $ip, $description = '') {
        $whitelist = $this->getIpWhitelist($sellerId);

        if (count($whitelist) >= 50) {
            return ['success' => false, 'error' => 'Maximum limit of 50 IPs reached'];
        }

        foreach ($whitelist as $entry) {
            if ($entry['ip'] === $ip) {
                return ['success' => false, 'error' => 'IP already exists in whitelist'];
            }
        }

        if (!$this->isValidIpOrCidr($ip)) {
            return ['success' => false, 'error' => 'Invalid IP address or CIDR format'];
        }

        $whitelist[] = [
            'ip' => $ip,
            'description' => $description,
            'added_at' => date('Y-m-d H:i:s')
        ];

        $this->update($sellerId, ['ip_whitelist' => json_encode($whitelist)]);

        return ['success' => true, 'whitelist' => $whitelist];
    }

    public function removeIpFromWhitelist($sellerId, $ip) {
        $whitelist = $this->getIpWhitelist($sellerId);

        $whitelist = array_filter($whitelist, function($entry) use ($ip) {
            return $entry['ip'] !== $ip;
        });

        $whitelist = array_values($whitelist);

        $this->update($sellerId, ['ip_whitelist' => json_encode($whitelist)]);

        return ['success' => true, 'whitelist' => $whitelist];
    }

    public function toggleIpWhitelist($sellerId, $enabled) {
        $this->update($sellerId, ['ip_whitelist_enabled' => $enabled ? 1 : 0]);

        return ['success' => true, 'enabled' => $enabled];
    }

    public function isIpWhitelisted($sellerId, $clientIp) {
        $seller = $this->find($sellerId);

        if (!$seller) {
            return false;
        }

        if (!$seller['ip_whitelist_enabled']) {
            return true;
        }

        $whitelist = $this->getIpWhitelist($sellerId);

        if (empty($whitelist)) {
            return false;
        }

        foreach ($whitelist as $entry) {
            if ($this->ipMatchesRule($clientIp, $entry['ip'])) {
                return true;
            }
        }

        return false;
    }

    private function isValidIpOrCidr($ip) {
        if (filter_var($ip, FILTER_VALIDATE_IP)) {
            return true;
        }

        if (strpos($ip, '/') !== false) {
            list($subnet, $mask) = explode('/', $ip);

            if (!filter_var($subnet, FILTER_VALIDATE_IP)) {
                return false;
            }

            if (!is_numeric($mask) || $mask < 0 || $mask > 32) {
                return false;
            }

            return true;
        }

        return false;
    }

    private function ipMatchesRule($clientIp, $rule) {
        if ($clientIp === $rule) {
            return true;
        }

        if (strpos($rule, '/') !== false) {
            return $this->ipInCidr($clientIp, $rule);
        }

        return false;
    }

    private function ipInCidr($ip, $cidr) {
        list($subnet, $mask) = explode('/', $cidr);

        $ipLong = ip2long($ip);
        $subnetLong = ip2long($subnet);
        $maskLong = -1 << (32 - (int)$mask);

        return ($ipLong & $maskLong) === ($subnetLong & $maskLong);
    }
}
