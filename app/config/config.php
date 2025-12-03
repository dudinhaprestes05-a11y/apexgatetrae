<?php

function loadEnv($path) {
    if (!file_exists($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }

        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);

            $value = trim($value, '"\'');

            if (!array_key_exists($name, $_ENV)) {
                putenv("$name=$value");
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
            }
        }
    }
}

loadEnv(dirname(dirname(__DIR__)) . '/.env');

define('APP_NAME', 'Gateway PIX');
define('APP_VERSION', '1.0.0');
define('APP_ENV', getenv('APP_ENV') ?: 'development');

define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'gateway_pix');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_CHARSET', 'utf8mb4');

define('BASE_URL', getenv('BASE_URL') ?: 'http://localhost');
define('BASE_PATH', dirname(dirname(__DIR__)));
define('APP_PATH', BASE_PATH . '/app');
define('PUBLIC_PATH', BASE_PATH . '/public');

define('SESSION_LIFETIME', 7200);
define('SESSION_NAME', 'GATEWAY_PIX_SESSION');

define('API_RATE_LIMIT', 100);
define('API_RATE_WINDOW', 60);

define('WEBHOOK_MAX_RETRIES', 5);
define('WEBHOOK_RETRY_DELAY', 60);

define('PIX_EXPIRATION_MINUTES', 30);

define('LOG_LEVEL', APP_ENV === 'production' ? 'warning' : 'debug');
define('LOG_PATH', BASE_PATH . '/logs');

define('TIMEZONE', 'America/Sao_Paulo');
date_default_timezone_set(TIMEZONE);

define('MAINTENANCE_MODE', false);

$allowed_ips = [
    '127.0.0.1',
    '::1',
];

define('ALLOWED_IPS', $allowed_ips);

ini_set('display_errors', APP_ENV === 'development' ? '1' : '0');
error_reporting(APP_ENV === 'development' ? E_ALL : E_ERROR);
