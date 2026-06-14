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
        $agreements = $db->query("SELECT * FROM booking_agreements ORDER BY sort_order ASC, id ASC")->fetchAll();

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
            $maxSort = (int)$db->query("SELECT MAX(sort_order) FROM booking_agreements")->fetchColumn();
            $stmt = $db->prepare("INSERT INTO booking_agreements (agreement_text, sort_order) VALUES (:text, :so)");
            $stmt->execute(['text' => $text, 'so' => $maxSort + 1]);
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

    public function reorder(Request $request, Response $response) {
        $body = $request->getBody();
        $targetId = (int)($body['id'] ?? 0);
        $direction = trim($body['direction'] ?? '');

        if ($targetId <= 0 || !in_array($direction, ['up', 'down'])) {
            $_SESSION['agreement_error'] = 'ข้อมูลการจัดเรียงไม่ถูกต้อง';
            $response->redirect('/admin/agreements');
            return;
        }

        try {
            $db = Database::getConnection();
            $db->beginTransaction();

            // Re-sequence all agreements to ensure unique sequential sort_order values
            $agreements = $db->query("SELECT * FROM booking_agreements ORDER BY sort_order ASC, id ASC")->fetchAll();
            $stmtUpdate = $db->prepare("UPDATE booking_agreements SET sort_order = :so WHERE id = :id");
            foreach ($agreements as $index => $a) {
                $stmtUpdate->execute(['so' => $index + 1, 'id' => $a['id']]);
                $agreements[$index]['sort_order'] = $index + 1;
            }

            $targetIndex = -1;
            foreach ($agreements as $index => $a) {
                if ((int)$a['id'] === $targetId) {
                    $targetIndex = $index;
                    break;
                }
            }

            if ($targetIndex !== -1) {
                if ($direction === 'up' && $targetIndex > 0) {
                    $prevAgreement = $agreements[$targetIndex - 1];
                    $targetAgreement = $agreements[$targetIndex];
                    
                    // Swap sort_order values
                    $stmt = $db->prepare("UPDATE booking_agreements SET sort_order = :so WHERE id = :id");
                    $stmt->execute(['so' => $prevAgreement['sort_order'], 'id' => $targetAgreement['id']]);
                    $stmt->execute(['so' => $targetAgreement['sort_order'], 'id' => $prevAgreement['id']]);
                    
                    $_SESSION['agreement_success'] = 'เลื่อนลำดับข้อตกลงขึ้นเรียบร้อยแล้ว';
                } elseif ($direction === 'down' && $targetIndex < count($agreements) - 1) {
                    $nextAgreement = $agreements[$targetIndex + 1];
                    $targetAgreement = $agreements[$targetIndex];
                    
                    // Swap sort_order values
                    $stmt = $db->prepare("UPDATE booking_agreements SET sort_order = :so WHERE id = :id");
                    $stmt->execute(['so' => $nextAgreement['sort_order'], 'id' => $targetAgreement['id']]);
                    $stmt->execute(['so' => $targetAgreement['sort_order'], 'id' => $nextAgreement['id']]);
                    
                    $_SESSION['agreement_success'] = 'เลื่อนลำดับข้อตกลงลงเรียบร้อยแล้ว';
                } else {
                    $_SESSION['agreement_error'] = 'ไม่สามารถเลื่อนลำดับในทิศทางที่เลือกได้';
                }
            } else {
                $_SESSION['agreement_error'] = 'ไม่พบข้อตกลงที่ต้องการจัดเรียง';
            }

            $db->commit();
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            $_SESSION['agreement_error'] = 'เกิดข้อผิดพลาดในการจัดเรียง: ' . $e->getMessage();
        }

        $response->redirect('/admin/agreements');
    }
}
