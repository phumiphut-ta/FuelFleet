<?php
namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Response;
use App\Core\Database;
use App\Core\Router;
use App\Core\AuthMiddleware;
use Exception;
use PDO;

class HistoryImportController {
    public function __construct() {
        AuthMiddleware::checkAdmin();
    }

    private function getProvinces(): array {
        return [
            "กรุงเทพมหานคร", "กระบี่", "กาญจนบุรี", "กาฬสินธุ์", "กำแพงเพชร", "ขอนแก่น", "จันทบุรี", "ฉะเชิงเทรา", "ชลบุรี", 
            "ชัยนาท", "ชัยภูมิ", "ชุมพร", "เชียงราย", "เชียงใหม่", "ตรัง", "ตราด", "ตาก", "นครนายก", "นครปฐม", "นครพนม", 
            "นครราชสีมา", "นครศรีธรรมราช", "นครสวรรค์", "นนทบุรี", "นราธิวาส", "น่าน", "บึงกาฬ", "บุรีรัมย์", "ปทุมธานี", 
            "ประจวบคีรีขันธ์", "ปราจีนบุรี", "ปัตตานี", "พระนครศรีอยุธยา", "พะเยา", "พังงา", "พัทลุง", "พิจิตร", "พิษณุโลก", 
            "เพชรบุรี", "เพชรบูรณ์", "แพร่", "ภูเก็ต", "มหาสารคาม", "มุกดาหาร", "แม่ฮ่องสอน", "ยะลา", "ยโสธร", 
            "ร้อยเอ็ด", "ระนอง", "ระยอง", "ราชบุรี", "ลพบุรี", "ลำปาง", "ลำพูน", "เลย", "ศรีสะเกษ", "สกลนคร", "สงขลา", 
            "สตูล", "สมุทรปราการ", "สมุทรสงคราม", "สมุทรสาคร", "สระแก้ว", "สระบุรี", "สิงห์บุรี", "สุโขทัย", "สุพรรณบุรี", 
            "สุราษฎร์ธานี", "สุรินทร์", "หนองคาย", "หนองบัวลำภู", "อ่างทอง", "อุดรธานี", "อุทัยธานี", "อุตรดิตถ์", "อุบลราชธานี", "อำนาจเจริญ"
        ];
    }

    private function getOrCreateHistoricalEmployee($db): int {
        $stmt = $db->prepare("SELECT id FROM employee WHERE employee_code = 'EMP_HIST'");
        $stmt->execute();
        $employeeId = $stmt->fetchColumn();

        if (!$employeeId) {
            $divisionId = $db->query("SELECT id FROM division LIMIT 1")->fetchColumn();
            $departmentId = $db->query("SELECT id FROM department LIMIT 1")->fetchColumn();
            $positionId = $db->query("SELECT id FROM position LIMIT 1")->fetchColumn();

            if (!$divisionId || !$departmentId || !$positionId) {
                throw new Exception("กรุณาติดตั้งข้อมูลโครงสร้างองค์กร (กอง/ฝ่าย/ตำแหน่ง) ในระบบก่อนทำรายการ");
            }

            $stmtCreate = $db->prepare("
                INSERT INTO employee (employee_code, full_name, division_id, department_id, position_id, status)
                VALUES ('EMP_HIST', 'ข้อมูลประวัติระบบเดิม', :div, :dept, :pos, 'Active')
            ");
            $stmtCreate->execute([
                'div' => $divisionId,
                'dept' => $departmentId,
                'pos' => $positionId
            ]);
            $employeeId = $db->lastInsertId();
        }

        return (int)$employeeId;
    }

    public function index(Request $request, Response $response) {
        $db = Database::getConnection();

        // Get active cars
        $cars = $db->query("SELECT * FROM car_detail WHERE status = 'Active' ORDER BY license_plate ASC")->fetchAll();

        // Get existing monthly fuel imports (HIST-%)
        $fuelHistories = $db->query("
            SELECT r.id, r.receipt_number, r.receipt_date, r.liters, r.amount, c.license_plate, c.fuel_type 
            FROM gas_receipt r 
            INNER JOIN car_detail c ON r.car_id = c.id 
            WHERE r.receipt_number LIKE 'HIST-%' 
            ORDER BY r.receipt_date DESC, c.license_plate ASC
        ")->fetchAll();

        // Get existing travel imports (HIST-TRAVEL-%)
        $rawTravel = $db->query("
            SELECT 
                b.purpose, 
                bp.province_name, 
                COUNT(b.id) as count_trips 
            FROM car_booking b
            INNER JOIN car_booking_provinces bp ON b.id = bp.booking_id
            WHERE b.purpose LIKE 'HIST-TRAVEL-%'
            GROUP BY b.purpose, bp.province_name
            ORDER BY b.purpose DESC
        ")->fetchAll();

        $travelHistories = [];
        foreach ($rawTravel as $row) {
            $parts = explode('-', $row['purpose']);
            $fy = isset($parts[2]) ? (int)$parts[2] : 0;
            $travelHistories[] = [
                'fy' => $fy,
                'province' => $row['province_name'],
                'count_trips' => $row['count_trips'],
                'purpose' => $row['purpose']
            ];
        }

        $success = $_SESSION['hist_success'] ?? null;
        $error = $_SESSION['hist_error'] ?? null;
        unset($_SESSION['hist_success'], $_SESSION['hist_error']);

        $router = new Router($request, $response);
        return $router->renderView('admin/history_import/index', [
            'cars' => $cars,
            'provinces' => $this->getProvinces(),
            'fuelHistories' => $fuelHistories,
            'travelHistories' => $travelHistories,
            'success' => $success,
            'error' => $error
        ]);
    }

    public function saveFuel(Request $request, Response $response) {
        $body = $request->getBody();
        $carId = isset($body['car_id']) ? (int)$body['car_id'] : 0;
        $year = isset($body['year']) ? (int)$body['year'] : 0;
        $month = isset($body['month']) ? trim($body['month']) : '';
        $liters = isset($body['liters']) ? (float)$body['liters'] : 0.0;
        $amount = isset($body['amount']) ? (float)$body['amount'] : 0.0;

        if (!$carId || !$year || empty($month) || $liters <= 0 || $amount <= 0) {
            $_SESSION['hist_error'] = 'กรุณากรอกข้อมูลปริมาณน้ำมันและยอดเงินให้ครบถ้วนถูกต้อง';
            $response->redirect('/admin/history-import');
            return;
        }

        try {
            $db = Database::getConnection();
            $employeeId = $this->getOrCreateHistoricalEmployee($db);

            // Get car fuel type
            $stmtCar = $db->prepare("SELECT fuel_type FROM car_detail WHERE id = ?");
            $stmtCar->execute([$carId]);
            $carFuel = $stmtCar->fetchColumn();

            if (!$carFuel) {
                $_SESSION['hist_error'] = 'ไม่พบข้อมูลยานพาหนะที่เลือก';
                $response->redirect('/admin/history-import');
                return;
            }

            // Construct Receipt Number
            $monthPad = sprintf("%02d", $month);
            $receiptNum = "HIST-{$carId}-{$year}-{$monthPad}";
            $receiptDate = "{$year}-{$monthPad}-15";

            // Check duplicate
            $stmtCheck = $db->prepare("SELECT id FROM gas_receipt WHERE receipt_number = ?");
            $stmtCheck->execute([$receiptNum]);
            $existingId = $stmtCheck->fetchColumn();

            if ($existingId) {
                // Update
                $stmtUpdate = $db->prepare("
                    UPDATE gas_receipt 
                    SET liters = :liters, amount = :amount 
                    WHERE id = :id
                ");
                $stmtUpdate->execute([
                    'liters' => $liters,
                    'amount' => $amount,
                    'id' => $existingId
                ]);

                // Audit log
                $stmtAudit = $db->prepare("
                    INSERT INTO audit_logs (user_id, username, action, table_name, record_id, new_value)
                    VALUES (:user_id, :username, 'Update historical fuel', 'gas_receipt', :record_id, :new_val)
                ");
                $stmtAudit->execute([
                    'user_id' => $_SESSION['admin_user']['id'],
                    'username' => $_SESSION['admin_user']['username'],
                    'record_id' => $existingId,
                    'new_val' => json_encode(['liters' => $liters, 'amount' => $amount])
                ]);
            } else {
                // Insert
                $pricePerLiter = $amount / $liters;
                $stmtInsert = $db->prepare("
                    INSERT INTO gas_receipt (receipt_number, receipt_date, record_date, employee_id, car_id, amount, liters, price_per_liter, status)
                    VALUES (:receipt_number, :receipt_date, :record_date, :employee_id, :car_id, :amount, :liters, :price_per_liter, 'Verified')
                ");
                $stmtInsert->execute([
                    'receipt_number' => $receiptNum,
                    'receipt_date' => $receiptDate,
                    'record_date' => $receiptDate,
                    'employee_id' => $employeeId,
                    'car_id' => $carId,
                    'amount' => $amount,
                    'liters' => $liters,
                    'price_per_liter' => $pricePerLiter
                ]);
                $newId = $db->lastInsertId();

                // Audit log
                $stmtAudit = $db->prepare("
                    INSERT INTO audit_logs (user_id, username, action, table_name, record_id, new_value)
                    VALUES (:user_id, :username, 'Create historical fuel', 'gas_receipt', :record_id, :new_val)
                ");
                $stmtAudit->execute([
                    'user_id' => $_SESSION['admin_user']['id'],
                    'username' => $_SESSION['admin_user']['username'],
                    'record_id' => $newId,
                    'new_val' => json_encode(['receipt_number' => $receiptNum, 'liters' => $liters, 'amount' => $amount])
                ]);
            }

            $_SESSION['hist_success'] = 'บันทึกประวัติการใช้น้ำมันเรียบร้อยแล้ว';
        } catch (Exception $e) {
            $_SESSION['hist_error'] = 'เกิดข้อผิดพลาด: ' . $e->getMessage();
        }

        $response->redirect('/admin/history-import');
    }

    public function deleteFuel(Request $request, Response $response, int $id) {
        try {
            $db = Database::getConnection();

            // Fetch to log
            $stmtFetch = $db->prepare("SELECT * FROM gas_receipt WHERE id = ? AND receipt_number LIKE 'HIST-%'");
            $stmtFetch->execute([$id]);
            $receipt = $stmtFetch->fetch();

            if (!$receipt) {
                $_SESSION['hist_error'] = 'ไม่พบประวัติการใช้น้ำมันที่ต้องการลบ';
                $response->redirect('/admin/history-import');
                return;
            }

            $stmtDel = $db->prepare("DELETE FROM gas_receipt WHERE id = ?");
            $stmtDel->execute([$id]);

            // Audit log
            $stmtAudit = $db->prepare("
                INSERT INTO audit_logs (user_id, username, action, table_name, record_id, previous_value)
                VALUES (:user_id, :username, 'Delete historical fuel', 'gas_receipt', :record_id, :prev_val)
            ");
            $stmtAudit->execute([
                'user_id' => $_SESSION['admin_user']['id'],
                'username' => $_SESSION['admin_user']['username'],
                'record_id' => $id,
                'prev_val' => json_encode($receipt)
            ]);

            $_SESSION['hist_success'] = 'ลบประวัติการใช้น้ำมันเรียบร้อยแล้ว';
        } catch (Exception $e) {
            $_SESSION['hist_error'] = 'เกิดข้อผิดพลาดในการลบ: ' . $e->getMessage();
        }

        $response->redirect('/admin/history-import');
    }

    public function saveTravel(Request $request, Response $response) {
        $body = $request->getBody();
        $fy = isset($body['fy']) ? (int)$body['fy'] : 0;
        $province = isset($body['province']) ? trim($body['province']) : '';
        $countTrips = isset($body['count_trips']) ? (int)$body['count_trips'] : 0;

        if ($fy < 2000 || $fy > 2099 || empty($province) || $countTrips <= 0) {
            $_SESSION['hist_error'] = 'กรุณาระบุปีงบประมาณ จังหวัด และจำนวนเที่ยวเดินทางให้ถูกต้อง';
            $response->redirect('/admin/history-import');
            return;
        }

        if (!in_array($province, $this->getProvinces())) {
            $_SESSION['hist_error'] = 'ข้อมูลจังหวัดที่เลือกไม่ถูกต้อง';
            $response->redirect('/admin/history-import');
            return;
        }

        try {
            $db = Database::getConnection();
            $employeeId = $this->getOrCreateHistoricalEmployee($db);

            // Get first active car
            $carId = $db->query("SELECT id FROM car_detail WHERE status = 'Active' LIMIT 1")->fetchColumn();
            if (!$carId) {
                $_SESSION['hist_error'] = 'กรุณาลงทะเบียนยานพาหนะหลวงที่มีสถานะ Active ในระบบอย่างน้อย 1 คันก่อนบันทึกประวัติการเดินทาง';
                $response->redirect('/admin/history-import');
                return;
            }

            $purpose = "HIST-TRAVEL-{$fy}-{$province}";

            $db->beginTransaction();

            // Clear previous entries
            // Due to foreign key ON DELETE CASCADE, this deletes car_booking_provinces automatically
            $stmtClear = $db->prepare("DELETE FROM car_booking WHERE purpose = ?");
            $stmtClear->execute([$purpose]);

            // Insert bookings and province records
            $stmtBook = $db->prepare("
                INSERT INTO car_booking (employee_id, car_id, booking_date, start_time, end_time, purpose, cancellation_password, status)
                VALUES (:employee_id, :car_id, :booking_date, :start_time, :end_time, :purpose, '', 'Confirmed')
            ");
            $stmtProv = $db->prepare("
                INSERT INTO car_booking_provinces (booking_id, province_name)
                VALUES (:booking_id, :province_name)
            ");

            $mockDate = "{$fy}-03-15"; // Middle of fiscal year
            $mockStart = "{$fy}-03-15 09:00:00";
            $mockEnd = "{$fy}-03-15 17:00:00";

            for ($i = 0; $i < $countTrips; $i++) {
                $stmtBook->execute([
                    'employee_id' => $employeeId,
                    'car_id' => $carId,
                    'booking_date' => $mockDate,
                    'start_time' => $mockStart,
                    'end_time' => $mockEnd,
                    'purpose' => $purpose
                ]);
                $bookingId = $db->lastInsertId();

                $stmtProv->execute([
                    'booking_id' => $bookingId,
                    'province_name' => $province
                ]);
            }

            $db->commit();

            // Audit log
            $stmtAudit = $db->prepare("
                INSERT INTO audit_logs (user_id, username, action, table_name, record_id, new_value)
                VALUES (:user_id, :username, 'Save historical travel', 'car_booking', NULL, :new_val)
            ");
            $stmtAudit->execute([
                'user_id' => $_SESSION['admin_user']['id'],
                'username' => $_SESSION['admin_user']['username'],
                'new_val' => json_encode(['fy' => $fy, 'province' => $province, 'count' => $countTrips])
            ]);

            $_SESSION['hist_success'] = 'บันทึกประวัติสถิติการเดินทางเรียบร้อยแล้ว';
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            $_SESSION['hist_error'] = 'เกิดข้อผิดพลาด: ' . $e->getMessage();
        }

        $response->redirect('/admin/history-import');
    }

    public function deleteTravel(Request $request, Response $response) {
        $body = $request->getBody();
        $fy = isset($body['fy']) ? (int)$body['fy'] : 0;
        $province = isset($body['province']) ? trim($body['province']) : '';
        $purpose = "HIST-TRAVEL-{$fy}-{$province}";

        try {
            $db = Database::getConnection();

            $stmtFetch = $db->prepare("SELECT id FROM car_booking WHERE purpose = ?");
            $stmtFetch->execute([$purpose]);
            $bookings = $stmtFetch->fetchAll(PDO::FETCH_COLUMN);

            if (empty($bookings)) {
                $_SESSION['hist_error'] = 'ไม่พบสถิติการเดินทางที่ต้องการลบ';
                $response->redirect('/admin/history-import');
                return;
            }

            // Clear
            $stmtClear = $db->prepare("DELETE FROM car_booking WHERE purpose = ?");
            $stmtClear->execute([$purpose]);

            // Audit log
            $stmtAudit = $db->prepare("
                INSERT INTO audit_logs (user_id, username, action, table_name, record_id, previous_value)
                VALUES (:user_id, :username, 'Delete historical travel', 'car_booking', NULL, :prev_val)
            ");
            $stmtAudit->execute([
                'user_id' => $_SESSION['admin_user']['id'],
                'username' => $_SESSION['admin_user']['username'],
                'prev_val' => json_encode(['fy' => $fy, 'province' => $province, 'deleted_bookings_count' => count($bookings)])
            ]);

            $_SESSION['hist_success'] = 'ลบประวัติสถิติการเดินทางเรียบร้อยแล้ว';
        } catch (Exception $e) {
            $_SESSION['hist_error'] = 'เกิดข้อผิดพลาดในการลบ: ' . $e->getMessage();
        }

        $response->redirect('/admin/history-import');
    }
}
