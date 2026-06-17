<?php
namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Response;
use App\Core\Database;
use App\Core\Router;
use App\Core\AuthMiddleware;
use App\Services\ReceiptService;
use App\Repositories\MySQL\ReceiptRepository;
use App\Repositories\MySQL\CarRepository;
use Exception;

class ReceiptController {
    protected ReceiptService $receiptService;
    protected ReceiptRepository $receiptRepo;
    protected CarRepository $carRepo;

    public function __construct() {
        AuthMiddleware::checkAdmin();
        $this->receiptService = new ReceiptService();
        $this->receiptRepo = new ReceiptRepository();
        $this->carRepo = new CarRepository();
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

        $limit = 10; // Number of items per page
        $totalCount = $this->receiptRepo->count($search, $carId, $employeeId, $startDate, $endDate);
        $totalPages = (int)ceil($totalCount / $limit);
        if ($totalPages < 1) {
            $totalPages = 1;
        }
        if ($page > $totalPages) {
            $page = $totalPages;
        }

        $offset = ($page - 1) * $limit;
        $receipts = $this->receiptRepo->search($search, $limit, $offset, $carId, $employeeId, $startDate, $endDate);
        
        // Calculate totals across all matching non-cancelled records in search space
        $totals = $this->receiptRepo->getSearchTotals($search, $carId, $employeeId, $startDate, $endDate);
        $totalLiters = $totals['total_liters'];
        $totalAmount = $totals['total_amount'];

        $success = $_SESSION['receipt_success'] ?? null;
        $error = $_SESSION['receipt_error'] ?? null;
        unset($_SESSION['receipt_success'], $_SESSION['receipt_error']);

        $db = Database::getConnection();
        $cars = $this->carRepo->all();
        $employees = $db->query("SELECT * FROM employee WHERE status = 'Active' ORDER BY full_name ASC")->fetchAll();

        $router = new Router($request, $response);
        return $router->renderView('admin/receipt/index', [
            'receipts' => $receipts,
            'search' => $search,
            'carId' => $carId,
            'employeeId' => $employeeId,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'cars' => $cars,
            'employees' => $employees,
            'page' => $page,
            'totalPages' => $totalPages,
            'totalCount' => $totalCount,
            'totalLiters' => $totalLiters,
            'totalAmount' => $totalAmount,
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

        $receipts = $this->receiptRepo->exportAll($search, $carId, $employeeId, $startDate, $endDate);

        // Set headers for CSV download
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="รายงานใบเสร็จค่าน้ำมัน_' . date('Ymd_His') . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        // Write UTF-8 BOM for Excel compatibility (solves Thai font corruption!)
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Write CSV Header
        fputcsv($output, [
            'เลขที่ใบเสร็จ',
            'วันที่เติม',
            'ทะเบียนรถ',
            'พนักงานผู้เติม',
            'ประเภทน้ำมัน',
            'ปริมาณน้ำมัน (ลิตร)',
            'ยอดเงินค่าน้ำมัน (บาท)',
            'ราคาต่อลิตร (บาท)',
            'สถานะใบเสร็จ'
        ]);
        
        // Status mapping to make it clear for spreadsheet users
        $statusLabels = [
            'Pending verification' => 'รอตรวจอนุมัติ',
            'Verified' => 'อนุมัติแล้ว',
            'Cancelled' => 'ยกเลิกใบเสร็จ'
        ];

        foreach ($receipts as $r) {
            $statusLabel = $statusLabels[$r['status']] ?? $r['status'];
            fputcsv($output, [
                $r['receipt_number'],
                date('d/m/Y', strtotime($r['receipt_date'])),
                $r['license_plate'],
                $r['employee_name'],
                $r['fuel_type'],
                number_format($r['liters'], 2, '.', ''),
                number_format($r['amount'], 2, '.', ''),
                number_format($r['price_per_liter'], 2, '.', ''),
                $statusLabel
            ]);
        }
        
        fclose($output);
        exit;
    }

    public function new(Request $request, Response $response) {
        $db = Database::getConnection();
        
        $employees = $db->query("
            SELECT e.*, pos.name AS position_name, divi.name AS division_name 
            FROM employee e 
            LEFT JOIN position pos ON e.position_id = pos.id 
            LEFT JOIN division divi ON e.division_id = divi.id
            WHERE e.status = 'Active' 
            ORDER BY CASE WHEN divi.name IS NULL THEN 1 ELSE 0 END, divi.name ASC, e.full_name ASC
        ")->fetchAll();
        $cars = $db->query("SELECT * FROM car_detail WHERE status = 'Active' ORDER BY license_plate ASC")->fetchAll();

        $error = $_SESSION['receipt_form_error'] ?? null;
        unset($_SESSION['receipt_form_error']);

        $router = new Router($request, $response);
        return $router->renderView('admin/receipt/new', [
            'employees' => $employees,
            'cars' => $cars,
            'error' => $error
        ]);
    }

    public function create(Request $request, Response $response) {
        $body = $request->getBody();
        $files = $request->getFiles();

        $number = trim($body['receipt_number'] ?? '');
        $receiptDate = trim($body['receipt_date'] ?? '');
        $empId = (int)($body['employee_id'] ?? 0);
        $carId = (int)($body['car_id'] ?? 0);
        $amount = (float)($body['amount'] ?? 0);
        $liters = (float)($body['liters'] ?? 0);
        $mileage = isset($body['mileage']) && trim($body['mileage']) !== '' ? (int)$body['mileage'] : null;

        if (empty($number) || empty($receiptDate) || !$empId || !$carId || $amount <= 0 || $liters <= 0) {
            $_SESSION['receipt_form_error'] = 'กรุณากรอกข้อมูลตัวเลขและสนามข้อมูลที่จำเป็นให้ถูกต้อง';
            $response->redirect('/admin/receipts/new');
        }

        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("SELECT * FROM car_detail WHERE id = :id");
            $stmt->execute(['id' => $carId]);
            $car = $stmt->fetch();
            if (!$car) {
                throw new Exception("ไม่พบข้อมูลยานพาหนะดังกล่าวในระบบ");
            }
            $fuelType = $car['fuel_type'];

            $filePath = null;

            // Handle file upload
            if (isset($files['receipt_image'])) {
                $fileError = $files['receipt_image']['error'];
                if ($fileError !== UPLOAD_ERR_OK && $fileError !== UPLOAD_ERR_NO_FILE) {
                    if ($fileError === UPLOAD_ERR_INI_SIZE || $fileError === UPLOAD_ERR_FORM_SIZE) {
                        throw new Exception("ไฟล์หลักฐานแนบมีขนาดใหญ่เกินกว่าที่เซิร์ฟเวอร์ PHP กำหนด (สูงสุดไม่เกิน " . ini_get('upload_max_filesize') . ")");
                    }
                    throw new Exception("เกิดข้อผิดพลาดในการอัปโหลดไฟล์หลักฐาน (รหัสข้อผิดพลาด: {$fileError})");
                }
                
                if ($fileError === UPLOAD_ERR_OK) {
                    $fileTmpPath = $files['receipt_image']['tmp_name'];
                    $fileName = $files['receipt_image']['name'];
                    $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                    
                    // Allow images and PDFs
                    $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'pdf'];
                    if (in_array($fileExtension, $allowedExtensions)) {
                        $newFileName = 'receipt_' . time() . '_' . rand(1000, 9999) . '.' . $fileExtension;
                        
                        // Create uploads directory in workspace if not exists
                        $uploadDir = dirname(__DIR__, 3) . '/public/uploads/';
                        if (!is_dir($uploadDir)) {
                            mkdir($uploadDir, 0755, true);
                        }
                        
                        $destPath = $uploadDir . $newFileName;
                        if (move_uploaded_file($fileTmpPath, $destPath)) {
                            $filePath = '/uploads/' . $newFileName;
                        } else {
                            throw new Exception("ไม่สามารถย้ายไฟล์แนบอัปโหลดไปยังโฟลเดอร์ปลายทางได้");
                        }
                    } else {
                        throw new Exception("ไฟล์แนบต้องเป็นภาพ JPG, JPEG, PNG, WEBP หรือเอกสาร PDF เท่านั้น");
                    }
                }
            }

            // Save receipt
            $result = $this->receiptService->recordReceipt([
                'receipt_number' => $number,
                'receipt_date' => $receiptDate,
                'record_date' => date('Y-m-d'),
                'employee_id' => $empId,
                'car_id' => $carId,
                'amount' => $amount,
                'liters' => $liters,
                'fuel_type' => $fuelType,
                'mileage' => $mileage,
                'status' => 'Verified' // Automatically Verified (no approval needed)
            ], $filePath);

            if ($result['success']) {
                // Discord notifications
                if ($filePath) {
                    \App\Core\DiscordNotifier::sendReceiptPending($result['receipt_id']);
                }
                \App\Core\DiscordNotifier::checkAndSendQuotaAlerts($carId, date('Y-m', strtotime($receiptDate)));

                // Log audit log
                $db = Database::getConnection();
                $stmtLog = $db->prepare("
                    INSERT INTO audit_logs (user_id, username, action, table_name, record_id, new_value)
                    VALUES (:user_id, :username, 'Create', 'gas_receipt', :record_id, :new_value)
                ");
                $stmtLog->execute([
                    'user_id' => $_SESSION['admin_user']['id'],
                    'username' => $_SESSION['admin_user']['username'],
                    'record_id' => $result['receipt_id'],
                    'new_value' => json_encode(['receipt_number' => $number, 'amount' => $amount, 'liters' => $liters, 'fuel_type' => $fuelType])
                ]);

                $_SESSION['receipt_success'] = 'บันทึกใบเสร็จน้ำมันเข้าระบบแล้ว (อนุมัติผ่านระบบอัตโนมัติแล้ว)';
                $response->redirect('/admin/receipts');
            } else {
                // Delete uploaded file if DB insert failed
                if ($filePath) {
                    unlink(dirname(__DIR__, 3) . '/public' . $filePath);
                }
                $_SESSION['receipt_form_error'] = $result['message'];
                $response->redirect('/admin/receipts/new');
            }

        } catch (Exception $e) {
            $_SESSION['receipt_form_error'] = 'เกิดข้อผิดพลาด: ' . $e->getMessage();
            $response->redirect('/admin/receipts/new');
        }
    }

    public function verify(Request $request, Response $response, int $id) {
        $receipt = $this->receiptRepo->find($id);
        if (!$receipt) {
            $_SESSION['receipt_error'] = 'ไม่พบใบเสร็จดังกล่าว';
            $response->redirect('/admin/receipts');
        }

        try {
            $this->receiptRepo->updateStatus($id, 'Verified');
            \App\Core\DiscordNotifier::sendReceiptVerificationResult($id, 'Approved', $_SESSION['admin_user']['full_name'] ?? 'ผู้ดูแลระบบ');
            \App\Core\DiscordNotifier::checkAndSendQuotaAlerts($receipt['car_id'], date('Y-m', strtotime($receipt['receipt_date'])));

            // Audit Log
            $db = Database::getConnection();
            $stmtLog = $db->prepare("
                INSERT INTO audit_logs (user_id, username, action, table_name, record_id, previous_value, new_value)
                VALUES (:user_id, :username, 'Verify receipt', 'gas_receipt', :record_id, :prev_value, :new_value)
            ");
            $stmtLog->execute([
                'user_id' => $_SESSION['admin_user']['id'],
                'username' => $_SESSION['admin_user']['username'],
                'record_id' => $id,
                'prev_value' => json_encode($receipt),
                'new_value' => json_encode(['status' => 'Verified'])
            ]);

            $_SESSION['receipt_success'] = 'ยืนยันใบเสร็จน้ำมันเรียบร้อย (บวกยอดเข้าน้ำมันโควต้าแล้ว)';
        } catch (Exception $e) {
            $_SESSION['receipt_error'] = 'เกิดข้อผิดพลาดในการตรวจสอบ: ' . $e->getMessage();
        }
        $response->redirect('/admin/receipts');
    }

    public function cancel(Request $request, Response $response, int $id) {
        $receipt = $this->receiptRepo->find($id);
        if (!$receipt) {
            $_SESSION['receipt_error'] = 'ไม่พบใบเสร็จดังกล่าว';
            $response->redirect('/admin/receipts');
        }

        try {
            $this->receiptRepo->updateStatus($id, 'Cancelled');
            \App\Core\DiscordNotifier::sendReceiptVerificationResult($id, 'Rejected', $_SESSION['admin_user']['full_name'] ?? 'ผู้ดูแลระบบ');
            \App\Core\DiscordNotifier::checkAndSendQuotaAlerts($receipt['car_id'], date('Y-m', strtotime($receipt['receipt_date'])));

            // Audit Log
            $db = Database::getConnection();
            $stmtLog = $db->prepare("
                INSERT INTO audit_logs (user_id, username, action, table_name, record_id, previous_value, new_value)
                VALUES (:user_id, :username, 'Cancel receipt', 'gas_receipt', :record_id, :prev_value, :new_value)
            ");
            $stmtLog->execute([
                'user_id' => $_SESSION['admin_user']['id'],
                'username' => $_SESSION['admin_user']['username'],
                'record_id' => $id,
                'prev_value' => json_encode($receipt),
                'new_value' => json_encode(['status' => 'Cancelled'])
            ]);

            $_SESSION['receipt_success'] = 'ยกเลิกใบเสร็จน้ำมันนี้เรียบร้อย (หักลบยอดออกจากระบบคำนวณโควต้า)';
        } catch (Exception $e) {
            $_SESSION['receipt_error'] = 'เกิดข้อผิดพลาดในการยกเลิก: ' . $e->getMessage();
        }
        $response->redirect('/admin/receipts');
    }

    public function edit(Request $request, Response $response, int $id) {
        $receipt = $this->receiptRepo->find($id);
        if (!$receipt) {
            $_SESSION['receipt_error'] = 'ไม่พบใบเสร็จดังกล่าว';
            $response->redirect('/admin/receipts');
        }

        $db = Database::getConnection();
        $employees = $db->query("
            SELECT e.*, pos.name AS position_name, divi.name AS division_name 
            FROM employee e 
            LEFT JOIN position pos ON e.position_id = pos.id 
            LEFT JOIN division divi ON e.division_id = divi.id
            WHERE e.status = 'Active' 
            ORDER BY CASE WHEN divi.name IS NULL THEN 1 ELSE 0 END, divi.name ASC, e.full_name ASC
        ")->fetchAll();
        $cars = $db->query("SELECT * FROM car_detail WHERE status = 'Active' ORDER BY license_plate ASC")->fetchAll();

        $error = $_SESSION['receipt_form_error'] ?? null;
        unset($_SESSION['receipt_form_error']);

        $router = new Router($request, $response);
        return $router->renderView('admin/receipt/edit', [
            'receipt' => $receipt,
            'employees' => $employees,
            'cars' => $cars,
            'error' => $error
        ]);
    }

    public function update(Request $request, Response $response, int $id) {
        $receipt = $this->receiptRepo->find($id);
        if (!$receipt) {
            $_SESSION['receipt_error'] = 'ไม่พบใบเสร็จดังกล่าว';
            $response->redirect('/admin/receipts');
        }

        $body = $request->getBody();
        $files = $request->getFiles();

        $number = trim($body['receipt_number'] ?? '');
        $receiptDate = trim($body['receipt_date'] ?? '');
        $empId = (int)($body['employee_id'] ?? 0);
        $carId = (int)($body['car_id'] ?? 0);
        $amount = (float)($body['amount'] ?? 0);
        $liters = (float)($body['liters'] ?? 0);
        $mileage = isset($body['mileage']) && trim($body['mileage']) !== '' ? (int)$body['mileage'] : null;
        $status = trim($body['status'] ?? $receipt['status']);

        if (empty($number) || empty($receiptDate) || !$empId || !$carId || $amount <= 0 || $liters <= 0) {
            $_SESSION['receipt_form_error'] = 'กรุณากรอกข้อมูลตัวเลขและสนามข้อมูลที่จำเป็นให้ถูกต้อง';
            $response->redirect("/admin/receipts/edit/{$id}");
        }

        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("SELECT * FROM car_detail WHERE id = :id");
            $stmt->execute(['id' => $carId]);
            $car = $stmt->fetch();
            if (!$car) {
                throw new Exception("ไม่พบข้อมูลยานพาหนะดังกล่าวในระบบ");
            }
            $fuelType = $car['fuel_type'];

            $filePath = null;

            // Handle file upload
            if (isset($files['receipt_image'])) {
                $fileError = $files['receipt_image']['error'];
                if ($fileError !== UPLOAD_ERR_OK && $fileError !== UPLOAD_ERR_NO_FILE) {
                     if ($fileError === UPLOAD_ERR_INI_SIZE || $fileError === UPLOAD_ERR_FORM_SIZE) {
                         throw new Exception("ไฟล์หลักฐานแนบมีขนาดใหญ่เกินกว่าที่เซิร์ฟเวอร์ PHP กำหนด (สูงสุดไม่เกิน " . ini_get('upload_max_filesize') . ")");
                     }
                     throw new Exception("เกิดข้อผิดพลาดในการอัปโหลดไฟล์หลักฐาน (รหัสข้อผิดพลาด: {$fileError})");
                }
                
                if ($fileError === UPLOAD_ERR_OK) {
                    $fileTmpPath = $files['receipt_image']['tmp_name'];
                    $fileName = $files['receipt_image']['name'];
                    $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                    
                    // Allow images and PDFs
                    $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'pdf'];
                    if (in_array($fileExtension, $allowedExtensions)) {
                        $newFileName = 'receipt_' . time() . '_' . rand(1000, 9999) . '.' . $fileExtension;
                        
                        $uploadDir = dirname(__DIR__, 3) . '/public/uploads/';
                        if (!is_dir($uploadDir)) {
                            mkdir($uploadDir, 0755, true);
                        }
                        
                        $destPath = $uploadDir . $newFileName;
                        if (move_uploaded_file($fileTmpPath, $destPath)) {
                            $filePath = '/uploads/' . $newFileName;
                        } else {
                            throw new Exception("ไม่สามารถย้ายไฟล์แนบอัปโหลดไปยังโฟลเดอร์ปลายทางได้");
                        }
                    } else {
                        throw new Exception("ไฟล์แนบต้องเป็นภาพ JPG, JPEG, PNG, WEBP หรือเอกสาร PDF เท่านั้น");
                    }
                }
            }

            // Save updated receipt
            $result = $this->receiptService->updateReceipt($id, [
                'receipt_number' => $number,
                'receipt_date' => $receiptDate,
                'employee_id' => $empId,
                'car_id' => $carId,
                'amount' => $amount,
                'liters' => $liters,
                'fuel_type' => $fuelType,
                'mileage' => $mileage,
                'status' => $status
            ], $filePath);

            if ($result['success']) {
                // Discord alerts
                if ($filePath) {
                    \App\Core\DiscordNotifier::sendReceiptPending($id);
                }
                if ($status === 'Verified' || $receipt['status'] === 'Verified') {
                    \App\Core\DiscordNotifier::checkAndSendQuotaAlerts($carId, date('Y-m', strtotime($receiptDate)));
                }

                // Log audit log
                $stmtLog = $db->prepare("
                    INSERT INTO audit_logs (user_id, username, action, table_name, record_id, previous_value, new_value)
                    VALUES (:user_id, :username, 'Update', 'gas_receipt', :record_id, :prev_value, :new_value)
                ");
                $stmtLog->execute([
                    'user_id' => $_SESSION['admin_user']['id'],
                    'username' => $_SESSION['admin_user']['username'],
                    'record_id' => $id,
                    'prev_value' => json_encode($receipt),
                    'new_value' => json_encode(['receipt_number' => $number, 'amount' => $amount, 'liters' => $liters, 'fuel_type' => $fuelType, 'status' => $status])
                ]);

                $_SESSION['receipt_success'] = 'แก้ไขและอัปเดตรายละเอียดใบเสร็จค่าน้ำมันเรียบร้อยแล้ว';
                $response->redirect('/admin/receipts');
            } else {
                if ($filePath) {
                    @unlink(dirname(__DIR__, 3) . '/public' . $filePath);
                }
                $_SESSION['receipt_form_error'] = $result['message'];
                $response->redirect("/admin/receipts/edit/{$id}");
            }

        } catch (Exception $e) {
            $_SESSION['receipt_form_error'] = 'เกิดข้อผิดพลาด: ' . $e->getMessage();
            $response->redirect("/admin/receipts/edit/{$id}");
        }
    }
}
