<?php

require_once __DIR__ . '/../models/Notification.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Seller.php';

class NotificationService {
    private $notificationModel;
    private $userModel;
    private $sellerModel;

    public function __construct() {
        $this->notificationModel = new Notification();
        $this->userModel = new User();
        $this->sellerModel = new Seller();
    }

    public function notifyDocumentRejected($sellerId, $documentType, $reason) {
        $seller = $this->sellerModel->find($sellerId);

        if (!$seller) {
            return false;
        }

        $user = $this->userModel->findBy('seller_id', $sellerId);

        $title = 'Documento Rejeitado';
        $message = "Seu documento '{$documentType}' foi rejeitado. Motivo: {$reason}";
        $link = '/seller/documents';

        $this->notificationModel->createNotification(
            $user['id'] ?? null,
            $sellerId,
            'document_rejected',
            $title,
            $message,
            $link
        );

        $this->sendEmail($seller['email'], $title, $message);

        return true;
    }

    public function notifyDocumentApproved($sellerId, $documentType) {
        $seller = $this->sellerModel->find($sellerId);

        if (!$seller) {
            return false;
        }

        $user = $this->userModel->findBy('seller_id', $sellerId);

        $title = 'Documento Aprovado';
        $message = "Seu documento '{$documentType}' foi aprovado!";
        $link = '/seller/documents';

        $this->notificationModel->createNotification(
            $user['id'] ?? null,
            $sellerId,
            'document_approved',
            $title,
            $message,
            $link
        );

        return true;
    }

    public function notifyAccountApproved($sellerId, $apiKey, $apiSecret) {
        $seller = $this->sellerModel->find($sellerId);

        if (!$seller) {
            return false;
        }

        $user = $this->userModel->findBy('seller_id', $sellerId);

        $title = 'Conta Aprovada!';
        $message = "Parabéns! Sua conta foi aprovada e já está ativa. Você já pode começar a usar nossa API.";
        $link = '/seller/dashboard';

        $this->notificationModel->createNotification(
            $user['id'] ?? null,
            $sellerId,
            'account_approved',
            $title,
            $message,
            $link
        );

        $emailMessage = $message . "\n\nSuas credenciais de API:\nAPI Key: {$apiKey}\nAPI Secret: {$apiSecret}\n\nGuarde essas informações em local seguro!";
        $this->sendEmail($seller['email'], $title, $emailMessage);

        return true;
    }

    public function notifyAccountRejected($sellerId, $reason) {
        $seller = $this->sellerModel->find($sellerId);

        if (!$seller) {
            return false;
        }

        $user = $this->userModel->findBy('seller_id', $sellerId);

        $title = 'Conta Rejeitada';
        $message = "Sua solicitação de cadastro foi rejeitada. Motivo: {$reason}";
        $link = '/seller/profile';

        $this->notificationModel->createNotification(
            $user['id'] ?? null,
            $sellerId,
            'account_rejected',
            $title,
            $message,
            $link
        );

        $this->sendEmail($seller['email'], $title, $message);

        return true;
    }

    public function notifyNewSellerRegistration($sellerId) {
        $seller = $this->sellerModel->find($sellerId);

        if (!$seller) {
            return false;
        }

        $admins = $this->userModel->where(['role' => 'admin', 'status' => 'active']);

        $title = 'Novo Cadastro de Seller';
        $message = "Um novo seller se cadastrou: {$seller['name']} ({$seller['email']})";
        $link = "/admin/sellers/view/{$sellerId}";

        foreach ($admins as $admin) {
            $this->notificationModel->createNotification(
                $admin['id'],
                null,
                'info',
                $title,
                $message,
                $link
            );
        }

        return true;
    }

    public function notifyLowBalance($sellerId, $balance) {
        $seller = $this->sellerModel->find($sellerId);

        if (!$seller) {
            return false;
        }

        $user = $this->userModel->findBy('seller_id', $sellerId);

        $title = 'Saldo Baixo';
        $message = "Seu saldo está baixo: R$ " . number_format($balance, 2, ',', '.');
        $link = '/seller/cashout';

        $this->notificationModel->createNotification(
            $user['id'] ?? null,
            $sellerId,
            'warning',
            $title,
            $message,
            $link
        );

        return true;
    }

    private function sendEmail($to, $subject, $message) {
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $headers = "From: " . APP_NAME . " <noreply@" . parse_url(BASE_URL, PHP_URL_HOST) . ">\r\n";
        $headers .= "Reply-To: noreply@" . parse_url(BASE_URL, PHP_URL_HOST) . "\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

        $fullMessage = $message . "\n\n---\n" . APP_NAME . "\n" . BASE_URL;

        return mail($to, $subject, $fullMessage, $headers);
    }
}
