<?php
namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Response;
use App\Core\Database;
use App\Core\Router;
use App\Core\AuthMiddleware;
use Exception;

class AgreementController {
    public function __construct() {
        AuthMiddleware::checkAdmin();
    }

    public function index(Request $request, Response $response) {
        $db = Database::getConnection();
        $agreements = $db->query("SELECT * FROM booking_agreements ORDER BY id ASC")->fetchAll();

        $success = $_SESSION['agreement_success'] ?? null;
        $error = $_SESSION['agreement_error'] ?? null;
        unset($_SESSION['agreement_success'], $_SESSION['agreement_error']);

        $router = new Router($request, $response);
        return $router->renderView('admin/agreements/index', [
            'agreements' => $agreements,
            'success' => $success,
            'error' => $error
        ]);
    }

    public function create(Request $request, Response $response) {
        $body = $request->getBody();
        $text = trim($body['agreement_text'] ?? '');

        if (empty($text)) {
            $_SESSION['agreement_error'] = 'กรุณากรอกข้อความข้อตกลง';
            $response->redirect('/admin/agreements');
            return;
        }

        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("INSERT INTO booking_agreements (agreement_text) VALUES (:text)");
            $stmt->execute(['text' => $text]);
            $newId = (int)$db->lastInsertId();

            // Log Audit Log
            $stmtLog = $db->prepare("
                INSERT INTO audit_logs (user_id, username, action, table_name, record_id, new_value)
                VALUES (:user_id, :username, 'Create agreement', 'booking_agreements', :record_id, :new_value)
            ");
            $stmtLog->execute([
                'user_id' => $_SESSION['admin_user']['id'],
                'username' => $_SESSION['admin_user']['username'],
                'record_id' => $newId,
                'new_value' => json_encode(['agreement_text' => $text])
            ]);

            $_SESSION['agreement_success'] = 'เพิ่มข้อตกลงการจองรถยนต์เรียบร้อยแล้ว';
        } catch (Exception $e) {
            $_SESSION['agreement_error'] = 'เกิดข้อผิดพลาดในการบันทึก: ' . $e->getMessage();
        }

        $response->redirect('/admin/agreements');
    }

    public function update(Request $request, Response $response, int $id) {
        $body = $request->getBody();
        $text = trim($body['agreement_text'] ?? '');

        if (empty($text)) {
            $_SESSION['agreement_error'] = 'กรุณากรอกข้อความข้อตกลง';
            $response->redirect('/admin/agreements');
            return;
        }

        try {
            $db = Database::getConnection();
            
            // Get previous value
            $prevStmt = $db->prepare("SELECT * FROM booking_agreements WHERE id = :id");
            $prevStmt->execute(['id' => $id]);
            $prevValue = $prevStmt->fetch();

            if (!$prevValue) {
                $_SESSION['agreement_error'] = 'ไม่พบข้อตกลงที่ต้องการแก้ไข';
                $response->redirect('/admin/agreements');
                return;
            }

            $stmt = $db->prepare("UPDATE booking_agreements SET agreement_text = :text WHERE id = :id");
            $stmt->execute(['text' => $text, 'id' => $id]);

            // Log Audit Log
            $stmtLog = $db->prepare("
                INSERT INTO audit_logs (user_id, username, action, table_name, record_id, previous_value, new_value)
                VALUES (:user_id, :username, 'Update agreement', 'booking_agreements', :record_id, :prev_value, :new_value)
            ");
            $stmtLog->execute([
                'user_id' => $_SESSION['admin_user']['id'],
                'username' => $_SESSION['admin_user']['username'],
                'record_id' => $id,
                'prev_value' => json_encode($prevValue),
                'new_value' => json_encode(['agreement_text' => $text])
            ]);

            $_SESSION['agreement_success'] = 'แก้ไขข้อตกลงการจองรถยนต์สำเร็จ';
        } catch (Exception $e) {
            $_SESSION['agreement_error'] = 'เกิดข้อผิดพลาดในการอัปเดต: ' . $e->getMessage();
        }

        $response->redirect('/admin/agreements');
    }

    public function delete(Request $request, Response $response, int $id) {
        try {
            $db = Database::getConnection();
            
            // Get previous value
            $prevStmt = $db->prepare("SELECT * FROM booking_agreements WHERE id = :id");
            $prevStmt->execute(['id' => $id]);
            $prevValue = $prevStmt->fetch();

            if (!$prevValue) {
                $_SESSION['agreement_error'] = 'ไม่พบข้อตกลงที่ต้องการลบ';
                $response->redirect('/admin/agreements');
                return;
            }

            $stmt = $db->prepare("DELETE FROM booking_agreements WHERE id = :id");
            $stmt->execute(['id' => $id]);

            // Log Audit Log
            $stmtLog = $db->prepare("
                INSERT INTO audit_logs (user_id, username, action, table_name, record_id, previous_value)
                VALUES (:user_id, :username, 'Delete agreement', 'booking_agreements', :record_id, :prev_value)
            ");
            $stmtLog->execute([
                'user_id' => $_SESSION['admin_user']['id'],
                'username' => $_SESSION['admin_user']['username'],
                'record_id' => $id,
                'prev_value' => json_encode($prevValue)
            ]);

            $_SESSION['agreement_success'] = 'ลบข้อตกลงการจองรถยนต์เรียบร้อยแล้ว';
        } catch (Exception $e) {
            $_SESSION['agreement_error'] = 'เกิดข้อผิดพลาดในการลบ: ' . $e->getMessage();
        }

        $response->redirect('/admin/agreements');
    }
}
