<?php

require_once __DIR__ . '/../models/Acquirer.php';
require_once __DIR__ . '/../models/Log.php';

class VelanaService {
    private $acquirer;
    private $logModel;
    private $apiUrl;
    private $authToken;

    public function __construct($acquirer = null) {
        $this->logModel = new Log();

        if ($acquirer) {
            $this->acquirer = $acquirer;
            $this->apiUrl = rtrim($acquirer['api_url'] ?? $acquirer['base_url'] ?? 'https://api.velana.com.br', '/');

            $secretKey = $acquirer['client_id'] ?? $acquirer['api_key'] ?? '';

            $this->authToken = base64_encode($secretKey . ':x');
        }
    }

    public function createPixTransaction($data) {
        $startTime = microtime(true);

        $this->logModel->info('velana', 'Creating PIX transaction', [
            'transaction_id' => $data['transaction_id'],
            'amount' => $data['amount']
        ]);

        try {
            $webhookUrl = BASE_URL . '/api/webhook/acquirer?acquirer=velana';

            $customerDocument = preg_replace('/[^0-9]/', '', $data['customer']['document'] ?? '00000000000');
            $documentType = strlen($customerDocument) === 11 ? 'cpf' : 'cnpj';

            $customerEmail = $data['customer']['email'] ?? null;
            if (!$customerEmail || !filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
                $randomId = substr(md5(uniqid()), 0, 8);
                $customerEmail = "cliente{$randomId}@gmail.com";
            }

            $payload = [
                'amount' => (int)($data['amount'] * 100),
                'currency' => 'BRL',
                'paymentMethod' => 'pix',
                'items' => [
                    [
                        'title' => $data['title'] ?? 'Recebimento',
                        'unitPrice' => (int)($data['amount'] * 100),
                        'quantity' => 1,
                        'tangible' => false
                    ]
                ],
                'customer' => [
                    'name' => $data['customer']['name'] ?? 'Cliente',
                    'email' => $customerEmail,
                    'document' => [
                        'number' => $customerDocument,
                        'type' => $documentType
                    ]
                ],
                'postbackUrl' => $webhookUrl
            ];

            if (isset($data['external_id'])) {
                $payload['metadata'] = ['external_id' => $data['external_id']];
            }

            $response = $this->sendRequest('/v1/transactions', $payload, 'POST');

            $responseTime = (microtime(true) - $startTime) * 1000;

            if (isset($response['id'])) {
                $acquirerModel = new Acquirer();
                if (isset($this->acquirer['id'])) {
                    $acquirerModel->updateAvgResponseTime($this->acquirer['id'], $responseTime);
                }

                $this->logModel->info('velana', 'PIX transaction created successfully', [
                    'transaction_id' => $data['transaction_id'],
                    'velana_id' => $response['id'],
                    'secure_id' => $response['secureId'] ?? null,
                    'response_time' => $responseTime
                ]);

                $qrcode = $response['pix']['qrcode'] ?? null;

                return [
                    'success' => true,
                    'data' => [
                        'acquirer_transaction_id' => $response['id'],
                        'qrcode' => $qrcode,
                        'qrcode_base64' => null,
                        'expiration_date' => $response['pix']['expirationDate'] ?? null,
                        'status' => $response['status'] ?? 'waiting_payment',
                        'amount' => $response['amount'] / 100,
                        'secure_id' => $response['secureId'] ?? null,
                        'raw_response' => $response
                    ]
                ];
            } else {
                throw new Exception('Invalid response from Velana: ' . json_encode($response));
            }

        } catch (Exception $e) {
            $this->logModel->error('velana', 'Failed to create PIX transaction', [
                'transaction_id' => $data['transaction_id'],
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public function createTransfer($data) {
        $startTime = microtime(true);

        $this->logModel->info('velana', 'Creating transfer', [
            'transaction_id' => $data['transaction_id'],
            'amount' => $data['amount']
        ]);

        try {
            $webhookUrl = BASE_URL . '/api/webhook/acquirer?acquirer=velana';

            $payload = [
                'method' => 'pix',
                'amount' => (int)($data['amount'] * 100),
                'pixKey' => $data['pix_key'],
                'pixKeyType' => $data['pix_key_type'],
                'postbackUrl' => $webhookUrl
            ];

            if (isset($data['external_id'])) {
                $payload['metadata'] = ['external_id' => $data['external_id']];
            }

            $response = $this->sendRequest('/v1/transfers', $payload, 'POST');

            $responseTime = (microtime(true) - $startTime) * 1000;

            if (isset($response['id'])) {
                $acquirerModel = new Acquirer();
                if (isset($this->acquirer['id'])) {
                    $acquirerModel->updateAvgResponseTime($this->acquirer['id'], $responseTime);
                }

                $this->logModel->info('velana', 'Transfer created successfully', [
                    'transaction_id' => $data['transaction_id'],
                    'transfer_id' => $response['id'],
                    'response_time' => $responseTime
                ]);

                return [
                    'success' => true,
                    'data' => [
                        'acquirer_transaction_id' => $response['id'],
                        'amount' => $response['amount'] / 100,
                        'status' => $response['status'] ?? 'in_analysis',
                        'pix_key' => $response['pixKey'] ?? $data['pix_key'],
                        'pix_key_type' => $response['pixKeyType'] ?? $data['pix_key_type'],
                        'raw_response' => $response
                    ]
                ];
            } else {
                throw new Exception('Invalid response from Velana: ' . json_encode($response));
            }

        } catch (Exception $e) {
            $this->logModel->error('velana', 'Failed to create transfer', [
                'transaction_id' => $data['transaction_id'],
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public function consultTransaction($transactionId) {
        try {
            $this->logModel->info('velana', 'Consulting transaction', [
                'transaction_id' => $transactionId
            ]);

            $response = $this->sendRequest("/v1/transactions/{$transactionId}", null, 'GET');

            if (isset($response['id'])) {
                $this->logModel->info('velana', 'Transaction consulted successfully', [
                    'transaction_id' => $transactionId,
                    'status' => $response['status']
                ]);

                return [
                    'success' => true,
                    'data' => [
                        'id' => $response['id'],
                        'secure_id' => $response['secureId'] ?? null,
                        'status' => $response['status'],
                        'amount' => $response['amount'] / 100,
                        'paid_amount' => isset($response['paidAmount']) ? $response['paidAmount'] / 100 : null,
                        'pix' => $response['pix'] ?? null,
                        'end_to_end_id' => $response['pix']['end2EndId'] ?? null,
                        'qrcode' => $response['pix']['qrcode'] ?? null,
                        'raw_response' => $response
                    ]
                ];
            } else {
                throw new Exception('Transaction not found');
            }

        } catch (Exception $e) {
            $this->logModel->error('velana', 'Failed to consult transaction', [
                'transaction_id' => $transactionId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public function consultTransfer($transferId) {
        try {
            $this->logModel->info('velana', 'Consulting transfer', [
                'transfer_id' => $transferId
            ]);

            $response = $this->sendRequest("/v1/transfers/{$transferId}", null, 'GET');

            if (isset($response['id'])) {
                $this->logModel->info('velana', 'Transfer consulted successfully', [
                    'transfer_id' => $transferId,
                    'status' => $response['status']
                ]);

                return [
                    'success' => true,
                    'data' => [
                        'id' => $response['id'],
                        'status' => $response['status'],
                        'amount' => $response['amount'] / 100,
                        'receipt_url' => $response['receiptUrl'] ?? null,
                        'raw_response' => $response
                    ]
                ];
            } else {
                throw new Exception('Transfer not found');
            }

        } catch (Exception $e) {
            $this->logModel->error('velana', 'Failed to consult transfer', [
                'transfer_id' => $transferId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    private function sendRequest($endpoint, $payload = null, $method = 'POST') {
        $url = $this->apiUrl . $endpoint;

        $ch = curl_init($url);

        $jsonPayload = $payload ? json_encode($payload) : null;

        $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Basic ' . $this->authToken,
            'User-Agent: Gateway-PIX/1.0'
        ];

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_CUSTOMREQUEST => $method
        ]);

        if ($method === 'POST' && $jsonPayload) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonPayload);
        }

        $this->logModel->info('velana', 'Sending request', [
            'url' => $url,
            'method' => $method,
            'payload' => $payload
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);

        curl_close($ch);

        if ($error) {
            $this->logModel->error('velana', 'cURL error', [
                'error' => $error,
                'url' => $url
            ]);
            throw new Exception("cURL Error: {$error}");
        }

        if ($httpCode < 200 || $httpCode >= 300) {
            $errorMessage = "HTTP {$httpCode}";
            if ($response) {
                $decoded = json_decode($response, true);
                if (isset($decoded['message'])) {
                    $errorMessage .= ": " . $decoded['message'];
                } elseif (isset($decoded['error'])) {
                    $errorMessage .= ": " . $decoded['error'];
                } else {
                    $errorMessage .= ": " . substr($response, 0, 200);
                }
            }

            $this->logModel->error('velana', 'HTTP error response', [
                'http_code' => $httpCode,
                'response' => $response,
                'url' => $url
            ]);

            throw new Exception($errorMessage);
        }

        $decoded = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Invalid JSON response from Velana: {$response}");
        }

        return $decoded;
    }

    public function parseWebhook($payload) {
        try {
            if (!isset($payload['type'])) {
                throw new Exception('Missing webhook type');
            }

            if ($payload['type'] === 'transaction') {
                return $this->parseTransactionWebhook($payload);
            } elseif ($payload['type'] === 'transfer') {
                return $this->parseWithdrawWebhook($payload);
            } else {
                throw new Exception('Unknown webhook type: ' . $payload['type']);
            }

        } catch (Exception $e) {
            $this->logModel->error('velana', 'Failed to parse webhook', [
                'error' => $e->getMessage(),
                'payload' => $payload
            ]);

            return null;
        }
    }

    private function parseTransactionWebhook($payload) {
        $data = $payload['data'] ?? [];

        return [
            'transaction_type' => 'cashin',
            'acquirer_transaction_id' => $data['id'] ?? null,
            'status' => $this->mapTransactionStatus($data['status'] ?? 'waiting_payment'),
            'amount' => isset($data['amount']) ? $data['amount'] / 100 : null,
            'paid_amount' => isset($data['paidAmount']) ? $data['paidAmount'] / 100 : null,
            'end_to_end_id' => $data['pix']['end2EndId'] ?? null,
            'qrcode' => $data['pix']['qrcode'] ?? null,
            'expiration_date' => $data['pix']['expirationDate'] ?? null,
            'secure_id' => $data['secureId'] ?? null,
            'raw_data' => $data
        ];
    }

    private function parseWithdrawWebhook($payload) {
        $data = $payload['data'] ?? [];

        return [
            'transaction_type' => 'cashout',
            'acquirer_transaction_id' => $data['id'] ?? null,
            'status' => $this->mapWithdrawStatus($data['status'] ?? 'in_analysis'),
            'amount' => isset($data['amount']) ? $data['amount'] / 100 : null,
            'receipt_url' => $data['receiptUrl'] ?? null,
            'raw_data' => $data
        ];
    }

    private function mapTransactionStatus($velanaStatus) {
        $statusMap = [
            'waiting_payment' => 'waiting_payment',
            'paid' => 'paid',
            'refused' => 'failed',
            'cancelled' => 'cancelled',
            'expired' => 'expired'
        ];

        return $statusMap[$velanaStatus] ?? 'waiting_payment';
    }

    private function mapWithdrawStatus($velanaStatus) {
        $statusMap = [
            'in_analysis' => 'processing',
            'pending' => 'processing',
            'processing' => 'processing',
            'success' => 'completed',
            'failed' => 'failed',
            'cancelled' => 'cancelled'
        ];

        return $statusMap[$velanaStatus] ?? 'processing';
    }
}
