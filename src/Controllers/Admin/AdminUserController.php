<?php
namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Response;
use App\Core\Database;
use App\Core\Router;
use App\Core\AuthMiddleware;
use App\Repositories\Interfaces\AdminUserRepositoryInterface;
use Exception;

class AdminUserController {
    protected AdminUserRepositoryInterface $userRepo;

    public function __construct(AdminUserRepositoryInterface $userRepo = null) {
        AuthMiddleware::checkAdmin();
        $this->userRepo = $userRepo ?: new \App\Repositories\MySQL\AdminUserRepository();
    }

    public function index(Request $request, Response $response) {
        $users = $this->userRepo->all();

        $success = $_SESSION['user_success'] ?? null;
        $error = $_SESSION['user_error'] ?? null;
        unset($_SESSION['user_success'], $_SESSION['user_error']);

        $router = new Router($request, $response);
        return $router->renderView('admin/users/index', [
            'users' => $users,
            'success' => $success,
            'error' => $error
        ]);
    }

    public function new(Request $request, Response $response) {
        $error = $_SESSION['user_error'] ?? null;
        unset($_SESSION['user_error']);

        $router = new Router($request, $response);
        return $router->renderView('admin/users/new', [
            'error' => $error
        ]);
    }

    public function create(Request $request, Response $response) {
        $body = $request->getBody();
        $username = trim($body['username'] ?? '');
        $password = trim($body['password'] ?? '');
        $fullName = trim($body['full_name'] ?? '');

        if (empty($username) || empty($password) || empty($fullName)) {
            $_SESSION['user_error'] = 'กรุณากรอกข้อมูลให้ครบถ้วน';
            $response->redirect('/admin/users/new');
            return;
        }

        if (strlen($password) < 6) {
            $_SESSION['user_error'] = 'รหัสผ่านต้องมีความยาวอย่างน้อย 6 ตัวอักษร';
            $response->redirect('/admin/users/new');
            return;
        }

        try {
            $existing = $this->userRepo->findByUsername($username);
            if ($existing) {
                $_SESSION['user_error'] = "มีชื่อผู้ใช้ \"{$username}\" ในระบบแล้ว";
                $response->redirect('/admin/users/new');
                return;
            }

            $hashedPassword = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
            $newId = $this->userRepo->create([
                'username' => $username,
                'password' => $hashedPassword,
                'full_name' => $fullName,
                'role' => 'admin'
            ]);

            // Audit Log
            $db = Database::getConnection();
            $stmtLog = $db->prepare("
                INSERT INTO audit_logs (user_id, username, action, table_name, record_id, new_value)
                VALUES (:user_id, :username, 'Create', 'admin_users', :record_id, :new_value)
            ");
            $stmtLog->execute([
                'user_id' => $_SESSION['admin_user']['id'],
                'username' => $_SESSION['admin_user']['username'],
                'record_id' => $newId,
                'new_value' => json_encode(['username' => $username, 'full_name' => $fullName])
            ]);

            $_SESSION['user_success'] = 'เพิ่มผู้ดูแลระบบเรียบร้อยแล้ว';
            $response->redirect('/admin/users');
        } catch (Exception $e) {
            $_SESSION['user_error'] = 'เกิดข้อผิดพลาด: ' . $e->getMessage();
            $response->redirect('/admin/users/new');
        }
    }

    public function edit(Request $request, Response $response, int $id) {
        if ($id === 1) {
            $_SESSION['user_error'] = 'ไม่สามารถแก้ไขผู้ดูแลระบบหลักของระบบได้';
            $response->redirect('/admin/users');
            return;
        }

        $user = $this->userRepo->find($id);
        if (!$user) {
            $_SESSION['user_error'] = 'ไม่พบผู้ใช้ที่ต้องการแก้ไข';
            $response->redirect('/admin/users');
            return;
        }

        $error = $_SESSION['user_error'] ?? null;
        unset($_SESSION['user_error']);

        $router = new Router($request, $response);
        return $router->renderView('admin/users/edit', [
            'user' => $user,
            'error' => $error
        ]);
    }

    public function update(Request $request, Response $response, int $id) {
        if ($id === 1) {
            $_SESSION['user_error'] = 'ไม่สามารถแก้ไขผู้ดูแลระบบหลักของระบบได้';
            $response->redirect('/admin/users');
            return;
        }

        $user = $this->userRepo->find($id);
        if (!$user) {
            $_SESSION['user_error'] = 'ไม่พบผู้ใช้ที่ต้องการแก้ไข';
            $response->redirect('/admin/users');
            return;
        }

        $body = $request->getBody();
        $username = trim($body['username'] ?? '');
        $fullName = trim($body['full_name'] ?? '');
        $password = trim($body['password'] ?? '');

        if (empty($username) || empty($fullName)) {
            $_SESSION['user_error'] = 'กรุณากรอกชื่อผู้ใช้และชื่อ-นามสกุล';
            $response->redirect("/admin/users/edit/{$id}");
            return;
        }

        if (!empty($password) && strlen($password) < 6) {
            $_SESSION['user_error'] = 'รหัสผ่านใหม่ต้องมีความยาวอย่างน้อย 6 ตัวอักษร';
            $response->redirect("/admin/users/edit/{$id}");
            return;
        }

        try {
            $existing = $this->userRepo->findByUsername($username);
            if ($existing && $existing['id'] !== $id) {
                $_SESSION['user_error'] = "มีชื่อผู้ใช้ \"{$username}\" ในระบบแล้ว";
                $response->redirect("/admin/users/edit/{$id}");
                return;
            }

            $updateData = [
                'username' => $username,
                'full_name' => $fullName,
                'role' => 'admin'
            ];

            if (!empty($password)) {
                $updateData['password'] = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
            }

            $this->userRepo->update($id, $updateData);

            // Audit Log
            $db = Database::getConnection();
            $stmtLog = $db->prepare("
                INSERT INTO audit_logs (user_id, username, action, table_name, record_id, previous_value, new_value)
                VALUES (:user_id, :username, 'Update', 'admin_users', :record_id, :prev_value, :new_value)
            ");
            $stmtLog->execute([
                'user_id' => $_SESSION['admin_user']['id'],
                'username' => $_SESSION['admin_user']['username'],
                'record_id' => $id,
                'prev_value' => json_encode($user),
                'new_value' => json_encode(['username' => $username, 'full_name' => $fullName])
            ]);

            $_SESSION['user_success'] = 'แก้ไขข้อมูลผู้ดูแลระบบสำเร็จ';
            $response->redirect('/admin/users');
        } catch (Exception $e) {
            $_SESSION['user_error'] = 'เกิดข้อผิดพลาด: ' . $e->getMessage();
            $response->redirect("/admin/users/edit/{$id}");
        }
    }

    public function delete(Request $request, Response $response, int $id) {
        if ($id === 1) {
            $_SESSION['user_error'] = 'ไม่สามารถลบผู้ดูแลระบบหลักของระบบได้';
            $response->redirect('/admin/users');
            return;
        }

        $user = $this->userRepo->find($id);
        if (!$user) {
            $_SESSION['user_error'] = 'ไม่พบผู้ใช้ที่ต้องการลบ';
            $response->redirect('/admin/users');
            return;
        }

        try {
            $this->userRepo->delete($id);

            // Audit Log
            $db = Database::getConnection();
            $stmtLog = $db->prepare("
                INSERT INTO audit_logs (user_id, username, action, table_name, record_id, previous_value)
                VALUES (:user_id, :username, 'Delete', 'admin_users', :record_id, :prev_value)
            ");
            $stmtLog->execute([
                'user_id' => $_SESSION['admin_user']['id'],
                'username' => $_SESSION['admin_user']['username'],
                'record_id' => $id,
                'prev_value' => json_encode($user)
            ]);

            $_SESSION['user_success'] = 'ลบผู้ดูแลระบบเรียบร้อยแล้ว';
            $response->redirect('/admin/users');
        } catch (Exception $e) {
            $_SESSION['user_error'] = 'เกิดข้อผิดพลาดในการลบ: ' . $e->getMessage();
            $response->redirect('/admin/users');
        }
    }
}
