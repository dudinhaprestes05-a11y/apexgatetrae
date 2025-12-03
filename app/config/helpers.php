<?php

function generateTransactionId($prefix = 'TXN') {
    return $prefix . '_' . date('YmdHis') . '_' . bin2hex(random_bytes(8));
}

function generateApiKey() {
    return 'sk_' . (APP_ENV === 'production' ? 'live' : 'test') . '_' . bin2hex(random_bytes(24));
}

function generateApiSecret() {
    return bin2hex(random_bytes(32));
}

function generateHmacSignature($data, $secret) {
    $payload = is_array($data) ? json_encode($data) : $data;
    return hash_hmac('sha256', $payload, $secret);
}

function verifyHmacSignature($data, $signature, $secret) {
    $expectedSignature = generateHmacSignature($data, $secret);
    return hash_equals($expectedSignature, $signature);
}

function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function errorResponse($message, $code = 400, $details = null) {
    $response = [
        'success' => false,
        'error' => [
            'code' => $code,
            'message' => $message,
        ]
    ];

    if ($details !== null) {
        $response['error']['details'] = $details;
    }

    jsonResponse($response, $code);
}

function successResponse($data = null, $message = null) {
    $response = ['success' => true];

    if ($message !== null) {
        $response['message'] = $message;
    }

    if ($data !== null) {
        $response['data'] = $data;
    }

    jsonResponse($response, 200);
}

function validateCpfCnpj($document) {
    $document = preg_replace('/[^0-9]/', '', $document);

    if (strlen($document) == 11) {
        return validateCpf($document);
    } elseif (strlen($document) == 14) {
        return validateCnpj($document);
    }

    return false;
}

function validateCpf($cpf) {
    if (strlen($cpf) != 11 || preg_match('/^(\d)\1+$/', $cpf)) {
        return false;
    }

    for ($t = 9; $t < 11; $t++) {
        $d = 0;
        for ($c = 0; $c < $t; $c++) {
            $d += $cpf[$c] * (($t + 1) - $c);
        }
        $d = ((10 * $d) % 11) % 10;
        if ($cpf[$c] != $d) {
            return false;
        }
    }

    return true;
}

function validateCnpj($cnpj) {
    if (strlen($cnpj) != 14 || preg_match('/^(\d)\1+$/', $cnpj)) {
        return false;
    }

    $length = strlen($cnpj) - 2;
    $numbers = substr($cnpj, 0, $length);
    $digits = substr($cnpj, $length);
    $sum = 0;
    $pos = $length - 7;

    for ($i = $length; $i >= 1; $i--) {
        $sum += $numbers[$length - $i] * $pos--;
        if ($pos < 2) {
            $pos = 9;
        }
    }

    $result = $sum % 11 < 2 ? 0 : 11 - $sum % 11;

    if ($result != $digits[0]) {
        return false;
    }

    $length = $length + 1;
    $numbers = substr($cnpj, 0, $length);
    $sum = 0;
    $pos = $length - 7;

    for ($i = $length; $i >= 1; $i--) {
        $sum += $numbers[$length - $i] * $pos--;
        if ($pos < 2) {
            $pos = 9;
        }
    }

    $result = $sum % 11 < 2 ? 0 : 11 - $sum % 11;

    return $result == $digits[1];
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function sanitizeDocument($document) {
    return preg_replace('/[^0-9]/', '', $document);
}

function formatMoney($amount) {
    return 'R$ ' . number_format($amount, 2, ',', '.');
}

function formatDocument($document) {
    $document = sanitizeDocument($document);

    if (strlen($document) == 11) {
        return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $document);
    } elseif (strlen($document) == 14) {
        return preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $document);
    }

    return $document;
}

function getClientIp() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
    } else {
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
}

function getUserAgent() {
    return $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
}

function redirect($url) {
    header("Location: $url");
    exit;
}

function view($viewName, $data = []) {
    extract($data);
    $viewPath = APP_PATH . "/views/{$viewName}.php";

    if (!file_exists($viewPath)) {
        throw new Exception("View not found: {$viewName}");
    }

    require $viewPath;
}

function asset($path) {
    return BASE_URL . '/public/assets/' . ltrim($path, '/');
}

function url($path = '') {
    return BASE_URL . '/' . ltrim($path, '/');
}

function isAjax() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
}

function csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function old($key, $default = '') {
    return $_SESSION['_old_input'][$key] ?? $default;
}

function hasError($key) {
    return isset($_SESSION['_errors'][$key]);
}

function getError($key) {
    $error = $_SESSION['_errors'][$key] ?? null;
    unset($_SESSION['_errors'][$key]);
    return $error;
}

function setFlash($key, $message) {
    $_SESSION['_flash'][$key] = $message;
}

function getFlash($key) {
    $message = $_SESSION['_flash'][$key] ?? null;
    unset($_SESSION['_flash'][$key]);
    return $message;
}

function calculateFee($amount, $feePercentage, $feeFixed) {
    return ($amount * $feePercentage) + $feeFixed;
}

function calculateNetAmount($amount, $fee) {
    return $amount - $fee;
}

function getAllHeadersCaseInsensitive() {
    $headers = [];

    if (function_exists('getallheaders')) {
        $rawHeaders = getallheaders();
        if ($rawHeaders) {
            foreach ($rawHeaders as $key => $value) {
                $headers[ucwords(strtolower($key), '-')] = $value;
            }
            if (!isset($headers['Authorization'])) {
                if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
                    $headers['Authorization'] = $_SERVER['HTTP_AUTHORIZATION'];
                } elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
                    $headers['Authorization'] = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
                }
            }
            return $headers;
        }
    }

    foreach ($_SERVER as $key => $value) {
        if (strpos($key, 'HTTP_') === 0) {
            $headerKey = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($key, 5)))));
            $headers[$headerKey] = $value;
        } elseif (in_array($key, ['CONTENT_TYPE', 'CONTENT_LENGTH'])) {
            $headerKey = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', $key))));
            $headers[$headerKey] = $value;
        }
    }

    if (!isset($headers['Authorization'])) {
        if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $headers['Authorization'] = $_SERVER['HTTP_AUTHORIZATION'];
        } elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            $headers['Authorization'] = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
        }
    }

    return $headers;
}
