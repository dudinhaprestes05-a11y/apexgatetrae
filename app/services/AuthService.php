<?php

require_once __DIR__ . '/../models/Seller.php';
require_once __DIR__ . '/../models/Log.php';

class AuthService {
    private $sellerModel;
    private $logModel;

    public function __construct() {
        $this->sellerModel = new Seller();
        $this->logModel = new Log();
    }

    public function authenticateApiRequest() {
        $authData = $this->getAuthFromHeaders();

        if (!$authData) {
            $debugInfo = ['ip' => getClientIp()];

            if (APP_ENV === 'development') {
                $headers = getAllHeadersCaseInsensitive();
                $debugInfo['available_headers'] = array_keys($headers);
                $debugInfo['has_authorization'] = isset($headers['Authorization']);
            }

            $this->logModel->warning('auth', 'Missing authentication', $debugInfo);
            errorResponse('Authentication is required', 401);
        }

        $seller = null;

        if (isset($authData['type']) && $authData['type'] === 'basic') {
            $seller = $this->authenticateBasicAuth($authData['api_key'], $authData['api_secret']);
        } else {
            $this->logModel->warning('auth', 'API Secret is required', ['api_key' => $authData['api_key'] ?? 'N/A', 'ip' => getClientIp()]);
            errorResponse('API Secret is required for authentication', 401);
        }

        if ($seller['status'] !== 'active') {
            $this->logModel->warning('auth', 'Inactive seller attempted access', [
                'seller_id' => $seller['id'],
                'status' => $seller['status']
            ]);
            errorResponse('Seller account is not active', 403);
        }

        return $seller;
    }

    private function authenticateBasicAuth($apiKey, $apiSecret) {
        $seller = $this->sellerModel->findByApiKey($apiKey);

        if (!$seller) {
            $this->logModel->warning('auth', 'Invalid API Key in Basic Auth', ['api_key' => $apiKey, 'ip' => getClientIp()]);
            errorResponse('Invalid credentials', 401);
        }

        // Hash do API Secret recebido para comparar com o hash armazenado no banco
        $hashedApiSecret = hash('sha256', $apiSecret);

        if (APP_ENV === 'development') {
            error_log('=== CREDENTIAL COMPARISON ===');
            error_log('Received API Key: ' . substr($apiKey, 0, 8) . '...');
            error_log('Received API Secret length: ' . strlen($apiSecret));
            error_log('Received API Secret first 4 chars: ' . substr($apiSecret, 0, 4));
            error_log('Hashed received secret first 4 chars: ' . substr($hashedApiSecret, 0, 4));
            error_log('Stored API Secret (hash) length: ' . strlen($seller['api_secret']));
            error_log('Stored API Secret (hash) first 4 chars: ' . substr($seller['api_secret'], 0, 4));
            error_log('Secrets match: ' . ($seller['api_secret'] === $hashedApiSecret ? 'YES' : 'NO'));
        }

        if (trim($seller['api_secret']) !== trim($hashedApiSecret)) {
            if (APP_ENV === 'development') {
                error_log('!!! AUTHENTICATION FAILED !!!');
                error_log('Seller ID: ' . $seller['id']);
                error_log('Seller Name: ' . $seller['name']);
                error_log('Received Secret (plain): ' . $apiSecret);
                error_log('Received Secret Length: ' . strlen($apiSecret));
                error_log('Received Secret First 16: ' . substr($apiSecret, 0, 16));
                error_log('Received Secret Last 16: ' . substr($apiSecret, -16));
                error_log('');
                error_log('Hashed Received Secret: ' . $hashedApiSecret);
                error_log('Hashed Length: ' . strlen($hashedApiSecret));
                error_log('Hashed First 16: ' . substr($hashedApiSecret, 0, 16));
                error_log('Hashed Last 16: ' . substr($hashedApiSecret, -16));
                error_log('');
                error_log('DB Secret (stored hash): ' . $seller['api_secret']);
                error_log('DB Secret Length: ' . strlen($seller['api_secret']));
                error_log('DB Secret First 16: ' . substr($seller['api_secret'], 0, 16));
                error_log('DB Secret Last 16: ' . substr($seller['api_secret'], -16));
                error_log('');
                error_log('Match: ' . ($seller['api_secret'] === $hashedApiSecret ? 'YES' : 'NO'));
                error_log('Trimmed Match: ' . (trim($seller['api_secret']) === trim($hashedApiSecret) ? 'YES' : 'NO'));

                // Verifica se o secret no banco é texto plano por engano
                $hashOfDbSecret = hash('sha256', $seller['api_secret']);
                error_log('');
                error_log('TEST: Hash do secret do banco: ' . $hashOfDbSecret);
                error_log('TEST: Secret enviado: ' . $apiSecret);
                error_log('TEST: DB secret é texto plano?: ' . ($seller['api_secret'] === $apiSecret ? 'YES (BUG!)' : 'NO'));

                // Compara byte a byte
                $diffCount = 0;
                $maxLen = max(strlen($seller['api_secret']), strlen($hashedApiSecret));
                for ($i = 0; $i < $maxLen && $i < 10; $i++) {
                    $dbChar = isset($seller['api_secret'][$i]) ? $seller['api_secret'][$i] : 'EOF';
                    $hashChar = isset($hashedApiSecret[$i]) ? $hashedApiSecret[$i] : 'EOF';
                    if ($dbChar !== $hashChar) {
                        $diffCount++;
                        error_log("Diff at pos {$i}: DB='{$dbChar}' vs Hash='{$hashChar}'");
                    }
                }
            }

            $this->logModel->warning('auth', 'Invalid API Secret in Basic Auth', [
                'seller_id' => $seller['id'],
                'ip' => getClientIp(),
                'expected_length' => strlen($seller['api_secret']),
                'received_hash_length' => strlen($hashedApiSecret),
                'db_secret_preview' => substr($seller['api_secret'], 0, 8) . '...',
                'hashed_preview' => substr($hashedApiSecret, 0, 8) . '...'
            ]);
            errorResponse('Invalid credentials', 401);
        }

        return $seller;
    }

    private function getAuthFromHeaders() {
        $headers = getAllHeadersCaseInsensitive();

        if (isset($headers['Authorization'])) {
            $auth = $headers['Authorization'];

            if (preg_match('/Basic\s+(.+)/i', $auth, $matches)) {
                $decoded = base64_decode($matches[1]);
                if ($decoded && strpos($decoded, ':') !== false) {
                    list($apiKey, $apiSecret) = explode(':', $decoded, 2);
                    return [
                        'type' => 'basic',
                        'api_key' => $apiKey,
                        'api_secret' => $apiSecret
                    ];
                }
            }

            if (preg_match('/Bearer\s+(.+)/i', $auth, $matches)) {
                return ['api_key' => $matches[1]];
            }
        }

        if (isset($headers['X-Api-Key'])) {
            $apiKey = $headers['X-Api-Key'];
            $apiSecret = $headers['X-Api-Secret'] ?? null;

            if (APP_ENV === 'development') {
                error_log('=== X-API-KEY AUTH DETECTED ===');
                error_log('X-API-Key: ' . substr($apiKey, 0, 8) . '...');
                error_log('X-API-Secret present: ' . ($apiSecret ? 'YES' : 'NO'));
                if ($apiSecret) {
                    error_log('X-API-Secret length: ' . strlen($apiSecret));
                    error_log('X-API-Secret first 4 chars: ' . substr($apiSecret, 0, 4));
                }
            }

            if ($apiSecret) {
                return [
                    'type' => 'basic',
                    'api_key' => $apiKey,
                    'api_secret' => $apiSecret
                ];
            }

            return ['api_key' => $apiKey];
        }

        if (isset($_GET['api_key'])) {
            $apiKey = $_GET['api_key'];
            $apiSecret = $_GET['api_secret'] ?? null;

            if ($apiSecret) {
                return [
                    'type' => 'basic',
                    'api_key' => $apiKey,
                    'api_secret' => $apiSecret
                ];
            }

            return ['api_key' => $apiKey];
        }

        if (APP_ENV === 'development') {
            error_log('Auth headers received: ' . json_encode(array_keys($headers)));
            error_log('$_SERVER keys: ' . json_encode(array_keys($_SERVER)));
        }

        return null;
    }


    public function checkRateLimit($identifier, $endpoint) {
        $db = db();

        $windowStart = date('Y-m-d H:i:00');
        $windowEnd = date('Y-m-d H:i:59', strtotime($windowStart) + API_RATE_WINDOW);

        $stmt = $db->prepare("
            SELECT requests FROM rate_limits
            WHERE identifier = ? AND endpoint = ? AND window_start = ?
        ");

        $stmt->execute([$identifier, $endpoint, $windowStart]);
        $result = $stmt->fetch();

        if ($result) {
            if ($result['requests'] >= API_RATE_LIMIT) {
                $this->logModel->warning('rate_limit', 'Rate limit exceeded', [
                    'identifier' => $identifier,
                    'endpoint' => $endpoint,
                    'requests' => $result['requests']
                ]);
                errorResponse('Rate limit exceeded. Try again later.', 429);
            }

            $stmt = $db->prepare("
                UPDATE rate_limits
                SET requests = requests + 1
                WHERE identifier = ? AND endpoint = ? AND window_start = ?
            ");

            $stmt->execute([$identifier, $endpoint, $windowStart]);
        } else {
            $stmt = $db->prepare("
                INSERT INTO rate_limits (identifier, endpoint, requests, window_start, window_end)
                VALUES (?, ?, 1, ?, ?)
            ");

            $stmt->execute([$identifier, $endpoint, $windowStart, $windowEnd]);
        }

        $db->prepare("DELETE FROM rate_limits WHERE window_end < NOW()")->execute();

        return true;
    }
}
