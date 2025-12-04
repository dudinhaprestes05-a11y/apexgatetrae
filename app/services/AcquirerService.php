<?php

require_once __DIR__ . '/../models/Acquirer.php';
require_once __DIR__ . '/../models/Log.php';

class AcquirerService {
    private $acquirerModel;
    private $logModel;

    public function __construct() {
        $this->acquirerModel = new Acquirer();
        $this->logModel = new Log();
    }

    public function createPixCashin($acquirer, $data) {
        $startTime = microtime(true);

        $this->logModel->info('acquirer', 'Creating PIX cashin', [
            'acquirer_id' => $acquirer['id'],
            'acquirer_code' => $acquirer['code'],
            'amount' => $data['amount']
        ]);

        try {
            if ($acquirer['code'] === 'podpay') {
                require_once __DIR__ . '/PodPayService.php';
                $podpay = new PodPayService($acquirer);

                $response = $podpay->createPixTransaction($data);
            } else {
                $payload = [
                    'transaction_id' => $data['transaction_id'],
                    'amount' => $data['amount'],
                    'pix_type' => $data['pix_type'] ?? 'dynamic',
                    'expires_at' => $data['expires_at'] ?? null,
                    'metadata' => $data['metadata'] ?? []
                ];

                $response = $this->sendRequest($acquirer, '/pix/cashin', $payload);
            }

            $responseTime = (microtime(true) - $startTime) * 1000;
            $this->acquirerModel->updateAvgResponseTime($acquirer['id'], $responseTime);

            if ($response['success']) {
                $this->logModel->info('acquirer', 'PIX cashin created successfully', [
                    'acquirer_id' => $acquirer['id'],
                    'transaction_id' => $data['transaction_id'],
                    'response_time' => $responseTime
                ]);

                return [
                    'success' => true,
                    'data' => $response['data']
                ];
            } else {
                throw new Exception($response['error'] ?? 'Unknown error from acquirer');
            }

        } catch (Exception $e) {
            $this->logModel->error('acquirer', 'Failed to create PIX cashin', [
                'acquirer_id' => $acquirer['id'],
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public function createPixCashout($acquirer, $data) {
        $startTime = microtime(true);

        $this->logModel->info('acquirer', 'Creating PIX cashout', [
            'acquirer_id' => $acquirer['id'],
            'acquirer_code' => $acquirer['code'],
            'amount' => $data['amount']
        ]);

        try {
            if ($acquirer['code'] === 'podpay') {
                require_once __DIR__ . '/PodPayService.php';
                $podpay = new PodPayService($acquirer);

                $response = $podpay->createTransfer($data);
            } else {
                $payload = [
                    'transaction_id' => $data['transaction_id'],
                    'amount' => $data['amount'],
                    'pix_key' => $data['pix_key'],
                    'pix_key_type' => $data['pix_key_type'],
                    'beneficiary_name' => $data['beneficiary_name'],
                    'beneficiary_document' => $data['beneficiary_document']
                ];

                $response = $this->sendRequest($acquirer, '/pix/cashout', $payload);
            }

            $responseTime = (microtime(true) - $startTime) * 1000;
            $this->acquirerModel->updateAvgResponseTime($acquirer['id'], $responseTime);

            if ($response['success']) {
                $this->logModel->info('acquirer', 'PIX cashout created successfully', [
                    'acquirer_id' => $acquirer['id'],
                    'transaction_id' => $data['transaction_id'],
                    'response_time' => $responseTime
                ]);

                return [
                    'success' => true,
                    'data' => $response['data']
                ];
            } else {
                throw new Exception($response['error'] ?? 'Unknown error from acquirer');
            }

        } catch (Exception $e) {
            $this->logModel->error('acquirer', 'Failed to create PIX cashout', [
                'acquirer_id' => $acquirer['id'],
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public function consultTransaction($acquirer, $transactionId, $isCashout = false) {
        try {
            if ($acquirer['code'] === 'podpay') {
                require_once __DIR__ . '/PodPayService.php';
                $podpay = new PodPayService($acquirer);

                if ($isCashout) {
                    $response = $podpay->consultTransfer($transactionId);
                } else {
                    $response = $podpay->consultTransaction($transactionId);
                }
            } else {
                $response = $this->sendRequest($acquirer, "/pix/consult/{$transactionId}", null, 'GET');
            }

            if ($response['success']) {
                return [
                    'success' => true,
                    'data' => $response['data']
                ];
            } else {
                throw new Exception($response['error'] ?? 'Unknown error from acquirer');
            }

        } catch (Exception $e) {
            $this->logModel->error('acquirer', 'Failed to consult transaction', [
                'acquirer_id' => $acquirer['id'],
                'transaction_id' => $transactionId,
                'is_cashout' => $isCashout,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    private function sendRequest($acquirer, $endpoint, $payload = null, $method = 'POST') {
        $url = rtrim($acquirer['api_url'], '/') . $endpoint;

        $ch = curl_init($url);

        $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
            'User-Agent: Gateway-PIX/1.0'
        ];

        if ($acquirer['api_key']) {
            $headers[] = 'X-API-Key: ' . $acquirer['api_key'];
        }

        if ($acquirer['api_secret'] && $payload) {
            $signature = generateHmacSignature($payload, $acquirer['api_secret']);
            $headers[] = 'X-Signature: ' . $signature;
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2
        ]);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($payload) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            }
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);

        curl_close($ch);

        if ($error) {
            throw new Exception("cURL Error: {$error}");
        }

        if ($httpCode < 200 || $httpCode >= 300) {
            throw new Exception("HTTP {$httpCode}: {$response}");
        }

        $decoded = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Invalid JSON response from acquirer");
        }

        return $decoded;
    }

    public function selectAcquirer($amount, $excludeIds = []) {
        $acquirer = $this->acquirerModel->getNextAvailableAcquirer($excludeIds);

        if (!$acquirer) {
            $this->logModel->error('acquirer', 'No available acquirer found', [
                'amount' => $amount,
                'excluded_ids' => $excludeIds
            ]);
            return null;
        }

        if (!$this->acquirerModel->checkDailyLimit($acquirer['id'], $amount)) {
            $this->logModel->warning('acquirer', 'Acquirer daily limit exceeded', [
                'acquirer_id' => $acquirer['id'],
                'amount' => $amount
            ]);

            $excludeIds[] = $acquirer['id'];
            return $this->selectAcquirer($amount, $excludeIds);
        }

        return $acquirer;
    }
}
