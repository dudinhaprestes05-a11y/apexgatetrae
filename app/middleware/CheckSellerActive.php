<?php

require_once __DIR__ . '/CheckAuth.php';
require_once __DIR__ . '/../models/Seller.php';

class CheckSellerActive {
    public static function handle() {
        CheckAuth::handle();

        if (!CheckAuth::isSeller()) {
            http_response_code(403);
            echo "Acesso negado.";
            exit;
        }

        $sellerId = CheckAuth::sellerId();

        if (!$sellerId) {
            header('Location: /login');
            exit;
        }

        $sellerModel = new Seller();
        $seller = $sellerModel->find($sellerId);

        if (!$seller) {
            header('Location: /login');
            exit;
        }

        if ($seller['status'] === 'pending') {
            if (!in_array($_SERVER['REQUEST_URI'], ['/seller/documents', '/seller/profile', '/logout'])) {
                header('Location: /seller/documents');
                exit;
            }
        }

        if ($seller['status'] === 'blocked') {
            http_response_code(403);
            echo "Sua conta está bloqueada. Entre em contato com o suporte.";
            exit;
        }

        if ($seller['status'] === 'rejected') {
            http_response_code(403);
            echo "Sua conta foi rejeitada. Entre em contato com o suporte.";
            exit;
        }

        return true;
    }
}
