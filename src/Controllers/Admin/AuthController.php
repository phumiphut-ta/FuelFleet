<?php
namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Response;
use App\Core\Database;
use App\Core\Router;
use PDO;

class AuthController {
    public function showLogin(Request $request, Response $response) {
        if (isset($_SESSION['admin_user'])) {
            $response->redirect('/admin/dashboard');
        }

        $error = $_SESSION['login_error'] ?? null;
        unset($_SESSION['login_error']);

        $router = new Router($request, $response);
        return $router->renderView('admin/login', ['error' => $error]);
    }

    public function login(Request $request, Response $response) {
        $body = $request->getBody();
        $username = trim($body['username'] ?? '');
        $password = trim($body['password'] ?? '');

        if (empty($username) || empty($password)) {
            $_SESSION['login_error'] = 'กรุณากรอกชื่อผู้ใช้และรหัสผ่าน';
            $response->redirect('/admin/login');
        }

        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM admin_users WHERE username = :username LIMIT 1");
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['admin_user'] = [
                'id' => $user['id'],
                'username' => $user['username'],
                'full_name' => $user['full_name'],
                'role' => $user['role']
            ];
            
            // Add audit log for login
            $stmtLog = $db->prepare("
                INSERT INTO audit_logs (user_id, username, action, table_name, record_id, new_value)
                VALUES (:user_id, :username, 'Login', 'admin_users', :record_id, :new_value)
            ");
            $stmtLog->execute([
                'user_id' => $user['id'],
                'username' => $user['username'],
                'record_id' => $user['id'],
                'new_value' => json_encode(['username' => $username, 'status' => 'Success'])
            ]);

            $response->redirect('/admin/dashboard');
        } else {
            $_SESSION['login_error'] = 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง';
            $response->redirect('/admin/login');
        }
    }

    public function logout(Request $request, Response $response) {
        if (isset($_SESSION['admin_user'])) {
            $user = $_SESSION['admin_user'];
            $db = Database::getConnection();
            $stmtLog = $db->prepare("
                INSERT INTO audit_logs (user_id, username, action, table_name, record_id)
                VALUES (:user_id, :username, 'Logout', 'admin_users', :record_id)
            ");
            $stmtLog->execute([
                'user_id' => $user['id'],
                'username' => $user['username'],
                'record_id' => $user['id']
            ]);
        }

        unset($_SESSION['admin_user']);
        session_destroy();
        
        // Start a fresh session to hold standard flashes if needed
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $response->redirect('/admin/login');
    }

    public function showChangePassword(Request $request, Response $response) {
        \App\Core\AuthMiddleware::checkAdmin();

        $success = $_SESSION['pwd_success'] ?? null;
        $error = $_SESSION['pwd_error'] ?? null;
        unset($_SESSION['pwd_success'], $_SESSION['pwd_error']);

        $router = new Router($request, $response);
        return $router->renderView('admin/change_password', [
            'success' => $success,
            'error' => $error
        ]);
    }

    public function changePassword(Request $request, Response $response) {
        \App\Core\AuthMiddleware::checkAdmin();

        $body = $request->getBody();
        $currentPassword = trim($body['current_password'] ?? '');
        $newPassword = trim($body['new_password'] ?? '');
        $confirmPassword = trim($body['confirm_password'] ?? '');

        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            $_SESSION['pwd_error'] = 'กรุณากรอกข้อมูลให้ครบทุกช่อง';
            $response->redirect('/admin/change-password');
        }

        if (strlen($newPassword) < 6) {
            $_SESSION['pwd_error'] = 'รหัสผ่านใหม่ต้องมีความยาวอย่างน้อย 6 ตัวอักษร';
            $response->redirect('/admin/change-password');
        }

        if ($newPassword !== $confirmPassword) {
            $_SESSION['pwd_error'] = 'รหัสผ่านใหม่และยืนยันรหัสผ่านใหม่ไม่ตรงกัน';
            $response->redirect('/admin/change-password');
        }

        $userId = $_SESSION['admin_user']['id'];
        $db = Database::getConnection();

        // Fetch current user details to verify password
        $stmt = $db->prepare("SELECT * FROM admin_users WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $userId]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($currentPassword, $user['password'])) {
            $_SESSION['pwd_error'] = 'รหัสผ่านเดิมไม่ถูกต้อง';
            $response->redirect('/admin/change-password');
        }

        // Hash and update new password
        $newHash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);
        $stmtUpdate = $db->prepare("UPDATE admin_users SET password = :password WHERE id = :id");
        $stmtUpdate->execute([
            'password' => $newHash,
            'id' => $userId
        ]);

        // Add audit log
        $stmtLog = $db->prepare("
            INSERT INTO audit_logs (user_id, username, action, table_name, record_id, new_value)
            VALUES (:user_id, :username, 'Change Password', 'admin_users', :record_id, :new_value)
        ");
        $stmtLog->execute([
            'user_id' => $userId,
            'username' => $_SESSION['admin_user']['username'],
            'record_id' => $userId,
            'new_value' => json_encode(['username' => $_SESSION['admin_user']['username'], 'status' => 'Success'])
        ]);

        $_SESSION['pwd_success'] = 'เปลี่ยนรหัสผ่านเรียบร้อยแล้ว';
        $response->redirect('/admin/change-password');
    }
}
