<?php
namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Response;
use App\Core\Database;
use App\Core\Router;
use App\Core\AuthMiddleware;
use Exception;

class LineHelperController {
    public function __construct() {
        AuthMiddleware::checkAdmin();
    }

    public function index(Request $request, Response $response) {
        $db = Database::getConnection();
        
        $currentMonthStart = date('Y-m-01');
        $currentMonthEnd   = date('Y-m-t');

        // Fetch all cars and their monthly quota status for the current month
        $carsData = $db->query("
            SELECT
                c.id,
                c.license_plate,
                c.fuel_type,
                c.status,
                COALESCE(c.remaining_low_threshold, 20.00) AS threshold,
                COALESCE(q.monthly_quota, 0) AS quota_liters,
                COALESCE(SUM(r.liters), 0) AS used_liters
            FROM car_detail c
            LEFT JOIN car_quota_history q
                ON q.car_id = c.id
                AND q.id = (
                    SELECT id FROM car_quota_history
                    WHERE car_id = c.id
                    ORDER BY effective_month DESC
                    LIMIT 1
                )
            LEFT JOIN gas_receipt r
                ON r.car_id = c.id
                AND r.status = 'Verified'
                AND r.receipt_date BETWEEN '{$currentMonthStart}' AND '{$currentMonthEnd}'
            GROUP BY c.id, c.license_plate, c.fuel_type, c.status, c.remaining_low_threshold, q.monthly_quota
            ORDER BY c.license_plate ASC
        ")->fetchAll();

        $lowQuotaCars = [];
        foreach ($carsData as $car) {
            $remaining = $car['quota_liters'] - $car['used_liters'];
            if ($car['quota_liters'] > 0 && $remaining <= $car['threshold']) {
                $lowQuotaCars[] = $car;
            }
        }

        // Format vehicle list string
        $vehicleListLines = [];
        foreach ($lowQuotaCars as $car) {
            $remaining = $car['quota_liters'] - $car['used_liters'];
            $usedFormatted = (float)$car['used_liters'];
            $quotaFormatted = (float)$car['quota_liters'];
            $remainingFormatted = (float)$remaining;
            $vehicleListLines[] = "🚗 " . $car['license_plate'] . "\nใช้น้ำมันแล้ว: " . $usedFormatted . " / " . $quotaFormatted . " ลิตร (คงเหลือ: " . $remainingFormatted . " ลิตร)";
        }
        
        if (empty($vehicleListLines)) {
            $vehicleListString = "ไม่มีรถยนต์ที่ปริมาณน้ำมันคงเหลือต่ำกว่าเกณฑ์ในเดือนนี้";
        } else {
            $vehicleListString = implode("\n\n", $vehicleListLines);
        }

        // Format dates
        $thaiShortMonths = ['', 'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
        $thaiFullMonths = ['', 'มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน', 'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'];
        
        $day = (int)date('d');
        $monthIndex = (int)date('n');
        $year = (int)date('Y') + 543;
        
        $thaiDate = $day . ' ' . $thaiShortMonths[$monthIndex] . ' ' . $year;
        $thaiMonthYear = $thaiFullMonths[$monthIndex] . ' ' . $year;

        // Fetch Template from DB
        $templateStmt = $db->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'line_announcement_template' LIMIT 1");
        $templateStmt->execute();
        $template = $templateStmt->fetchColumn();
        
        if (!$template) {
            $template = "📢 อัปเดตโควต้าน้ำมันรถยนต์ส่วนกลาง (ประจำวันที่ {date})\n\n{vehicle_list}\n\n🛑 โปรดทราบ:\nหากมีการใช้งานน้ำมันเกินโควต้าที่กำหนด จะไม่สามารถเบิกใบเสร็จค่าน้ำมันส่วนที่เกินได้\nขอให้ทุกท่านระมัดระวังและวางแผนการเดินทางอย่างรอบคอบ";
        }

        // Interpolate variables
        $interpolatedMessage = str_replace(
            ['{date}', '{month_year}', '{vehicle_list}'],
            [$thaiDate, $thaiMonthYear, $vehicleListString],
            $template
        );

        // Interpolate vehicle-specific placeholders: {used:PLATE}, {quota:PLATE}, {remaining:PLATE}
        foreach ($carsData as $car) {
            $plate = $car['license_plate'];
            $usedVal = (float)$car['used_liters'];
            $quotaVal = (float)$car['quota_liters'];
            $remainingVal = (float)($car['quota_liters'] - $car['used_liters']);

            $interpolatedMessage = str_replace(
                ["{used:$plate}", "{quota:$plate}", "{remaining:$plate}"],
                [$usedVal, $quotaVal, $remainingVal],
                $interpolatedMessage
            );
        }

        // Clean up any unmatched vehicle placeholders to avoid raw code leaking, providing a helpful typo warning
        $interpolatedMessage = preg_replace('/\{(used|quota|remaining):([^\}]+)\}/u', '(ไม่พบทะเบียนรถ: $2)', $interpolatedMessage);

        $success = $_SESSION['line_helper_success'] ?? null;
        $error = $_SESSION['line_helper_error'] ?? null;
        unset($_SESSION['line_helper_success'], $_SESSION['line_helper_error']);

        $router = new Router($request, $response);
        return $router->renderView('admin/line_helper/index', [
            'template' => $template,
            'interpolatedMessage' => $interpolatedMessage,
            'lowQuotaCars' => $lowQuotaCars,
            'allCars' => $carsData,
            'success' => $success,
            'error' => $error
        ]);
    }

    public function save(Request $request, Response $response) {
        $body = $request->getBody();
        $templateText = trim($body['template_text'] ?? '');
        $thresholds = $body['thresholds'] ?? [];

        try {
            $db = Database::getConnection();
            $db->beginTransaction();

            // 1. Save template setting
            $stmtSettings = $db->prepare("
                INSERT INTO system_settings (setting_key, setting_value, description)
                VALUES ('line_announcement_template', :val1, 'เทมเพลตสำหรับข้อความประกาศอัปเดตโควต้าน้ำมันทาง LINE')
                ON DUPLICATE KEY UPDATE setting_value = :val2
            ");
            $stmtSettings->execute([
                'val1' => $templateText,
                'val2' => $templateText
            ]);

            // 2. Save thresholds
            if (is_array($thresholds)) {
                $stmtCar = $db->prepare("UPDATE car_detail SET remaining_low_threshold = :threshold WHERE id = :id");
                foreach ($thresholds as $carId => $thresholdVal) {
                    $stmtCar->execute([
                        'threshold' => (float)$thresholdVal,
                        'id' => (int)$carId
                    ]);
                }
            }

            // Log Audit Log
            $stmtLog = $db->prepare("
                INSERT INTO audit_logs (user_id, username, action, table_name, record_id, new_value)
                VALUES (:user_id, :username, 'Update LINE Broadcast settings', 'system_settings', 0, :new_value)
            ");
            $stmtLog->execute([
                'user_id' => $_SESSION['admin_user']['id'],
                'username' => $_SESSION['admin_user']['username'],
                'new_value' => json_encode([
                    'template' => $templateText,
                    'thresholds' => $thresholds
                ])
            ]);

            $db->commit();
            $_SESSION['line_helper_success'] = 'บันทึกการตั้งค่าประกาศ LINE เรียบร้อยแล้ว';
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            $_SESSION['line_helper_error'] = 'เกิดข้อผิดพลาดในการบันทึก: ' . $e->getMessage();
        }

        $response->redirect('/admin/line-helper');
    }
}
