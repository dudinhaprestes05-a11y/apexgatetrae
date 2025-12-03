<?php

require_once __DIR__ . '/../../models/User.php';
require_once __DIR__ . '/../../models/Seller.php';
require_once __DIR__ . '/../../models/SystemSettings.php';
require_once __DIR__ . '/../../services/NotificationService.php';
require_once __DIR__ . '/../../middleware/CheckAuth.php';

class AuthController {
    private $userModel;
    private $sellerModel;
    private $settingsModel;
    private $notificationService;

    public function __construct() {
        $this->userModel = new User();
        $this->sellerModel = new Seller();
        $this->settingsModel = new SystemSettings();
        $this->notificationService = new NotificationService();
    }

    public function showLogin() {
        if (isset($_SESSION['user_id'])) {
            $this->redirectToDashboard();
        }
        require __DIR__ . '/../../views/auth/login.php';
    }

    public function showRegister() {
        if (isset($_SESSION['user_id'])) {
            $this->redirectToDashboard();
        }
        require __DIR__ . '/../../views/auth/register.php';
    }

    public function login() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /login');
            exit;
        }

        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $_SESSION['error'] = 'Email e senha são obrigatórios';
            header('Location: /login');
            exit;
        }

        $user = $this->userModel->findByEmail($email);

        if (!$user || !password_verify($password, $user['password'])) {
            $_SESSION['error'] = 'Credenciais inválidas';
            header('Location: /login');
            exit;
        }

        if ($user['status'] !== 'active') {
            $_SESSION['error'] = 'Sua conta está inativa';
            header('Location: /login');
            exit;
        }

        $this->userModel->update($user['id'], ['last_login' => date('Y-m-d H:i:s')]);

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['seller_id'] = $user['seller_id'];

        $_SESSION['success'] = 'Login realizado com sucesso!';

        $intendedUrl = $_SESSION['_intended_url'] ?? null;
        unset($_SESSION['_intended_url']);

        if ($intendedUrl && $intendedUrl !== '/login' && $intendedUrl !== '/register') {
            header('Location: ' . $intendedUrl);
        } else {
            $this->redirectToDashboard();
        }
        exit;
    }

    public function register() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /register');
            exit;
        }

        $data = $this->validateRegistrationData($_POST);

        if (isset($data['error'])) {
            $_SESSION['error'] = $data['error'];
            $_SESSION['old_data'] = $_POST;
            header('Location: /register');
            exit;
        }

        if ($this->userModel->findByEmail($data['email'])) {
            $_SESSION['error'] = 'Este email já está cadastrado';
            $_SESSION['old_data'] = $_POST;
            header('Location: /register');
            exit;
        }

        if ($this->sellerModel->findByDocument($data['document'])) {
            $_SESSION['error'] = 'Este CPF/CNPJ já está cadastrado';
            $_SESSION['old_data'] = $_POST;
            header('Location: /register');
            exit;
        }

        try {
            // Busca taxas padrão do sistema
            $settings = $this->settingsModel->getSettings();

            $sellerId = $this->sellerModel->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'document' => $data['document'],
                'phone' => $data['phone'],
                'person_type' => $data['person_type'],
                'company_name' => $data['company_name'] ?? null,
                'trading_name' => $data['trading_name'] ?? null,
                'monthly_revenue' => $data['monthly_revenue'] ?? null,
                'average_ticket' => $data['average_ticket'] ?? null,
                'status' => 'pending',
                'document_status' => 'pending',
                'fee_percentage_cashin' => $settings['default_fee_percentage_cashin'] ?? 0,
                'fee_fixed_cashin' => $settings['default_fee_fixed_cashin'] ?? 0,
                'fee_percentage_cashout' => $settings['default_fee_percentage_cashout'] ?? 0,
                'fee_fixed_cashout' => $settings['default_fee_fixed_cashout'] ?? 0
            ]);

            $userId = $this->userModel->create([
                'seller_id' => $sellerId,
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => password_hash($data['password'], PASSWORD_DEFAULT),
                'role' => 'seller',
                'status' => 'active'
            ]);

            $this->notificationService->notifyNewSellerRegistration($sellerId);

            $_SESSION['user_id'] = $userId;
            $_SESSION['user_name'] = $data['name'];
            $_SESSION['user_email'] = $data['email'];
            $_SESSION['user_role'] = 'seller';
            $_SESSION['seller_id'] = $sellerId;

            $_SESSION['success'] = 'Cadastro realizado com sucesso! Por favor, envie seus documentos para análise.';

            header('Location: /seller/documents');
            exit;

        } catch (Exception $e) {
            $_SESSION['error'] = 'Erro ao criar cadastro: ' . $e->getMessage();
            $_SESSION['old_data'] = $_POST;
            header('Location: /register');
            exit;
        }
    }

    public function logout() {
        CheckAuth::logout();
        $_SESSION['success'] = 'Logout realizado com sucesso!';
        header('Location: /login');
        exit;
    }

    private function validateRegistrationData($data) {
        $required = ['name', 'email', 'document', 'phone', 'person_type', 'password', 'password_confirmation'];

        foreach ($required as $field) {
            if (empty($data[$field])) {
                return ['error' => 'Todos os campos obrigatórios devem ser preenchidos'];
            }
        }

        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return ['error' => 'Email inválido'];
        }

        if (strlen($data['password']) < 8) {
            return ['error' => 'A senha deve ter no mínimo 8 caracteres'];
        }

        if ($data['password'] !== $data['password_confirmation']) {
            return ['error' => 'As senhas não coincidem'];
        }

        $document = preg_replace('/[^0-9]/', '', $data['document']);

        if (!in_array(strlen($document), [11, 14])) {
            return ['error' => 'CPF/CNPJ inválido'];
        }

        if (!in_array($data['person_type'], ['individual', 'business'])) {
            return ['error' => 'Tipo de pessoa inválido'];
        }

        if ($data['person_type'] === 'business') {
            if (empty($data['company_name'])) {
                return ['error' => 'Razão social é obrigatória para pessoa jurídica'];
            }
        }

        return [
            'name' => trim($data['name']),
            'email' => trim(strtolower($data['email'])),
            'document' => $document,
            'phone' => preg_replace('/[^0-9]/', '', $data['phone']),
            'person_type' => $data['person_type'],
            'company_name' => !empty($data['company_name']) ? trim($data['company_name']) : null,
            'trading_name' => !empty($data['trading_name']) ? trim($data['trading_name']) : null,
            'monthly_revenue' => !empty($data['monthly_revenue']) ? floatval($data['monthly_revenue']) : null,
            'average_ticket' => !empty($data['average_ticket']) ? floatval($data['average_ticket']) : null,
            'password' => $data['password']
        ];
    }

    private function redirectToDashboard() {
        if (CheckAuth::isAdmin()) {
            header('Location: /admin/dashboard');
        } else {
            header('Location: /seller/dashboard');
        }
        exit;
    }
}
