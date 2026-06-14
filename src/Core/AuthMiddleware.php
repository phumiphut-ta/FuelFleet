<?php
namespace App\Core;

class AuthMiddleware {
    public static function checkAdmin(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['admin_user'])) {
            header("Location: /admin/login");
            exit;
        }
    }

    public static function isAdminLoggedIn(): bool {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return isset($_SESSION['admin_user']);
    }
}
