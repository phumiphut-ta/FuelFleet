<?php
namespace App\Controllers\Public;

use App\Core\Request;
use App\Core\Response;
use App\Core\Database;
use App\Core\Router;
use App\Services\BookingService;
use Exception;

class BookingController {
    protected BookingService $bookingService;

    public function __construct() {
        $this->bookingService = new BookingService();
    }

    public function new(Request $request, Response $response) {
        $db = Database::getConnection();
        
        // Fetch active employees and active vehicles (only vehicles with 'Active' status)
        $employees = $db->query("
            SELECT e.*, pos.name AS position_name, divi.name AS division_name 
            FROM employee e 
            LEFT JOIN position pos ON e.position_id = pos.id 
            LEFT JOIN division divi ON e.division_id = divi.id
            WHERE e.status = 'Active' 
            ORDER BY CASE WHEN divi.name IS NULL THEN 1 ELSE 0 END, divi.name ASC, e.full_name ASC
        ")->fetchAll();
        $cars = $db->query("SELECT * FROM car_detail WHERE status = 'Active' ORDER BY license_plate ASC")->fetchAll();
        $agreements = $db->query("SELECT * FROM booking_agreements ORDER BY id ASC")->fetchAll();

        // 77 Thai provinces list for the checklist
        $provinces = [
            "กรุงเทพมหานคร", "กระบี่", "กาญจนบุรี", "กาฬสินธุ์", "กำแพงเพชร", "ขอนแก่น", "จันทบุรี", "ฉะเชิงเทรา", "ชลบุรี", 
            "ชัยนาท", "ชัยภูมิ", "ชุมพร", "เชียงราย", "เชียงใหม่", "ตรัง", "ตราด", "ตาก", "นครนายก", "นครปฐม", "นครพนม", 
            "นครราชสีมา", "นครศรีธรรมราช", "นครสวรรค์", "นนทบุรี", "นราธิวาส", "น่าน", "บึงกาฬ", "บุรีรัมย์", "ปทุมธานี", 
            "ประจวบคีรีขันธ์", "ปราจีนบุรี", "ปัตตานี", "พระนครศรีอยุธยา", "พะเยา", "พังงา", "พัทลุง", "พิจิตร", "พิษณุโลก", 
            "เพชรบุรี", "เพชรบูรณ์", "แพร่", "พะเยา", "ภูเก็ต", "มหาสารคาม", "มุกดาหาร", "แม่ฮ่องสอน", "ยะลา", "ยโสธร", 
            "ร้อยเอ็ด", "ระนอง", "ระยอง", "ราชบุรี", "ลพบุรี", "ลำปาง", "ลำพูน", "เลย", "ศรีสะเกษ", "สกลนคร", "สงขลา", 
            "สตูล", "สมุทรปราการ", "สมุทรสงคราม", "สมุทรสาคร", "สระแก้ว", "สระบุรี", "สิงห์บุรี", "สุโขทัย", "สุพรรณบุรี", 
            "สุราษฎร์ธานี", "สุรินทร์", "หนองคาย", "หนองบัวลำภู", "อ่างทอง", "อุดรธานี", "อุทัยธานี", "อุตรดิตถ์", "อุบลราชธานี", "อำนาจเจริญ"
        ];

        $error = $_SESSION['booking_form_error'] ?? null;
        $formData = $_SESSION['booking_form_data'] ?? null;
        unset($_SESSION['booking_form_error'], $_SESSION['booking_form_data']);

        $router = new Router($request, $response);
        return $router->renderView('public/booking_form', [
            'employees' => $employees,
            'cars' => $cars,
            'provinces' => $provinces,
            'agreements' => $agreements,
            'error' => $error,
            'formData' => $formData
        ]);
    }

    public function create(Request $request, Response $response) {
        $body = $request->getBody();
        
        $empId = (int)($body['employee_id'] ?? 0);
        $carId = (int)($body['car_id'] ?? 0);
        $startTime = trim($body['start_time'] ?? '');
        $endTime = trim($body['end_time'] ?? '');
        $purpose = trim($body['purpose'] ?? '');
        $password = trim($body['cancellation_password'] ?? '');
        $selectedProvinces = $body['provinces'] ?? []; // Array of selected provinces

        // Filter out empty options or bad inputs
        $selectedProvinces = array_filter($selectedProvinces, function($val) {
            return !empty(trim($val));
        });

        // Store form state for recovery if it fails
        $_SESSION['booking_form_data'] = $body;

        if (!$empId || !$carId || empty($startTime) || empty($endTime) || empty($purpose) || empty($password) || empty($selectedProvinces)) {
            $_SESSION['booking_form_error'] = 'กรุณากรอกข้อมูลให้ครบถ้วน และเลือกจังหวัดปลายทางอย่างน้อย 1 จังหวัด';
            $response->redirect('/booking/new');
        }

        if (strtotime($startTime) > strtotime($endTime)) {
            $_SESSION['booking_form_error'] = 'วันที่เริ่มต้น ห้ามอยู่หลังวันที่สิ้นสุด';
            $response->redirect('/booking/new');
        }

        try {
            $bookingDate = date('Y-m-d', strtotime($startTime));
            $startTimeFormatted = date('Y-m-d 00:00:00', strtotime($startTime));
            $endTimeFormatted = date('Y-m-d 23:59:59', strtotime($endTime));

            $result = $this->bookingService->createBooking([
                'employee_id' => $empId,
                'car_id' => $carId,
                'booking_date' => $bookingDate,
                'start_time' => $startTimeFormatted,
                'end_time' => $endTimeFormatted,
                'purpose' => $purpose,
                'cancellation_password' => $password
            ], $selectedProvinces);

            if ($result['success']) {
                unset($_SESSION['booking_form_data']);
                $_SESSION['booking_success'] = $result['message'];
                $response->redirect('/');
            } else {
                $_SESSION['booking_form_error'] = $result['message'];
                $response->redirect('/booking/new');
            }
        } catch (Exception $e) {
            $_SESSION['booking_form_error'] = 'เกิดข้อผิดพลาดในการบันทึกการจอง: ' . $e->getMessage();
            $response->redirect('/booking/new');
        }
    }

    public function cancel(Request $request, Response $response) {
        $body = $request->getBody();
        $bookingId = (int)($body['booking_id'] ?? 0);
        $password = trim($body['cancellation_password'] ?? '');

        if (!$bookingId || empty($password)) {
            $_SESSION['booking_error'] = 'กรุณากรอกรหัสผ่านสำหรับการยกเลิกการจอง';
            $response->redirect('/');
        }

        try {
            $result = $this->bookingService->cancelBooking($bookingId, $password);
            
            if ($result['success']) {
                $_SESSION['booking_success'] = $result['message'];
            } else {
                $_SESSION['booking_error'] = $result['message'];
            }
        } catch (Exception $e) {
            $_SESSION['booking_error'] = 'เกิดข้อผิดพลาดในการยกเลิก: ' . $e->getMessage();
        }
        
        $response->redirect('/');
    }

    public function editPublic(Request $request, Response $response, int $id) {
        $repo = new \App\Repositories\MySQL\BookingRepository();
        $booking = $repo->find($id);

        if (!$booking) {
            $_SESSION['booking_error'] = 'ไม่พบข้อมูลการจองรถยนต์ดังกล่าวในระบบ';
            $response->redirect('/');
            return;
        }

        if ($booking['status'] === 'Cancelled') {
            $_SESSION['booking_error'] = 'การจองนี้ถูกยกเลิกแล้ว ไม่สามารถแก้ไขได้';
            $response->redirect('/');
            return;
        }

        $db = Database::getConnection();
        
        // Fetch active employees and active vehicles
        $employees = $db->query("
            SELECT e.*, pos.name AS position_name, divi.name AS division_name 
            FROM employee e 
            LEFT JOIN position pos ON e.position_id = pos.id 
            LEFT JOIN division divi ON e.division_id = divi.id
            WHERE e.status = 'Active' 
            ORDER BY CASE WHEN divi.name IS NULL THEN 1 ELSE 0 END, divi.name ASC, e.full_name ASC
        ")->fetchAll();
        
        $cars = $db->query("SELECT * FROM car_detail WHERE status = 'Active' ORDER BY license_plate ASC")->fetchAll();

        // 77 Thai provinces list
        $provinces = [
            "กรุงเทพมหานคร", "กระบี่", "กาญจนบุรี", "กาฬสินธุ์", "กำแพงเพชร", "ขอนแก่น", "จันทบุรี", "ฉะเชิงเทรา", "ชลบุรี", 
            "ชัยนาท", "ชัยภูมิ", "ชุมพร", "เชียงราย", "เชียงใหม่", "ตรัง", "ตราด", "ตาก", "นครนายก", "นครปฐม", "นครพนม", 
            "นครราชสีมา", "นครศรีธรรมราช", "นครสวรรค์", "นนทบุรี", "นราธิวาส", "น่าน", "บึงกาฬ", "บุรีรัมย์", "ปทุมธานี", 
            "ประจวบคีรีขันธ์", "ปราจีนบุรี", "ปัตตานี", "พระนครศรีอยุธยา", "พะเยา", "พังงา", "พัทลุง", "พิจิตร", "พิษณุโลก", 
            "เพชรบุรี", "เพชรบูรณ์", "แพร่", "พะเยา", "ภูเก็ต", "มหาสารคาม", "มุกดาหาร", "แม่ฮ่องสอน", "ยะลา", "ยโสธร", 
            "ร้อยเอ็ด", "ระนอง", "ระยอง", "ราชบุรี", "ลพบุรี", "ลำปาง", "ลำพูน", "เลย", "ศรีสะเกษ", "สกลนคร", "สงขลา", 
            "สตูล", "สมุทรปราการ", "สมุทรสงคราม", "สมุทรสาคร", "สระแก้ว", "สระบุรี", "สิงห์บุรี", "สุโขทัย", "สุพรรณบุรี", 
            "สุราษฎร์ธานี", "สุรินทร์", "หนองคาย", "หนองบัวลำภู", "อ่างทอง", "อุดรธานี", "อุทัยธานี", "อุตรดิตถ์", "อุบลราชธานี", "อำนาจเจริญ"
        ];

        $error = $_SESSION['booking_form_error'] ?? null;
        unset($_SESSION['booking_form_error']);

        $router = new Router($request, $response);
        return $router->renderView('public/booking_edit', [
            'booking' => $booking,
            'employees' => $employees,
            'cars' => $cars,
            'provinces' => $provinces,
            'error' => $error
        ]);
    }

    public function updatePublic(Request $request, Response $response, int $id) {
        $repo = new \App\Repositories\MySQL\BookingRepository();
        $booking = $repo->find($id);

        if (!$booking) {
            $_SESSION['booking_error'] = 'ไม่พบข้อมูลการจองรถยนต์ดังกล่าวในระบบ';
            $response->redirect('/');
            return;
        }

        if ($booking['status'] === 'Cancelled') {
            $_SESSION['booking_error'] = 'การจองนี้ถูกยกเลิกแล้ว ไม่สามารถแก้ไขได้';
            $response->redirect('/');
            return;
        }

        $body = $request->getBody();
        $empId = (int)($body['employee_id'] ?? 0);
        $carId = (int)($body['car_id'] ?? 0);
        $startTime = trim($body['start_time'] ?? '');
        $endTime = trim($body['end_time'] ?? '');
        $purpose = trim($body['purpose'] ?? '');
        $password = trim($body['cancellation_password'] ?? '');
        $selectedProvinces = $body['provinces'] ?? [];

        // Filter empty provinces
        $selectedProvinces = array_filter($selectedProvinces, function($val) {
            return !empty(trim($val));
        });

        if (!$empId || !$carId || empty($startTime) || empty($endTime) || empty($purpose) || empty($password) || empty($selectedProvinces)) {
            $_SESSION['booking_form_error'] = 'กรุณากรอกข้อมูลให้ครบถ้วน และเลือกจังหวัดปลายทางอย่างน้อย 1 จังหวัด';
            $response->redirect("/booking/edit/{$id}");
            return;
        }

        // Verify password
        if (!password_verify($password, $booking['cancellation_password'])) {
            $_SESSION['booking_form_error'] = 'รหัสผ่านสำหรับยืนยันการแก้ไขไม่ถูกต้อง';
            $response->redirect("/booking/edit/{$id}");
            return;
        }

        if (strtotime($startTime) > strtotime($endTime)) {
            $_SESSION['booking_form_error'] = 'วันที่เริ่มต้น ห้ามอยู่หลังวันที่สิ้นสุด';
            $response->redirect("/booking/edit/{$id}");
            return;
        }

        try {
            $bookingDate = date('Y-m-d', strtotime($startTime));
            $startTimeFormatted = date('Y-m-d 00:00:00', strtotime($startTime));
            $endTimeFormatted = date('Y-m-d 23:59:59', strtotime($endTime));

            // When user edits booking, status is reset to Pending (รออนุมัติ) for review again
            $result = $this->bookingService->updateBooking($id, [
                'employee_id' => $empId,
                'car_id' => $carId,
                'booking_date' => $bookingDate,
                'start_time' => $startTimeFormatted,
                'end_time' => $endTimeFormatted,
                'purpose' => $purpose
            ], $selectedProvinces);

            if ($result['success']) {
                // Also update status back to 'Pending' in DB when edited by user
                $db = Database::getConnection();
                $stmtStatus = $db->prepare("UPDATE car_booking SET status = 'Pending' WHERE id = :id");
                $stmtStatus->execute(['id' => $id]);

                $_SESSION['booking_success'] = 'แก้ไขการจองรถยนต์เรียบร้อยแล้ว (สถานะเปลี่ยนเป็นรออนุมัติ)';
                $response->redirect('/');
            } else {
                $_SESSION['booking_form_error'] = $result['message'];
                $response->redirect("/booking/edit/{$id}");
            }
        } catch (Exception $e) {
            $_SESSION['booking_form_error'] = 'เกิดข้อผิดพลาดในการแก้ไขการจอง: ' . $e->getMessage();
            $response->redirect("/booking/edit/{$id}");
        }
    }

    public function recentReceipts(Request $request, Response $response) {
        $db = Database::getConnection();
        $stmt = $db->query("
            SELECT r.*, e.full_name AS employee_name, c.license_plate, c.fuel_type, a.file_path
            FROM gas_receipt r
            LEFT JOIN employee e ON r.employee_id = e.id
            LEFT JOIN car_detail c ON r.car_id = c.id
            LEFT JOIN receipt_attachment a ON a.receipt_id = r.id
            ORDER BY r.id DESC
            LIMIT 10
        ");
        $receipts = $stmt->fetchAll();

        $router = new Router($request, $response);
        return $router->renderView('public/recent_receipts', [
            'receipts' => $receipts
        ]);
    }
}
