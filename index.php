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
    elseif ($uri === '/seller/webhooks') {
        require_once __DIR__ . '/app/controllers/web/SellerController.php';
        $controller = new SellerController();
        $controller->webhooks();
    }
    elseif ($uri === '/seller/webhooks/update' && $method === 'POST') {
        require_once __DIR__ . '/app/controllers/web/SellerController.php';
        $controller = new SellerController();
        $controller->updateWebhooks();
    }
    elseif ($uri === '/seller/ip-whitelist') {
        require_once __DIR__ . '/app/controllers/web/SellerController.php';
        $controller = new SellerController();
        $controller->ipWhitelist();
    }
    elseif ($uri === '/seller/ip-whitelist/get' && $method === 'GET') {
        require_once __DIR__ . '/app/controllers/web/SellerController.php';
        $controller = new SellerController();
        $controller->getIpWhitelistJson();
    }
    elseif ($uri === '/seller/ip-whitelist/add' && $method === 'POST') {
        require_once __DIR__ . '/app/controllers/web/SellerController.php';
        $controller = new SellerController();
        $controller->addIpToWhitelist();
    }
    elseif ($uri === '/seller/ip-whitelist/remove' && $method === 'POST') {
        require_once __DIR__ . '/app/controllers/web/SellerController.php';
        $controller = new SellerController();
        $controller->removeIpFromWhitelist();
    }
    elseif ($uri === '/seller/ip-whitelist/toggle' && $method === 'POST') {
        require_once __DIR__ . '/app/controllers/web/SellerController.php';
        $controller = new SellerController();
        $controller->toggleIpWhitelist();
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
    elseif (preg_match('#^/admin/sellers/(\d+)/fees$#', $uri, $matches) && $method === 'POST') {
        require_once __DIR__ . '/app/controllers/web/AdminController.php';
        $controller = new AdminController();
        $controller->updateSellerFees($matches[1]);
    }
    elseif (preg_match('#^/admin/sellers/(\d+)/limits$#', $uri, $matches) && $method === 'POST') {
        require_once __DIR__ . '/app/controllers/web/AdminController.php';
        $controller = new AdminController();
        $controller->updateSellerLimits($matches[1]);
    }
    elseif (preg_match('#^/admin/sellers/(\d+)/toggle-cashin$#', $uri, $matches) && $method === 'POST') {
        require_once __DIR__ . '/app/controllers/web/AdminController.php';
        $controller = new AdminController();
        $controller->toggleCashin($matches[1]);
    }
    elseif (preg_match('#^/admin/sellers/(\d+)/toggle-cashout$#', $uri, $matches) && $method === 'POST') {
        require_once __DIR__ . '/app/controllers/web/AdminController.php';
        $controller = new AdminController();
        $controller->toggleCashout($matches[1]);
    }
    elseif (preg_match('#^/admin/sellers/(\d+)/block$#', $uri, $matches) && $method === 'POST') {
        require_once __DIR__ . '/app/controllers/web/AdminController.php';
        $controller = new AdminController();
        $controller->blockSeller($matches[1]);
    }
    elseif (preg_match('#^/admin/sellers/(\d+)/unblock$#', $uri, $matches) && $method === 'POST') {
        require_once __DIR__ . '/app/controllers/web/AdminController.php';
        $controller = new AdminController();
        $controller->unblockSeller($matches[1]);
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
    elseif (preg_match('#^/admin/transactions/(\d+)/(cashin|cashout)$#', $uri, $matches)) {
        require_once __DIR__ . '/app/controllers/web/AdminController.php';
        $controller = new AdminController();
        $controller->transactionDetails($matches[1], $matches[2]);
    }
    elseif ($uri === '/admin/acquirers') {
        require_once __DIR__ . '/app/controllers/web/AdminController.php';
        $controller = new AdminController();
        $controller->acquirers();
    }
    elseif (preg_match('#^/admin/acquirers/get/(\d+)$#', $uri, $matches)) {
        require_once __DIR__ . '/app/controllers/web/AdminController.php';
        $controller = new AdminController();
        $controller->getAcquirer($matches[1]);
    }
    elseif ($uri === '/admin/acquirers/create' && $method === 'POST') {
        require_once __DIR__ . '/app/controllers/web/AdminController.php';
        $controller = new AdminController();
        $controller->createAcquirer();
    }
    elseif (preg_match('#^/admin/acquirers/update/(\d+)$#', $uri, $matches) && $method === 'POST') {
        require_once __DIR__ . '/app/controllers/web/AdminController.php';
        $controller = new AdminController();
        $controller->updateAcquirer($matches[1]);
    }
    elseif (preg_match('#^/admin/acquirers/toggle/(\d+)$#', $uri, $matches) && $method === 'POST') {
        require_once __DIR__ . '/app/controllers/web/AdminController.php';
        $controller = new AdminController();
        $controller->toggleAcquirerStatus($matches[1]);
    }
    elseif (preg_match('#^/admin/acquirers/reset-limit/(\d+)$#', $uri, $matches) && $method === 'POST') {
        require_once __DIR__ . '/app/controllers/web/AdminController.php';
        $controller = new AdminController();
        $controller->resetAcquirerLimit($matches[1]);
    }
    elseif (preg_match('#^/admin/acquirers/delete/(\d+)$#', $uri, $matches) && $method === 'POST') {
        require_once __DIR__ . '/app/controllers/web/AdminController.php';
        $controller = new AdminController();
        $controller->deleteAcquirer($matches[1]);
    }
    elseif (preg_match('#^/admin/acquirers/(\d+)/accounts$#', $uri, $matches)) {
        require_once __DIR__ . '/app/controllers/web/AdminController.php';
        $controller = new AdminController();
        $controller->acquirerAccounts($matches[1]);
    }
    elseif (preg_match('#^/admin/acquirers/accounts/get/(\d+)$#', $uri, $matches)) {
        require_once __DIR__ . '/app/controllers/web/AdminController.php';
        $controller = new AdminController();
        $controller->getAcquirerAccount($matches[1]);
    }
    elseif ($uri === '/admin/acquirers/accounts/create' && $method === 'POST') {
        require_once __DIR__ . '/app/controllers/web/AdminController.php';
        $controller = new AdminController();
        $controller->createAcquirerAccount();
    }
    elseif (preg_match('#^/admin/acquirers/accounts/update/(\d+)$#', $uri, $matches) && $method === 'POST') {
        require_once __DIR__ . '/app/controllers/web/AdminController.php';
        $controller = new AdminController();
        $controller->updateAcquirerAccount($matches[1]);
    }
    elseif (preg_match('#^/admin/acquirers/accounts/toggle/(\d+)$#', $uri, $matches) && $method === 'POST') {
        require_once __DIR__ . '/app/controllers/web/AdminController.php';
        $controller = new AdminController();
        $controller->toggleAcquirerAccount($matches[1]);
    }
    elseif (preg_match('#^/admin/acquirers/accounts/reset-limit/(\d+)$#', $uri, $matches) && $method === 'POST') {
        require_once __DIR__ . '/app/controllers/web/AdminController.php';
        $controller = new AdminController();
        $controller->resetAcquirerAccountLimit($matches[1]);
    }
    elseif (preg_match('#^/admin/acquirers/accounts/(\d+)/details$#', $uri, $matches)) {
        require_once __DIR__ . '/app/controllers/web/AdminController.php';
        $controller = new AdminController();
        $controller->viewAcquirerAccountDetails($matches[1]);
    }
    elseif (preg_match('#^/admin/sellers/(\d+)/accounts$#', $uri, $matches)) {
        require_once __DIR__ . '/app/controllers/web/AdminController.php';
        $controller = new AdminController();
        $controller->getSellerAcquirerAccounts($matches[1]);
    }
    elseif (preg_match('#^/admin/sellers/(\d+)/accounts/assign$#', $uri, $matches) && $method === 'POST') {
        require_once __DIR__ . '/app/controllers/web/AdminController.php';
        $controller = new AdminController();
        $controller->assignAccountToSeller($matches[1]);
    }
    elseif (preg_match('#^/admin/sellers/(\d+)/accounts/(\d+)/remove$#', $uri, $matches) && $method === 'POST') {
        require_once __DIR__ . '/app/controllers/web/AdminController.php';
        $controller = new AdminController();
        $controller->removeAccountFromSeller($matches[1], $matches[2]);
    }
    elseif (preg_match('#^/admin/sellers/(\d+)/accounts/(\d+)/toggle$#', $uri, $matches) && $method === 'POST') {
        require_once __DIR__ . '/app/controllers/web/AdminController.php';
        $controller = new AdminController();
        $controller->toggleSellerAccountStatus($matches[1], $matches[2]);
    }
    elseif (preg_match('#^/admin/sellers/(\d+)/accounts/reorder$#', $uri, $matches) && $method === 'POST') {
        require_once __DIR__ . '/app/controllers/web/AdminController.php';
        $controller = new AdminController();
        $controller->updateSellerAccountPriority();
    }
    elseif ($uri === '/admin/accounts/available') {
        require_once __DIR__ . '/app/controllers/web/AdminController.php';
        $controller = new AdminController();
        $controller->getAvailableAccounts();
    }
    elseif ($uri === '/admin/reports') {
        require_once __DIR__ . '/app/controllers/web/AdminController.php';
        $controller = new AdminController();
        $controller->reports();
    }
    elseif ($uri === '/admin/logs') {
        require_once __DIR__ . '/app/controllers/web/AdminController.php';
        $controller = new AdminController();
        $controller->logs();
    }
    elseif ($uri === '/admin/settings') {
        require_once __DIR__ . '/app/controllers/web/AdminController.php';
        $controller = new AdminController();
        $controller->settings();
    }
    elseif ($uri === '/admin/settings/update' && $method === 'POST') {
        require_once __DIR__ . '/app/controllers/web/AdminController.php';
        $controller = new AdminController();
        $controller->updateSettings();
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
