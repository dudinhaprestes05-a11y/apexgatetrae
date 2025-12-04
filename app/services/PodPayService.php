<?php

require_once __DIR__ . '/../models/Acquirer.php';
require_once __DIR__ . '/../models/Log.php';

class PodPayService {
    private $acquirer;
    private $logModel;
    private $apiUrl;
    private $authToken;
    private $withdrawKey;

    public function __construct($acquirer = null) {
        $this->logModel = new Log();

        if ($acquirer) {
            $this->acquirer = $acquirer;
            $this->apiUrl = rtrim($acquirer['api_url'], '/');

            // Support both account format (client_id/client_secret) and acquirer format (api_key/api_secret)
            $clientId = $acquirer['client_id'] ?? $acquirer['api_key'] ?? '';
            $clientSecret = $acquirer['client_secret'] ?? $acquirer['api_secret'] ?? '';

            $this->authToken = base64_encode($clientId . ':' . $clientSecret);

            // Get withdraw key from config or merchant_id (for accounts)
            $config = json_decode($acquirer['config'] ?? '{}', true) ?? [];
            $this->withdrawKey = $config['withdraw_key'] ?? $acquirer['merchant_id'] ?? null;
        }
    }

    public function createPixTransaction($data) {
        $startTime = microtime(true);

        $this->logModel->info('podpay', 'Creating PIX transaction', [
            'transaction_id' => $data['transaction_id'],
            'amount' => $data['amount']
        ]);

        try {
            $webhookUrl = BASE_URL . '/api/webhook/acquirer?acquirer=' . $this->acquirer['code'];

            $payload = [
                'amount' => (int)($data['amount'] * 100),
                'currency' => 'BRL',
                'paymentMethod' => 'pix',
                'items' => [
                    [
                        'title' => $data['title'] ?? 'Recebimento PIX',
                        'unitPrice' => (int)($data['amount'] * 100),
                        'quantity' => 1,
                        'tangible' => false
                    ]
                ],
                'customer' => [
                    'name' => $data['customer']['name'] ?? 'Cliente',
                    'email' => $data['customer']['email'] ?? 'cliente@example.com',
                    'document' => [
                        'number' => preg_replace('/[^0-9]/', '', $data['customer']['document'] ?? '00000000000'),
                        'type' => strlen(preg_replace('/[^0-9]/', '', $data['customer']['document'] ?? '')) === 11 ? 'cpf' : 'cnpj'
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
                $acquirerModel->updateAvgResponseTime($this->acquirer['id'], $responseTime);

                $this->logModel->info('podpay', 'PIX transaction created successfully', [
                    'transaction_id' => $data['transaction_id'],
                    'secure_id' => $response['id'],
                    'response_time' => $responseTime
                ]);

                // Get QR code
                $qrcode = $response['pix']['qrcode'] ?? null;
                $qrcodeBase64 = $response['pix']['qrcodeBase64'] ?? null;

                // If base64 not provided by acquirer, generate it
                if ($qrcode && !$qrcodeBase64) {
                    $qrcodeBase64 = $this->generateQrCodeBase64($qrcode);
                }

                return [
                    'success' => true,
                    'data' => [
                        'acquirer_transaction_id' => $response['id'],
                        'qrcode' => $qrcode,
                        'qrcode_base64' => $qrcodeBase64,
                        'expiration_date' => $response['pix']['expirationDate'] ?? null,
                        'status' => $response['status'] ?? 'waiting_payment',
                        'amount' => $response['amount'] / 100,
                        'raw_response' => $response
                    ]
                ];
            } else {
                throw new Exception('Invalid response from PodPay: ' . json_encode($response));
            }

        } catch (Exception $e) {
            $this->logModel->error('podpay', 'Failed to create PIX transaction', [
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

        $this->logModel->info('podpay', 'Creating transfer', [
            'transaction_id' => $data['transaction_id'],
            'amount' => $data['amount']
        ]);

        try {
            if (!$this->withdrawKey) {
                throw new Exception('Withdraw key not configured for this acquirer');
            }

            $webhookUrl = BASE_URL . '/api/webhook/acquirer?acquirer=' . $this->acquirer['code'];

            $payload = [
                'method' => 'fiat',
                'amount' => (int)($data['amount'] * 100),
                'pixKey' => $data['pix_key'],
                'pixKeyType' => $data['pix_key_type'],
                'netPayout' => $data['net_payout'] ?? true,
                'postbackUrl' => $webhookUrl
            ];

            if (isset($data['external_id'])) {
                $payload['metadata'] = ['external_id' => $data['external_id']];
            }

            $response = $this->sendRequest('/v1/transfers', $payload, 'POST', [
                'x-withdraw-key' => $this->withdrawKey
            ]);

            $responseTime = (microtime(true) - $startTime) * 1000;

            if (isset($response['id'])) {
                $acquirerModel = new Acquirer();
                $acquirerModel->updateAvgResponseTime($this->acquirer['id'], $responseTime);

                $this->logModel->info('podpay', 'Transfer created successfully', [
                    'transaction_id' => $data['transaction_id'],
                    'transfer_id' => $response['id'],
                    'response_time' => $responseTime
                ]);

                return [
                    'success' => true,
                    'data' => [
                        'acquirer_transaction_id' => $response['id'],
                        'amount' => $response['amount'] / 100,
                        'net_amount' => $response['netAmount'] / 100,
                        'fee' => $response['fee'] / 100,
                        'status' => $response['status'] ?? 'PENDING_QUEUE',
                        'pix_key' => $response['pixKey'] ?? $data['pix_key'],
                        'pix_key_type' => $response['pixKeyType'] ?? $data['pix_key_type'],
                        'raw_response' => $response
                    ]
                ];
            } else {
                throw new Exception('Invalid response from PodPay: ' . json_encode($response));
            }

        } catch (Exception $e) {
            $this->logModel->error('podpay', 'Failed to create transfer', [
                'transaction_id' => $data['transaction_id'],
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public function consultTransaction($secureId) {
        try {
            $response = $this->sendRequest("/v1/transactions/{$secureId}", null, 'GET');

            if (isset($response['id'])) {
                return [
                    'success' => true,
                    'data' => [
                        'secure_id' => $response['id'],
                        'status' => $response['status'],
                        'amount' => $response['amount'] / 100,
                        'pix' => $response['pix'] ?? null,
                        'raw_response' => $response
                    ]
                ];
            } else {
                throw new Exception('Transaction not found');
            }

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public function consultTransfer($transferId) {
        try {
            $response = $this->sendRequest("/v1/transfers/{$transferId}", null, 'GET', [
                'x-withdraw-key' => $this->withdrawKey
            ]);

            if (isset($response['id'])) {
                return [
                    'success' => true,
                    'data' => [
                        'transfer_id' => $response['id'],
                        'status' => $response['status'],
                        'amount' => $response['amount'] / 100,
                        'net_amount' => $response['netAmount'] / 100,
                        'fee' => $response['fee'] / 100,
                        'raw_response' => $response
                    ]
                ];
            } else {
                throw new Exception('Transfer not found');
            }

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public function getAvailableBalance() {
        try {
            $response = $this->sendRequest('/v1/balance/available', null, 'GET');

            if (isset($response['amount'])) {
                return [
                    'success' => true,
                    'data' => [
                        'amount' => $response['amount'] / 100,
                        'waiting_funds' => ($response['waitingFunds'] ?? 0) / 100,
                        'max_antecipable' => ($response['maxAntecipable'] ?? 0) / 100,
                        'reserve' => ($response['reserve'] ?? 0) / 100,
                        'recipient_id' => $response['recipientId'] ?? null,
                        'raw_response' => $response
                    ]
                ];
            } else {
                throw new Exception('Invalid balance response');
            }

        } catch (Exception $e) {
            $this->logModel->error('podpay', 'Failed to get available balance', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    private function sendRequest($endpoint, $payload = null, $method = 'POST', $additionalHeaders = []) {
        $url = $this->apiUrl . $endpoint;

        $ch = curl_init($url);

        $jsonPayload = $payload ? json_encode($payload) : null;

        $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Basic ' . $this->authToken,
            'User-Agent: Gateway-PIX/1.0'
        ];

        foreach ($additionalHeaders as $key => $value) {
            $headers[] = $key . ': ' . $value;
        }

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

        $this->logModel->info('podpay', 'Sending request', [
            'url' => $url,
            'method' => $method,
            'payload' => $payload
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);

        curl_close($ch);

        if ($error) {
            $this->logModel->error('podpay', 'cURL error', [
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

            $this->logModel->error('podpay', 'HTTP error response', [
                'http_code' => $httpCode,
                'response' => $response,
                'url' => $url
            ]);

            throw new Exception($errorMessage);
        }

        $decoded = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Invalid JSON response from PodPay: {$response}");
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
            } elseif ($payload['type'] === 'withdraw') {
                return $this->parseWithdrawWebhook($payload);
            } else {
                throw new Exception('Unknown webhook type: ' . $payload['type']);
            }

        } catch (Exception $e) {
            $this->logModel->error('podpay', 'Failed to parse webhook', [
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
            'end_to_end_id' => $data['pix']['end2EndId'] ?? null,
            'qrcode' => $data['pix']['qrcode'] ?? null,
            'expiration_date' => $data['pix']['expirationDate'] ?? null,
            'raw_data' => $data
        ];
    }

    private function parseWithdrawWebhook($payload) {
        $data = $payload['data'] ?? [];

        return [
            'transaction_type' => 'cashout',
            'acquirer_transaction_id' => $data['id'] ?? null,
            'status' => $this->mapWithdrawStatus($data['status'] ?? 'pending'),
            'amount' => isset($data['amount']) ? $data['amount'] / 100 : null,
            'net_amount' => isset($data['netAmount']) ? $data['netAmount'] / 100 : null,
            'fee' => isset($data['fee']) ? $data['fee'] / 100 : null,
            'raw_data' => $data
        ];
    }

    private function mapTransactionStatus($podpayStatus) {
        $statusMap = [
            'waiting_payment' => 'waiting_payment',
            'pending' => 'pending',
            'approved' => 'paid',
            'paid' => 'paid',
            'refused' => 'failed',
            'cancelled' => 'cancelled',
            'expired' => 'expired'
        ];

        return $statusMap[$podpayStatus] ?? 'pending';
    }

    private function mapWithdrawStatus($podpayStatus) {
        $statusMap = [
            'PENDING_QUEUE' => 'processing',
            'pending' => 'processing',
            'processing' => 'processing',
            'COMPLETED' => 'completed',
            'completed' => 'completed',
            'failed' => 'failed',
            'cancelled' => 'cancelled'
        ];

        return $statusMap[$podpayStatus] ?? 'processing';
    }

    private function generateQrCodeBase64($qrcodeString) {
        try {
            // Use QR Server API (free and reliable alternative)
            $size = 300;
            $url = "https://api.qrserver.com/v1/create-qr-code/?size={$size}x{$size}&data=" . urlencode($qrcodeString);

            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 10,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_FOLLOWLOCATION => true
            ]);

            $imageData = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($curlError || $httpCode !== 200 || !$imageData) {
                $this->logModel->warning('podpay', 'Failed to generate QR code image', [
                    'qrcode_length' => strlen($qrcodeString),
                    'http_code' => $httpCode,
                    'curl_error' => $curlError
                ]);
                return null;
            }

            return base64_encode($imageData);

        } catch (Exception $e) {
            $this->logModel->error('podpay', 'Error generating QR code base64', [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
}
