<?php

require_once __DIR__ . '/CheckAuth.php';

class CheckAdmin {
    public static function handle() {
        CheckAuth::handle();

        if (!CheckAuth::isAdmin()) {
            http_response_code(403);
            echo "Acesso negado. Você não tem permissão para acessar esta página.";
            exit;
        }

        return true;
    }
}
