<?php
namespace App\Core;

class AuthMiddleware {
    public static function checkAdmin(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_set_cookie_params([
                'lifetime' => 0,
                'path' => '/',
                'domain' => '',
                'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
            session_start();
        }
        if (!isset($_SESSION['admin_user'])) {
            $basePath = Request::getBasePath();
            header("Location: " . $basePath . "/admin/login");
            exit;
        }
    }

    public static function isAdminLoggedIn(): bool {
        if (session_status() === PHP_SESSION_NONE) {
            session_set_cookie_params([
                'lifetime' => 0,
                'path' => '/',
                'domain' => '',
                'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
            session_start();
        }
        return isset($_SESSION['admin_user']);
    }
}
