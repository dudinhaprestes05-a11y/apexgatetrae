<?php

require_once __DIR__ . '/BaseModel.php';

class Log extends BaseModel {
    protected $table = 'logs';

    private $logLevels = [
        'debug' => 1,
        'info' => 2,
        'warning' => 3,
        'error' => 4,
        'critical' => 5
    ];

    private function shouldLog($level) {
        $configuredLevel = strtolower(getenv('LOG_LEVEL') ?: LOG_LEVEL);

        if (!isset($this->logLevels[$level]) || !isset($this->logLevels[$configuredLevel])) {
            return true;
        }

        return $this->logLevels[$level] >= $this->logLevels[$configuredLevel];
    }

    public function log($level, $category, $message, $context = null, $userId = null, $sellerId = null) {
        if (!$this->shouldLog($level)) {
            return false;
        }

        $data = [
            'level' => $level,
            'category' => $category,
            'message' => $message,
            'context' => $context ? json_encode($context) : null,
            'user_id' => $userId,
            'seller_id' => $sellerId,
            'ip_address' => getClientIp(),
            'user_agent' => getUserAgent()
        ];

        return $this->create($data);
    }

    public function debug($category, $message, $context = null) {
        return $this->log('debug', $category, $message, $context);
    }

    public function info($category, $message, $context = null) {
        return $this->log('info', $category, $message, $context);
    }

    public function warning($category, $message, $context = null) {
        return $this->log('warning', $category, $message, $context);
    }

    public function error($category, $message, $context = null) {
        return $this->log('error', $category, $message, $context);
    }

    public function critical($category, $message, $context = null) {
        return $this->log('critical', $category, $message, $context);
    }

    public function getLogsByCategory($category, $limit = 100) {
        return $this->where(['category' => $category], 'created_at DESC', $limit);
    }

    public function getLogsBySeller($sellerId, $limit = 100) {
        return $this->where(['seller_id' => $sellerId], 'created_at DESC', $limit);
    }

    public function getRecentLogs($limit = 100, $level = null) {
        if ($level) {
            return $this->where(['level' => $level], 'created_at DESC', $limit);
        }

        return $this->all('created_at DESC', $limit);
    }

    public function cleanOldLogs($days = 30) {
        $sql = "DELETE FROM {$this->table} WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)";
        return $this->execute($sql, [$days]);
    }
}
