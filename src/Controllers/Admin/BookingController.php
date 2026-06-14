<?php
namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Response;
use App\Core\Database;
use App\Core\Router;
use App\Core\AuthMiddleware;
use App\Services\BookingService;
use App\Repositories\MySQL\BookingRepository;
use Exception;

class BookingController {
    protected BookingService $bookingService;
    protected BookingRepository $bookingRepo;

    public function __construct() {
        AuthMiddleware::checkAdmin();
        $this->bookingService = new BookingService();
        $this->bookingRepo = new BookingRepository();
    }

    public function index(Request $request, Response $response) {
        $queryParams = $request->getBody();
        $search = trim($queryParams['search'] ?? '');
        $carId = isset($queryParams['car_id']) && $queryParams['car_id'] !== '' ? (int)$queryParams['car_id'] : null;
        $employeeId = isset($queryParams['employee_id']) && $queryParams['employee_id'] !== '' ? (int)$queryParams['employee_id'] : null;
        $startDate = isset($queryParams['start_date']) && $queryParams['start_date'] !== '' ? trim($queryParams['start_date']) : null;
        $endDate = isset($queryParams['end_date']) && $queryParams['end_date'] !== '' ? trim($queryParams['end_date']) : null;
        
        $page = (int)($queryParams['page'] ?? 1);
        if ($page < 1) {
            $page = 1;
        }

        $limit = 10;
        $totalCount = $this->bookingRepo->countBookings($search, $carId, $employeeId, $startDate, $endDate);
        $totalPages = (int)ceil($totalCount / $limit);
        if ($totalPages < 1) {
            $totalPages = 1;
        }
        if ($page > $totalPages) {
            $page = $totalPages;
        }

        $offset = ($page - 1) * $limit;
        $bookings = $this->bookingRepo->searchBookings($search, $limit, $offset, $carId, $employeeId, $startDate, $endDate);

        $success = $_SESSION['booking_success'] ?? null;
        $error = $_SESSION['booking_error'] ?? null;
        unset($_SESSION['booking_success'], $_SESSION['booking_error']);

        $db = Database::getConnection();
        $cars = $db->query("SELECT * FROM car_detail WHERE status = 'Active' ORDER BY license_plate ASC")->fetchAll();
        $employees = $db->query("SELECT * FROM employee WHERE status = 'Active' ORDER BY full_name ASC")->fetchAll();

        $router = new Router($request, $response);
        return $router->renderView('admin/booking/index', [
            'bookings' => $bookings,
            'search' => $search,
            'carId' => $carId,
            'employeeId' => $employeeId,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'page' => $page,
            'totalPages' => $totalPages,
            'totalCount' => $totalCount,
            'cars' => $cars,
            'employees' => $employees,
            'success' => $success,
            'error' => $error
        ]);
    }

    public function export(Request $request, Response $response) {
        $queryParams = $request->getBody();
        $search = trim($queryParams['search'] ?? '');
        $carId = isset($queryParams['car_id']) && $queryParams['car_id'] !== '' ? (int)$queryParams['car_id'] : null;
        $employeeId = isset($queryParams['employee_id']) && $queryParams['employee_id'] !== '' ? (int)$queryParams['employee_id'] : null;
        $startDate = isset($queryParams['start_date']) && $queryParams['start_date'] !== '' ? trim($queryParams['start_date']) : null;
        $endDate = isset($queryParams['end_date']) && $queryParams['end_date'] !== '' ? trim($queryParams['end_date']) : null;

        $bookings = $this->bookingRepo->exportAllBookings($search, $carId, $employeeId, $startDate, $endDate);

        // Set headers for CSV download
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="รายงานการจองรถยนต์_' . date('Ymd_His') . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        // Write UTF-8 BOM for Excel compatibility (solves Thai font corruption!)
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Write CSV Header
        fputcsv($output, [
            'เลขรหัสการจอง',
            'วันที่ทำรายการ',
            'ผู้จองเดินทาง',
            'ทะเบียนรถที่ใช้',
            'วัตถุประสงค์เดินทาง',
            'จังหวัดปลายทาง',
            'วันที่เริ่มเดินทาง',
            'วันที่เดินทางกลับ',
            'สถานะการจอง',
            'เหตุผลการยกเลิก'
        ]);
        
        $statusLabels = [
            'Confirmed' => 'อนุมัติการจองแล้ว',
            'Pending' => 'รออนุมัติ',
            'Cancelled' => 'ยกเลิกการจองแล้ว'
        ];

        foreach ($bookings as $b) {
            $statusLabel = $statusLabels[$b['status']] ?? $b['status'];
            $provincesStr = isset($b['provinces']) ? implode(', ', $b['provinces']) : '';
            fputcsv($output, [
                $b['id'],
                date('d/m/Y', strtotime($b['booking_date'])),
                $b['employee_name'],
                $b['license_plate'],
                $b['purpose'],
                $provincesStr,
                date('d/m/Y', strtotime($b['start_time'])),
                date('d/m/Y', strtotime($b['end_time'])),
                $statusLabel,
                $b['cancel_reason'] ?? ''
            ]);
        }
        
        fclose($output);
        exit;
    }

    public function edit(Request $request, Response $response, int $id) {
        $booking = $this->bookingRepo->find($id);
        if (!$booking) {
            $_SESSION['booking_error'] = 'ไม่พบข้อมูลการจองดังกล่าวในระบบ';
            $response->redirect('/admin/bookings');
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
        return $router->renderView('admin/booking/edit', [
            'booking' => $booking,
            'employees' => $employees,
            'cars' => $cars,
            'provinces' => $provinces,
            'error' => $error
        ]);
    }

    public function update(Request $request, Response $response, int $id) {
        $booking = $this->bookingRepo->find($id);
        if (!$booking) {
            $_SESSION['booking_error'] = 'ไม่พบข้อมูลการจองดังกล่าวในระบบ';
            $response->redirect('/admin/bookings');
        }

        $body = $request->getBody();
        $empId = (int)($body['employee_id'] ?? 0);
        $carId = (int)($body['car_id'] ?? 0);
        $startTime = trim($body['start_time'] ?? '');
        $endTime = trim($body['end_time'] ?? '');
        $purpose = trim($body['purpose'] ?? '');
        $selectedProvinces = $body['provinces'] ?? [];

        // Filter empty provinces
        $selectedProvinces = array_filter($selectedProvinces, function($val) {
            return !empty(trim($val));
        });

        if (!$empId || !$carId || empty($startTime) || empty($endTime) || empty($purpose) || empty($selectedProvinces)) {
            $_SESSION['booking_form_error'] = 'กรุณากรอกข้อมูลให้ครบถ้วน และเลือกจังหวัดปลายทางอย่างน้อย 1 จังหวัด';
            $response->redirect("/admin/bookings/edit/{$id}");
        }

        if (strtotime($startTime) > strtotime($endTime)) {
            $_SESSION['booking_form_error'] = 'วันที่เริ่มต้น ห้ามอยู่หลังวันที่สิ้นสุด';
            $response->redirect("/admin/bookings/edit/{$id}");
        }

        try {
            $bookingDate = date('Y-m-d', strtotime($startTime));
            $startTimeFormatted = date('Y-m-d 00:00:00', strtotime($startTime));
            $endTimeFormatted = date('Y-m-d 23:59:59', strtotime($endTime));

            $result = $this->bookingService->updateBooking($id, [
                'employee_id' => $empId,
                'car_id' => $carId,
                'booking_date' => $bookingDate,
                'start_time' => $startTimeFormatted,
                'end_time' => $endTimeFormatted,
                'purpose' => $purpose
            ], $selectedProvinces);

            if ($result['success']) {
                // Log audit log
                $db = Database::getConnection();
                $stmtLog = $db->prepare("
                    INSERT INTO audit_logs (user_id, username, action, table_name, record_id, previous_value, new_value)
                    VALUES (:user_id, :username, 'Update booking', 'car_booking', :record_id, :prev_value, :new_value)
                ");
                $stmtLog->execute([
                    'user_id' => $_SESSION['admin_user']['id'],
                    'username' => $_SESSION['admin_user']['username'],
                    'record_id' => $id,
                    'prev_value' => json_encode($booking),
                    'new_value' => json_encode([
                        'employee_id' => $empId,
                        'car_id' => $carId,
                        'start_time' => $startTimeFormatted,
                        'end_time' => $endTimeFormatted,
                        'purpose' => $purpose,
                        'provinces' => $selectedProvinces
                    ])
                ]);

                $_SESSION['booking_success'] = $result['message'];
                $response->redirect('/admin/bookings');
            } else {
                $_SESSION['booking_form_error'] = $result['message'];
                $response->redirect("/admin/bookings/edit/{$id}");
            }
        } catch (Exception $e) {
            $_SESSION['booking_form_error'] = 'เกิดข้อผิดพลาดในการแก้ไขการจอง: ' . $e->getMessage();
            $response->redirect("/admin/bookings/edit/{$id}");
        }
    }

    public function approve(Request $request, Response $response, int $id) {
        $booking = $this->bookingRepo->find($id);
        if (!$booking) {
            $_SESSION['booking_error'] = 'ไม่พบข้อมูลการจองที่ต้องการอนุมัติ';
            $response->redirect('/admin/bookings');
            return;
        }

        try {
            $result = $this->bookingService->approveBooking($id);
            if ($result['success']) {
                // Log audit log
                $db = Database::getConnection();
                $stmtLog = $db->prepare("
                    INSERT INTO audit_logs (user_id, username, action, table_name, record_id, previous_value, new_value)
                    VALUES (:user_id, :username, 'Approve booking', 'car_booking', :record_id, :prev_value, :new_value)
                ");
                $stmtLog->execute([
                    'user_id' => $_SESSION['admin_user']['id'],
                    'username' => $_SESSION['admin_user']['username'],
                    'record_id' => $id,
                    'prev_value' => json_encode($booking),
                    'new_value' => json_encode(['status' => 'Confirmed'])
                ]);

                $_SESSION['booking_success'] = $result['message'];
            } else {
                $_SESSION['booking_error'] = $result['message'];
            }
        } catch (Exception $e) {
            $_SESSION['booking_error'] = 'เกิดข้อผิดพลาดในการอนุมัติ: ' . $e->getMessage();
        }

        $response->redirect('/admin/bookings');
    }

    public function cancel(Request $request, Response $response, int $id) {
        $booking = $this->bookingRepo->find($id);
        if (!$booking) {
            $_SESSION['booking_error'] = 'ไม่พบข้อมูลการจองที่ต้องการยกเลิก';
            $response->redirect('/admin/bookings');
            return;
        }

        if ($booking['status'] === 'Cancelled') {
            $_SESSION['booking_error'] = 'การจองนี้ถูกยกเลิกไปก่อนหน้านี้แล้ว';
            $response->redirect('/admin/bookings');
            return;
        }

        $body = $request->getBody();
        $reason = trim($body['cancel_reason'] ?? '');
        if (empty($reason)) {
            $_SESSION['booking_error'] = 'กรุณาระบุเหตุผลในการยกเลิกการจอง';
            $response->redirect('/admin/bookings');
            return;
        }

        try {
            $this->bookingRepo->cancelWithReason($id, $reason);
            $this->bookingRepo->addCancelLog($id);

            // Log audit log
            $db = Database::getConnection();
            $stmtLog = $db->prepare("
                INSERT INTO audit_logs (user_id, username, action, table_name, record_id, previous_value, new_value)
                VALUES (:user_id, :username, 'Cancel booking', 'car_booking', :record_id, :prev_value, :new_value)
            ");
            $stmtLog->execute([
                'user_id' => $_SESSION['admin_user']['id'],
                'username' => $_SESSION['admin_user']['username'],
                'record_id' => $id,
                'prev_value' => json_encode($booking),
                'new_value' => json_encode(['status' => 'Cancelled', 'cancel_reason' => $reason])
            ]);

            $_SESSION['booking_success'] = 'ยกเลิกการจองเรียบร้อยแล้ว';
        } catch (Exception $e) {
            $_SESSION['booking_error'] = 'เกิดข้อผิดพลาดในการยกเลิก: ' . $e->getMessage();
        }

        $response->redirect('/admin/bookings');
    }
}
