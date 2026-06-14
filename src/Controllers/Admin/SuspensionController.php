<?php
namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Response;
use App\Core\Database;
use App\Core\Router;
use App\Core\AuthMiddleware;
use App\Repositories\MySQL\SuspensionRepository;
use App\Repositories\MySQL\CarRepository;
use Exception;

class SuspensionController {
    protected SuspensionRepository $suspensionRepo;
    protected CarRepository $carRepo;

    public function __construct() {
        AuthMiddleware::checkAdmin();
        $this->suspensionRepo = new SuspensionRepository();
        $this->carRepo = new CarRepository();
    }

    public function index(Request $request, Response $response) {
        $suspensions = $this->suspensionRepo->all();

        $success = $_SESSION['suspension_success'] ?? null;
        $error = $_SESSION['suspension_error'] ?? null;
        $warning = $_SESSION['suspension_warning'] ?? null;
        unset($_SESSION['suspension_success'], $_SESSION['suspension_error'], $_SESSION['suspension_warning']);

        $router = new Router($request, $response);
        return $router->renderView('admin/suspension/index', [
            'suspensions' => $suspensions,
            'success' => $success,
            'error' => $error,
            'warning' => $warning
        ]);
    }

    public function new(Request $request, Response $response) {
        $cars = $this->carRepo->all();
        $router = new Router($request, $response);
        return $router->renderView('admin/suspension/new', ['cars' => $cars]);
    }

    public function create(Request $request, Response $response) {
        $body = $request->getBody();
        $carId = (int)($body['car_id'] ?? 0);
        $startDate = trim($body['start_date'] ?? '');
        $endDate = trim($body['end_date'] ?? '');
        $reason = trim($body['reason'] ?? '');

        if (!$carId || empty($startDate) || empty($endDate) || empty($reason)) {
            $_SESSION['suspension_error'] = 'กรุณากรอกข้อมูลให้ครบถ้วนทุกช่อง';
            $response->redirect('/admin/suspensions/new');
        }

        if (strtotime($startDate) > strtotime($endDate)) {
            $_SESSION['suspension_error'] = 'วันที่เริ่มต้น ห้ามอยู่หลัง วันที่สิ้นสุด';
            $response->redirect('/admin/suspensions/new');
        }

        try {
            $db = Database::getConnection();
            
            // 1. Check if there are existing bookings that overlap with this suspension
            // A booking overlaps if: start_time < (end_date + 1 day) AND end_time > start_date
            $stmtCheck = $db->prepare("
                SELECT b.*, e.full_name AS employee_name
                FROM car_booking b
                LEFT JOIN employee e ON b.employee_id = e.id
                WHERE b.car_id = :car_id 
                  AND b.status = 'Confirmed'
                  AND b.start_time < DATE_ADD(:end_date, INTERVAL 1 DAY)
                  AND b.end_time > :start_date
            ");
            $stmtCheck->execute([
                'car_id' => $carId,
                'start_date' => $startDate,
                'end_date' => $endDate
            ]);
            $overlappingBookings = $stmtCheck->fetchAll();

            // 2. Create the suspension
            $suspensionId = $this->suspensionRepo->create([
                'car_id' => $carId,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'reason' => $reason,
                'created_by' => $_SESSION['admin_user']['id']
            ]);

            // Audit Log
            $stmtLog = $db->prepare("
                INSERT INTO audit_logs (user_id, username, action, table_name, record_id, new_value)
                VALUES (:user_id, :username, 'Create', 'car_suspension', :record_id, :new_value)
            ");
            $stmtLog->execute([
                'user_id' => $_SESSION['admin_user']['id'],
                'username' => $_SESSION['admin_user']['username'],
                'record_id' => $suspensionId,
                'new_value' => json_encode(['car_id' => $carId, 'start_date' => $startDate, 'end_date' => $endDate, 'reason' => $reason])
            ]);

            $_SESSION['suspension_success'] = 'บันทึกการระงับใช้ยานพาหนะชั่วคราวเรียบร้อยแล้ว';

            // 3. If there are overlapping bookings, add a warning flash message
            if (!empty($overlappingBookings)) {
                $warnList = [];
                foreach ($overlappingBookings as $b) {
                    $warnList[] = "{$b['employee_name']} (วันที่ " . date('d/m/Y', strtotime($b['start_time'])) . " - " . date('d/m/Y', strtotime($b['end_time'])) . ")";
                }
                $_SESSION['suspension_warning'] = 'คำเตือน: มีการจองใช้งานรถคันนี้ล่วงหน้าในระหว่างช่วงเวลาปิดปรับปรุงโดย: ' . implode(', ', $warnList) . ' กรุณาแจ้งยกเลิกและติดต่อพนักงานเพื่อให้เปลี่ยนไปจองรถคันอื่นแทน';
            }

            $response->redirect('/admin/suspensions');
        } catch (Exception $e) {
            $_SESSION['suspension_error'] = 'เกิดข้อผิดพลาด: ' . $e->getMessage();
            $response->redirect('/admin/suspensions/new');
        }
    }

    public function cancel(Request $request, Response $response, int $id) {
        $suspension = $this->suspensionRepo->find($id);
        if (!$suspension) {
            $_SESSION['suspension_error'] = 'ไม่พบรายการคำสั่งระงับใช้งาน';
            $response->redirect('/admin/suspensions');
        }

        try {
            $this->suspensionRepo->cancel($id);

            // Audit Log
            $db = Database::getConnection();
            $stmtLog = $db->prepare("
                INSERT INTO audit_logs (user_id, username, action, table_name, record_id, previous_value)
                VALUES (:user_id, :username, 'Cancel suspension', 'car_suspension', :record_id, :prev_value)
            ");
            $stmtLog->execute([
                'user_id' => $_SESSION['admin_user']['id'],
                'username' => $_SESSION['admin_user']['username'],
                'record_id' => $id,
                'prev_value' => json_encode($suspension)
            ]);

            $_SESSION['suspension_success'] = 'ยกเลิกคำสั่งระงับใช้รถยนต์และเปิดใช้รถยนต์ตามปกติเรียบร้อยแล้ว';
        } catch (Exception $e) {
            $_SESSION['suspension_error'] = 'เกิดข้อผิดพลาดในการยกเลิกคำสั่ง: ' . $e->getMessage();
        }
        $response->redirect('/admin/suspensions');
    }
}
