<?php

session_start();

require_once __DIR__ . '/app/config/config.php';
require_once __DIR__ . '/app/config/database.php';
require_once __DIR__ . '/app/config/helpers.php';

if (MAINTENANCE_MODE && !in_array(getClientIp(), ALLOWED_IPS)) {
    http_response_code(503);
    echo "System under maintenance. Please try again later.";
    exit;
}

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

$uri = rtrim($uri, '/');
if (empty($uri)) {
    $uri = '/';
}

try {
    if ($uri === '/login' && $method === 'GET') {
        require_once __DIR__ . '/app/controllers/web/AuthController.php';
        $controller = new AuthController();
        $controller->showLogin();
    }
    elseif ($uri === '/login' && $method === 'POST') {
        require_once __DIR__ . '/app/controllers/web/AuthController.php';
        $controller = new AuthController();
        $controller->login();
    }
    elseif ($uri === '/register' && $method === 'GET') {
        require_once __DIR__ . '/app/controllers/web/AuthController.php';
        $controller = new AuthController();
        $controller->showRegister();
    }
    elseif ($uri === '/register' && $method === 'POST') {
        require_once __DIR__ . '/app/controllers/web/AuthController.php';
        $controller = new AuthController();
        $controller->register();
    }
    elseif ($uri === '/logout') {
        require_once __DIR__ . '/app/controllers/web/AuthController.php';
        $controller = new AuthController();
        $controller->logout();
    }
    elseif ($uri === '/seller/dashboard') {
        require_once __DIR__ . '/app/controllers/web/SellerController.php';
        $controller = new SellerController();
        $controller->dashboard();
    }
    elseif ($uri === '/seller/documents') {
        require_once __DIR__ . '/app/controllers/web/SellerController.php';
        $controller = new SellerController();
        $controller->documents();
    }
    elseif ($uri === '/seller/documents/upload' && $method === 'POST') {
        require_once __DIR__ . '/app/controllers/web/SellerController.php';
        $controller = new SellerController();
        $controller->uploadDocument();
    }
    elseif ($uri === '/seller/profile') {
        require_once __DIR__ . '/app/controllers/web/SellerController.php';
        $controller = new SellerController();
        $controller->profile();
    }
    elseif ($uri === '/seller/profile/update' && $method === 'POST') {
        require_once __DIR__ . '/app/controllers/web/SellerController.php';
        $controller = new SellerController();
        $controller->updateProfile();
    }
    elseif ($uri === '/seller/transactions') {
        require_once __DIR__ . '/app/controllers/web/SellerController.php';
        $controller = new SellerController();
        $controller->transactions();
    }
    elseif (preg_match('#^/seller/transactions/(\d+)/(cashin|cashout)$#', $uri, $matches)) {
        require_once __DIR__ . '/app/controllers/web/SellerController.php';
        $controller = new SellerController();
        $controller->transactionDetails($matches[1], $matches[2]);
    }
    elseif ($uri === '/seller/api-credentials') {
        require_once __DIR__ . '/app/controllers/web/SellerController.php';
        $controller = new SellerController();
        $controller->apiCredentials();
    }
    elseif ($uri === '/seller/api-credentials/regenerate' && $method === 'POST') {
        require_once __DIR__ . '/app/controllers/web/SellerController.php';
        $controller = new SellerController();
        $controller->regenerateApiKey();
    }
    elseif ($uri === '/seller/notifications') {
        require_once __DIR__ . '/app/controllers/web/SellerController.php';
        $controller = new SellerController();
        $controller->notifications();
    }
    elseif (preg_match('#^/seller/notifications/(\d+)/read$#', $uri, $matches) && $method === 'POST') {
        require_once __DIR__ . '/app/controllers/web/SellerController.php';
        $controller = new SellerController();
        $controller->markNotificationAsRead($matches[1]);
    }
    elseif ($uri === '/admin/dashboard') {
        require_once __DIR__ . '/app/controllers/web/AdminController.php';
        $controller = new AdminController();
        $controller->dashboard();
    }
    elseif ($uri === '/admin/sellers') {
        require_once __DIR__ . '/app/controllers/web/AdminController.php';
        $controller = new AdminController();
        $controller->sellers();
    }
    elseif (preg_match('#^/admin/sellers/view/(\d+)$#', $uri, $matches)) {
        require_once __DIR__ . '/app/controllers/web/AdminController.php';
        $controller = new AdminController();
        $controller->sellerDetails($matches[1]);
    }
    elseif (preg_match('#^/admin/sellers/(\d+)/approve$#', $uri, $matches) && $method === 'POST') {
        require_once __DIR__ . '/app/controllers/web/AdminController.php';
        $controller = new AdminController();
        $controller->approveSeller($matches[1]);
    }
    elseif (preg_match('#^/admin/sellers/(\d+)/reject$#', $uri, $matches) && $method === 'POST') {
        require_once __DIR__ . '/app/controllers/web/AdminController.php';
        $controller = new AdminController();
        $controller->rejectSeller($matches[1]);
    }
    elseif ($uri === '/admin/documents') {
        require_once __DIR__ . '/app/controllers/web/AdminController.php';
        $controller = new AdminController();
        $controller->documents();
    }
    elseif (preg_match('#^/admin/documents/view/(\d+)$#', $uri, $matches)) {
        require_once __DIR__ . '/app/controllers/web/AdminController.php';
        $controller = new AdminController();
        $controller->viewDocument($matches[1]);
    }
    elseif (preg_match('#^/admin/documents/(\d+)/approve$#', $uri, $matches) && $method === 'POST') {
        require_once __DIR__ . '/app/controllers/web/AdminController.php';
        $controller = new AdminController();
        $controller->approveDocument($matches[1]);
    }
    elseif (preg_match('#^/admin/documents/(\d+)/reject$#', $uri, $matches) && $method === 'POST') {
        require_once __DIR__ . '/app/controllers/web/AdminController.php';
        $controller = new AdminController();
        $controller->rejectDocument($matches[1]);
    }
    elseif ($uri === '/admin/transactions') {
        require_once __DIR__ . '/app/controllers/web/AdminController.php';
        $controller = new AdminController();
        $controller->transactions();
    }
    elseif ($uri === '/admin/acquirers') {
        require_once __DIR__ . '/app/controllers/web/AdminController.php';
        $controller = new AdminController();
        $controller->acquirers();
    }
    elseif ($uri === '/admin/logs') {
        require_once __DIR__ . '/app/controllers/web/AdminController.php';
        $controller = new AdminController();
        $controller->logs();
    }
    elseif (preg_match('#^/api/pix/create$#', $uri) && $method === 'POST') {
        require_once __DIR__ . '/app/controllers/api/PixController.php';
        $controller = new PixController();
        $controller->create();
    }
    elseif (preg_match('#^/api/pix/consult$#', $uri) && $method === 'GET') {
        require_once __DIR__ . '/app/controllers/api/PixController.php';
        $controller = new PixController();
        $controller->consult();
    }
    elseif (preg_match('#^/api/pix/list$#', $uri) && $method === 'GET') {
        require_once __DIR__ . '/app/controllers/api/PixController.php';
        $controller = new PixController();
        $controller->listTransactions();
    }
    elseif (preg_match('#^/api/cashout/create$#', $uri) && $method === 'POST') {
        require_once __DIR__ . '/app/controllers/api/CashoutController.php';
        $controller = new CashoutController();
        $controller->create();
    }
    elseif (preg_match('#^/api/cashout/consult$#', $uri) && $method === 'GET') {
        require_once __DIR__ . '/app/controllers/api/CashoutController.php';
        $controller = new CashoutController();
        $controller->consult();
    }
    elseif (preg_match('#^/api/cashout/list$#', $uri) && $method === 'GET') {
        require_once __DIR__ . '/app/controllers/api/CashoutController.php';
        $controller = new CashoutController();
        $controller->listTransactions();
    }
    elseif (preg_match('#^/api/webhook/acquirer$#', $uri) && $method === 'POST') {
        require_once __DIR__ . '/app/controllers/api/WebhookController.php';
        $controller = new WebhookController();
        $controller->receiveFromAcquirer();
    }
    elseif ($uri === '/' || $uri === '') {
        header('Location: /login');
        exit;
    }
    else {
        http_response_code(404);
        echo "Página não encontrada";
    }

} catch (Exception $e) {
    http_response_code(500);

    $response = [
        'error' => 'Internal server error'
    ];

    if (APP_ENV === 'development') {
        $response['message'] = $e->getMessage();
        $response['trace'] = $e->getTraceAsString();
    }

    echo json_encode($response);

    require_once __DIR__ . '/app/models/Log.php';
    $logModel = new Log();
    $logModel->critical('system', 'Uncaught exception', [
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ]);
}
