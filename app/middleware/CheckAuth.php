<?php

class CheckAuth {
    public static function handle() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
            $_SESSION['_intended_url'] = $_SERVER['REQUEST_URI'];
            header('Location: /login');
            exit;
        }

        return true;
    }

    public static function user() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['user_id'])) {
            return null;
        }

        return [
            'id' => $_SESSION['user_id'],
            'name' => $_SESSION['user_name'] ?? '',
            'email' => $_SESSION['user_email'] ?? '',
            'role' => $_SESSION['user_role'] ?? '',
            'seller_id' => $_SESSION['seller_id'] ?? null
        ];
    }

    public static function sellerId() {
        $user = self::user();
        return $user ? $user['seller_id'] : null;
    }

    public static function isAdmin() {
        $user = self::user();
        return $user && $user['role'] === 'admin';
    }

    public static function isSeller() {
        $user = self::user();
        return $user && $user['role'] === 'seller';
    }

    public static function logout() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION = [];

        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }

        session_destroy();
    }
}
