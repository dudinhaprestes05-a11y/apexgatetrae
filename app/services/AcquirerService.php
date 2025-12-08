<?php

require_once __DIR__ . '/../models/Acquirer.php';
require_once __DIR__ . '/../models/AcquirerAccount.php';
require_once __DIR__ . '/../models/SellerAcquirerAccount.php';
require_once __DIR__ . '/../models/Log.php';

class AcquirerService {
    private $acquirerModel;
    private $accountModel;
    private $sellerAccountModel;
    private $logModel;

    public function __construct() {
        $this->acquirerModel = new Acquirer();
        $this->accountModel = new AcquirerAccount();
        $this->sellerAccountModel = new SellerAcquirerAccount();
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
            } elseif ($acquirer['code'] === 'velana') {
                require_once __DIR__ . '/VelanaService.php';
                $velana = new VelanaService($acquirer);

                $response = $velana->createPixTransaction($data);
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
            } elseif ($acquirer['code'] === 'velana') {
                require_once __DIR__ . '/VelanaService.php';
                $velana = new VelanaService($acquirer);

                $response = $velana->createTransfer($data);
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
            } elseif ($acquirer['code'] === 'velana') {
                require_once __DIR__ . '/VelanaService.php';
                $velana = new VelanaService($acquirer);

                if ($isCashout) {
                    $response = $velana->consultTransfer($transactionId);
                } else {
                    $response = $velana->consultTransaction($transactionId);
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

    public function consultTransactionByAccount($transactionData, $isCashout = false) {
        try {
            if (!isset($transactionData['acquirer_code'])) {
                throw new Exception('Missing acquirer_code in transaction data');
            }

            if (!isset($transactionData['acquirer_transaction_id'])) {
                throw new Exception('Missing acquirer_transaction_id');
            }

            $accountData = [
                'id' => $transactionData['acquirer_id'] ?? null,
                'code' => $transactionData['acquirer_code'],
                'api_url' => $transactionData['api_url'] ?? '',
                'client_id' => $transactionData['client_id'] ?? null,
                'client_secret' => $transactionData['client_secret'] ?? null,
                'merchant_id' => $transactionData['merchant_id'] ?? null
            ];

            $this->logModel->info('acquirer', 'Consulting transaction with account', [
                'transaction_id' => $transactionData['transaction_id'] ?? null,
                'acquirer_transaction_id' => $transactionData['acquirer_transaction_id'],
                'account_id' => $transactionData['acquirer_account_id'] ?? null,
                'account_name' => $transactionData['account_name'] ?? null,
                'acquirer_code' => $transactionData['acquirer_code'],
                'is_cashout' => $isCashout
            ]);

            if ($transactionData['acquirer_code'] === 'podpay') {
                require_once __DIR__ . '/PodPayService.php';
                $podpay = new PodPayService($accountData);

                if ($isCashout) {
                    $response = $podpay->consultTransfer($transactionData['acquirer_transaction_id']);
                } else {
                    $response = $podpay->consultTransaction($transactionData['acquirer_transaction_id']);
                }
            } elseif ($transactionData['acquirer_code'] === 'velana') {
                require_once __DIR__ . '/VelanaService.php';
                $velana = new VelanaService($accountData);

                if ($isCashout) {
                    $response = $velana->consultTransfer($transactionData['acquirer_transaction_id']);
                } else {
                    $response = $velana->consultTransaction($transactionData['acquirer_transaction_id']);
                }
            } else {
                $response = $this->sendRequest($accountData, "/pix/consult/{$transactionData['acquirer_transaction_id']}", null, 'GET');
            }

            if ($response['success']) {
                $this->logModel->info('acquirer', 'Transaction consultation successful', [
                    'transaction_id' => $transactionData['transaction_id'] ?? null,
                    'account_id' => $transactionData['acquirer_account_id'] ?? null,
                    'status' => $response['data']['status'] ?? null
                ]);

                return [
                    'success' => true,
                    'data' => $response['data']
                ];
            } else {
                throw new Exception($response['error'] ?? 'Unknown error from acquirer');
            }

        } catch (Exception $e) {
            $this->logModel->error('acquirer', 'Failed to consult transaction with account', [
                'transaction_id' => $transactionData['transaction_id'] ?? null,
                'account_id' => $transactionData['acquirer_account_id'] ?? null,
                'acquirer_transaction_id' => $transactionData['acquirer_transaction_id'] ?? null,
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

        return $acquirer;
    }

    public function selectAccountForSeller($sellerId, $amount, $transactionType = 'cashin', $excludeAccountIds = []) {
        $hasSellerAccounts = $this->sellerAccountModel->hasSellerAccounts($sellerId);

        if ($hasSellerAccounts) {
            $sellerAccountIds = $this->sellerAccountModel->getAccountsBySeller($sellerId);

            $sellerAccountIds = array_values(array_diff($sellerAccountIds, $excludeAccountIds));

            if (empty($sellerAccountIds)) {
                $this->logModel->error('acquirer', 'No available seller-specific accounts', [
                    'seller_id' => $sellerId,
                    'amount' => $amount,
                    'transaction_type' => $transactionType,
                    'excluded_ids' => $excludeAccountIds
                ]);
                return null;
            }

            $this->logModel->info('acquirer', 'Selecting from seller accounts in priority order', [
                'seller_id' => $sellerId,
                'account_ids' => $sellerAccountIds,
                'amount' => $amount
            ]);

            $account = $this->accountModel->getNextAccountFromList(
                $sellerAccountIds,
                $amount,
                $transactionType
            );

            if ($account) {
                $this->logModel->info('acquirer', 'Seller-specific account selected', [
                    'seller_id' => $sellerId,
                    'account_id' => $account['id'],
                    'account_name' => $account['name'],
                    'acquirer_code' => $account['acquirer_code'],
                    'amount' => $amount
                ]);

                return $account;
            }
        }

        $account = $this->accountModel->getNextAccountForSellerWithAmount(
            $sellerId,
            $amount,
            $transactionType,
            $excludeAccountIds
        );

        if (!$account) {
            $this->logModel->error('acquirer', 'No available account found for seller with amount', [
                'seller_id' => $sellerId,
                'amount' => $amount,
                'transaction_type' => $transactionType,
                'excluded_ids' => $excludeAccountIds,
                'has_seller_accounts' => $hasSellerAccounts
            ]);
            return null;
        }

        $this->logModel->info('acquirer', 'Account selected for seller', [
            'seller_id' => $sellerId,
            'account_id' => $account['id'],
            'account_name' => $account['name'],
            'acquirer_code' => $account['acquirer_code'],
            'amount' => $amount,
            'used_default_selection' => !$hasSellerAccounts
        ]);

        return $account;
    }

    public function executeWithFallback($sellerId, $transactionType, $callable, $data) {
        $excludeAccountIds = [];
        $maxAttempts = 5;
        $attempt = 0;
        $lastError = null;
        $amount = $data['amount'] ?? 0;

        while ($attempt < $maxAttempts) {
            $attempt++;

            $account = $this->selectAccountForSeller($sellerId, $amount, $transactionType, $excludeAccountIds);

            if (!$account) {
                $this->logModel->error('acquirer', 'No more accounts available for fallback', [
                    'seller_id' => $sellerId,
                    'transaction_type' => $transactionType,
                    'attempt' => $attempt,
                    'last_error' => $lastError
                ]);

                return [
                    'success' => false,
                    'error' => $lastError ?? 'No available accounts',
                    'account_id' => null
                ];
            }

            $acquirer = [
                'id' => $account['acquirer_id'],
                'code' => $account['acquirer_code'],
                'api_url' => $account['base_url'],
                'client_id' => $account['client_id'],
                'client_secret' => $account['client_secret'],
                'merchant_id' => $account['merchant_id']
            ];

            $this->logModel->info('acquirer', 'Attempting transaction with account', [
                'seller_id' => $sellerId,
                'account_id' => $account['id'],
                'account_name' => $account['name'],
                'attempt' => $attempt
            ]);

            $result = call_user_func($callable, $acquirer, $data);

            if ($result['success']) {
                $this->accountModel->markAccountUsed($account['id'], $data['amount'] ?? 0);

                $this->logModel->info('acquirer', 'Transaction successful with account', [
                    'seller_id' => $sellerId,
                    'account_id' => $account['id'],
                    'attempt' => $attempt
                ]);

                return [
                    'success' => true,
                    'data' => $result['data'],
                    'account_id' => $account['id']
                ];
            }

            $lastError = $result['error'] ?? 'Unknown error';
            $isRetryable = $this->isRetryableError($lastError);

            $this->logModel->warning('acquirer', 'Transaction failed with account', [
                'seller_id' => $sellerId,
                'account_id' => $account['id'],
                'attempt' => $attempt,
                'error' => $lastError,
                'is_retryable' => $isRetryable
            ]);

            if ($isRetryable) {
                $excludeAccountIds[] = $account['id'];
            } else {
                return [
                    'success' => false,
                    'error' => $lastError,
                    'account_id' => $account['id']
                ];
            }
        }

        return [
            'success' => false,
            'error' => $lastError ?? 'Maximum attempts reached',
            'account_id' => null
        ];
    }

    private function isRetryableError($error) {
        $retryableErrors = [
            'saldo insuficiente',
            'insufficient balance',
            'insufficient funds',
            'limit exceeded',
            'timeout',
            'connection error',
            'service unavailable'
        ];

        $errorLower = strtolower($error);

        foreach ($retryableErrors as $retryable) {
            if (strpos($errorLower, $retryable) !== false) {
                return true;
            }
        }

        return false;
    }

    public function createPixCashinWithFallback($sellerId, $data) {
        return $this->executeWithFallback($sellerId, 'cashin', function($acquirer, $data) {
            return $this->createPixCashin($acquirer, $data);
        }, $data);
    }

    public function createPixCashoutWithFallback($sellerId, $data) {
        return $this->executeWithFallback($sellerId, 'cashout', function($acquirer, $data) {
            return $this->createPixCashout($acquirer, $data);
        }, $data);
    }

    public function getAccountForTransaction($accountId) {
        $account = $this->accountModel->getAccountWithAcquirer($accountId);

        if (!$account) {
            return null;
        }

        return [
            'id' => $account['acquirer_id'],
            'acquirer_id' => $account['acquirer_id'],
            'code' => $account['acquirer_code'],
            'api_url' => $account['base_url'],
            'client_id' => $account['client_id'],
            'client_secret' => $account['client_secret'],
            'merchant_id' => $account['merchant_id']
        ];
    }
}
