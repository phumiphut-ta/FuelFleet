<?php
namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Response;
use App\Core\Database;
use App\Core\Router;
use App\Core\AuthMiddleware;
use Exception;
use Mpdf\Mpdf;

class ReportController {
    public function __construct() {
        AuthMiddleware::checkAdmin();
    }

    public function index(Request $request, Response $response) {
        $db = Database::getConnection();
        $cars = $db->query("SELECT * FROM car_detail ORDER BY license_plate ASC")->fetchAll();

        $success = $_SESSION['report_success'] ?? null;
        $error = $_SESSION['report_error'] ?? null;
        unset($_SESSION['report_success'], $_SESSION['report_error']);

        $router = new Router($request, $response);
        return $router->renderView('admin/report/index', [
            'cars' => $cars,
            'success' => $success,
            'error' => $error
        ]);
    }

    public function generate(Request $request, Response $response) {
        $body = $request->getBody();
        $reportType = (int)($body['report_type'] ?? 1);
        $carId = isset($body['car_id']) && $body['car_id'] !== '' ? (int)$body['car_id'] : null;
        $month = trim($body['month'] ?? date('m'));
        $year = trim($body['year'] ?? date('Y'));
        $startDate = isset($body['start_date']) && $body['start_date'] !== '' ? trim($body['start_date']) : null;
        $endDate = isset($body['end_date']) && $body['end_date'] !== '' ? trim($body['end_date']) : null;
        
        $db = Database::getConnection();
        $printDate = date('d/m/Y H:i:s');
        $printedBy = $_SESSION['admin_user']['full_name'] ?? 'ผู้ดูแลระบบ';

        // 1. Log Report Printing
        $stmtPrint = $db->prepare("
            INSERT INTO report_print_log (report_type, printed_by, filter_criteria)
            VALUES (:report_type, :printed_by, :criteria)
        ");
        $criteria = json_encode(['car_id' => $carId, 'month' => $month, 'year' => $year, 'start_date' => $startDate, 'end_date' => $endDate]);
        $stmtPrint->execute([
            'report_type' => "Report {$reportType}",
            'printed_by' => $printedBy,
            'criteria' => $criteria
        ]);

        // Add audit log
        $stmtAudit = $db->prepare("
            INSERT INTO audit_logs (user_id, username, action, table_name, record_id, new_value)
            VALUES (:user_id, :username, 'Generate report', 'report_print_log', :record_id, :new_value)
        ");
        $stmtAudit->execute([
            'user_id' => $_SESSION['admin_user']['id'],
            'username' => $_SESSION['admin_user']['username'],
            'record_id' => $db->lastInsertId(),
            'new_value' => json_encode(['report_type' => $reportType, 'criteria' => $criteria])
        ]);

        // 2. Initialize mPDF with Unicode Thai fonts and a writable temporary directory
        $tempDir = dirname(__DIR__, 3) . '/public/uploads/tmp';
        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0777, true);
        }

        $format = 'A4';
        if ($reportType === 2 || $reportType === 9 || $reportType === 10) {
            $format = 'A4-L';
        }

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => $format,
            'margin_left' => 15,
            'margin_right' => 15,
            'margin_top' => 15,
            'margin_bottom' => 20,
            'autoScriptToLang' => true,
            'autoLangToFont' => true,
            'tempDir' => $tempDir
        ]);

        $mpdf->SetFooter('|หน้า {PAGENO}/{nbpg}|');

        // Base styles for professional official reports
        $htmlStyles = '
            <style>
                body { font-family: "Garuda", sans-serif; color: #222; }
                h1 { text-align: center; font-size: 20px; font-weight: bold; margin-bottom: 5px; }
                .subtitle { text-align: center; font-size: 13px; color: #555; margin-bottom: 25px; }
                .meta-table { width: 100%; font-size: 11px; margin-bottom: 20px; border-bottom: 1px solid #ddd; padding-bottom: 10px; }
                .report-table { width: 100%; border-collapse: collapse; font-size: 12px; margin-top: 10px; }
                .report-table th { background-color: #f1f5f9; border: 1px solid #cbd5e1; padding: 10px; font-weight: bold; text-align: left; }
                .report-table td { border: 1px solid #cbd5e1; padding: 8px 10px; }
                .text-right { text-align: right; }
                .text-center { text-align: center; }
                .footer { font-size: 10px; color: #777; text-align: center; border-top: 1px solid #ddd; padding-top: 8px; margin-top: 30px; }
                .total-row { font-weight: bold; background-color: #f8fafc; }
            </style>
        ';

        $title = "";
        $content = "";

        switch ($reportType) {
            case 1: // Monthly Fuel Usage Report
                $title = "รายงานการใช้น้ำมันรายเดือน (Monthly Fuel Usage Report)";
                $effectiveMonth = "{$year}-{$month}-01";
                $monthYear = "{$month}-{$year}";

                 $stmt = $db->prepare("
                    SELECT 
                        c.license_plate, 
                        c.fuel_type, 
                        IFNULL(
                            (SELECT q.monthly_quota 
                             FROM car_quota_history q 
                             WHERE q.car_id = c.id AND q.effective_month = :effective_month 
                             LIMIT 1), 
                            0
                        ) AS monthly_quota,
                        IFNULL(
                            (SELECT SUM(r.liters) 
                             FROM gas_receipt r 
                             WHERE r.car_id = c.id AND r.status = 'Verified' AND DATE_FORMAT(r.receipt_date, '%m-%Y') = :month_year_liters), 
                            0
                        ) AS used_liters,
                        IFNULL(
                            (SELECT SUM(r.amount) 
                             FROM gas_receipt r 
                             WHERE r.car_id = c.id AND r.status = 'Verified' AND DATE_FORMAT(r.receipt_date, '%m-%Y') = :month_year_amount), 
                            0
                        ) AS used_amount
                    FROM car_detail c
                    WHERE c.status = 'Active'
                    ORDER BY c.license_plate ASC
                ");
                $stmt->execute([
                    'effective_month' => $effectiveMonth,
                    'month_year_liters' => $monthYear,
                    'month_year_amount' => $monthYear
                ]);
                $data = $stmt->fetchAll();

                $content = '
                    <h1>' . $title . '</h1>
                    <div class="subtitle">ประจำเดือน ' . $month . '/' . $year . '</div>
                    <table class="meta-table">
                        <tr>
                            <td>พิมพ์โดย: ' . htmlspecialchars($printedBy) . '</td>
                            <td class="text-right">พิมพ์เมื่อ: ' . $printDate . '</td>
                        </tr>
                    </table>
                    <table class="report-table">
                        <thead>
                            <tr>
                                <th>ทะเบียนรถ</th>
                                <th>ประเภทพลังงาน</th>
                                <th class="text-right">โควต้าน้ำมัน (ลิตร)</th>
                                <th class="text-right">ใช้ไป (ลิตร)</th>
                                <th class="text-right">คงเหลือ (ลิตร)</th>
                                <th class="text-right">ค่าน้ำมันรวม (บาท)</th>
                            </tr>
                        </thead>
                        <tbody>';
                if (empty($data)) {
                    $content .= '<tr><td colspan="6" class="text-center">ไม่พบข้อมูลรถยนต์หลวงในระบบ</td></tr>';
                } else {
                    $sumQuota = 0;
                    $sumLiters = 0;
                    $sumRemaining = 0;
                    $sumAmount = 0;
                    foreach ($data as $row) {
                        $remaining = $row['monthly_quota'] - $row['used_liters'];
                        $sumQuota += $row['monthly_quota'];
                        $sumLiters += $row['used_liters'];
                        $sumRemaining += $remaining;
                        $sumAmount += $row['used_amount'];

                        $remainingText = number_format($remaining, 2) . ' L';
                        if ($remaining < 0) {
                            $remainingText = '<span style="color: #ef4444; font-weight: bold;">' . number_format($remaining, 2) . ' L</span>';
                        }

                        $content .= '
                            <tr>
                                <td>' . htmlspecialchars($row['license_plate']) . '</td>
                                <td>' . htmlspecialchars($row['fuel_type']) . '</td>
                                <td class="text-right">' . number_format($row['monthly_quota'], 2) . ' L</td>
                                <td class="text-right">' . number_format($row['used_liters'], 2) . ' L</td>
                                <td class="text-right">' . $remainingText . '</td>
                                <td class="text-right">' . number_format($row['used_amount'], 2) . ' ฿</td>
                            </tr>';
                    }
                    $content .= '
                        <tr class="total-row">
                            <td colspan="2" class="text-right">ผลรวมสรุป:</td>
                            <td class="text-right">' . number_format($sumQuota, 2) . ' L</td>
                            <td class="text-right">' . number_format($sumLiters, 2) . ' L</td>
                            <td class="text-right">' . number_format($sumRemaining, 2) . ' L</td>
                            <td class="text-right">' . number_format($sumAmount, 2) . ' ฿</td>
                        </tr>';
                }
                $content .= '</tbody></table>';
                break;

            case 2: // Fiscal Year Fuel Quota and Usage Matrix Report
                $title = "รายงานสถิติการใช้น้ำมันรถยนต์ส่วนกลางรายปีงบประมาณ";
                
                // CSS Overrides to compact the layout so that everything fits perfectly on a single A4-L page
                $htmlStyles .= '
                    <style>
                        h1 { margin-bottom: 2px; font-size: 18px; }
                        .subtitle { margin-bottom: 10px; font-size: 11px; }
                        .meta-table { margin-bottom: 8px; padding-bottom: 4px; }
                        .report-table { margin-top: 5px; font-size: 9.5px; }
                        .report-table th { padding: 5px 6px; }
                        .report-table td { padding: 3px 5px; }
                        .footer { margin-top: 8px; padding-top: 4px; font-size: 8.5px; }
                    </style>
                ';
                
                // Get end of fiscal year date
                $endDateStr = "{$year}-09-30";
                
                // 1. Fetch active cars and their active quotas in a single query
                $stmtCars = $db->prepare("
                    SELECT c.id, c.license_plate, c.fuel_type,
                           (SELECT q.monthly_quota 
                            FROM car_quota_history q 
                            WHERE q.car_id = c.id 
                              AND q.effective_month <= :end_date 
                            ORDER BY q.effective_month DESC 
                            LIMIT 1) AS active_quota
                    FROM car_detail c
                    WHERE c.status = 'Active'
                    ORDER BY c.license_plate ASC
                ");
                $stmtCars->execute(['end_date' => $endDateStr]);
                $cars = $stmtCars->fetchAll();
                
                // 2. Fetch monthly verified fuel usage for active cars
                $startDate = ($year - 1) . "-10-01";
                $endDate = $year . "-09-30";
                
                $stmtUsage = $db->prepare("
                    SELECT r.car_id, DATE_FORMAT(r.receipt_date, '%Y-%m') AS ym, SUM(r.liters) AS total_liters
                    FROM gas_receipt r
                    JOIN car_detail c ON r.car_id = c.id
                    WHERE r.status = 'Verified'
                      AND c.status = 'Active'
                      AND r.receipt_date >= :start_date
                      AND r.receipt_date <= :end_date
                    GROUP BY r.car_id, DATE_FORMAT(r.receipt_date, '%Y-%m')
                ");
                $stmtUsage->execute([
                    'start_date' => $startDate,
                    'end_date' => $endDate
                ]);
                $usageRows = $stmtUsage->fetchAll();
                
                $usageMap = [];
                foreach ($usageRows as $ur) {
                    $usageMap[$ur['car_id']][$ur['ym']] = (float)$ur['total_liters'];
                }
                
                // 3. Define the 12 months of the fiscal year in order (Oct of FY-1 to Sep of FY)
                $fyMonths = [
                    ['year' => $year - 1, 'month' => '10', 'name' => 'ต.ค.'],
                    ['year' => $year - 1, 'month' => '11', 'name' => 'พ.ย.'],
                    ['year' => $year - 1, 'month' => '12', 'name' => 'ธ.ค.'],
                    ['year' => $year, 'month' => '01', 'name' => 'ม.ค.'],
                    ['year' => $year, 'month' => '02', 'name' => 'ก.พ.'],
                    ['year' => $year, 'month' => '03', 'name' => 'มี.ค.'],
                    ['year' => $year, 'month' => '04', 'name' => 'เม.ย.'],
                    ['year' => $year, 'month' => '05', 'name' => 'พ.ค.'],
                    ['year' => $year, 'month' => '06', 'name' => 'มิ.ย.'],
                    ['year' => $year, 'month' => '07', 'name' => 'ก.ค.'],
                    ['year' => $year, 'month' => '08', 'name' => 'ส.ค.'],
                    ['year' => $year, 'month' => '09', 'name' => 'ก.ย.'],
                ];
                
                $content = '
                    <h1>' . $title . '</h1>
                    <div class="subtitle">ประจำปีงบประมาณ ' . $year . ' (1 ต.ค. ' . ($year - 1) . ' - 30 ก.ย. ' . $year . ')</div>
                    <table class="meta-table">
                        <tr>
                            <td>พิมพ์โดย: ' . htmlspecialchars($printedBy) . '</td>
                            <td class="text-right">พิมพ์เมื่อ: ' . $printDate . '</td>
                        </tr>
                    </table>';
                
                if (empty($cars)) {
                    $content .= '<p class="text-center" style="color: #64748b; font-size: 14px; margin-top: 30px;">ไม่พบข้อมูลรถยนต์ส่วนกลางที่เปิดใช้งานในระบบ</p>';
                } else {
                    $content .= '
                        <table class="report-table">
                            <thead>
                                <tr>
                                    <th class="text-center" style="background-color: #e2e8f0; border: 1px solid #94a3b8; font-weight: bold; width: 16%;">เดือน / ทะเบียนรถ</th>';
                    
                    // Render horizontal headers (license plates)
                    foreach ($cars as $car) {
                        $content .= '<th class="text-center" style="background-color: #e2e8f0; border: 1px solid #94a3b8; font-weight: bold;">' . htmlspecialchars($car['license_plate']) . '<br><span style="font-size: 9px; font-weight: normal; color: #475569;">(' . htmlspecialchars($car['fuel_type']) . ')</span></th>';
                    }
                    
                    $content .= '
                                </tr>
                            </thead>
                            <tbody>';
                    
                    // Row 1: Quota Row
                    $content .= '
                                <tr style="background-color: #f0fdf4; font-weight: bold; color: #15803d;">
                                    <td class="text-center" style="border: 1px solid #cbd5e1; padding: 10px;">โควต้าน้ำมัน (ลิตร/เดือน)</td>';
                    
                    foreach ($cars as $car) {
                        $quota = (float)($car['active_quota'] ?? 0.0);
                        $quotaText = $quota > 0 ? number_format($quota, 2) : '-';
                        $content .= '<td class="text-center" style="border: 1px solid #cbd5e1; padding: 10px;">' . $quotaText . '</td>';
                    }
                    
                    $content .= '
                                </tr>';
                    
                    // Subsequent Rows: Monthly Usage
                    $carTotals = array_fill_keys(array_column($cars, 'id'), 0.0);
                    
                    foreach ($fyMonths as $mInfo) {
                        $ymKey = sprintf("%04d-%02d", $mInfo['year'], $mInfo['month']);
                        $content .= '
                                <tr>
                                    <td class="text-center" style="font-weight: bold; background-color: #f8fafc; border: 1px solid #cbd5e1;">' . $mInfo['name'] . '</td>';
                        
                        foreach ($cars as $car) {
                            $carId = $car['id'];
                            $used = $usageMap[$carId][$ymKey] ?? 0.0;
                            $carTotals[$carId] += $used;
                            
                            $usedText = $used > 0 ? number_format($used, 2) : '-';
                            $content .= '<td class="text-right" style="border: 1px solid #cbd5e1;">' . $usedText . '</td>';
                        }
                        
                        $content .= '
                                </tr>';
                    }
                    
                    // Final Row: Total sum of the fiscal year for each car
                    $content .= '
                                <tr style="background-color: #eff6ff; font-weight: bold; color: #1d4ed8; border-top: 2px solid #3b82f6;">
                                    <td class="text-center" style="border: 1px solid #3b82f6; padding: 10px;">รวมใช้จริงทั้งปี (ลิตร)</td>';
                    
                    foreach ($cars as $car) {
                        $carId = $car['id'];
                        $totalUsed = $carTotals[$carId];
                        $totalUsedText = $totalUsed > 0 ? number_format($totalUsed, 2) : '-';
                        $content .= '<td class="text-right" style="border: 1px solid #3b82f6; padding: 10px;">' . $totalUsedText . '</td>';
                    }
                    
                    $content .= '
                                </tr>';
                    
                    $content .= '</tbody></table>';
                }
                break;

            case 3: // Province Travel Statistics Report by Fiscal Year
                $title = "รายงานจังหวัดจุดหมายยอดนิยมประจำปีงบประมาณ";
                $selectedYear = (int)$year;
                $startDate = ($selectedYear - 1) . "-10-01 00:00:00";
                $endDate = $selectedYear . "-09-30 23:59:59";

                $stmt = $db->prepare("
                    SELECT province_name, COUNT(*) AS count_trips
                    FROM car_booking_provinces p
                    LEFT JOIN car_booking b ON p.booking_id = b.id
                    WHERE b.status = 'Confirmed'
                      AND b.start_time >= :start_date
                      AND b.start_time <= :end_date
                    GROUP BY province_name
                    ORDER BY count_trips DESC
                ");
                $stmt->execute([
                    'start_date' => $startDate,
                    'end_date' => $endDate
                ]);
                $data = $stmt->fetchAll();

                $content = '
                    <h1>' . $title . '</h1>
                    <div class="subtitle">สถิติประจำปีงบประมาณ ' . $selectedYear . ' (ระหว่าง 1 ต.ค. ' . ($selectedYear - 1) . ' ถึง 30 ก.ย. ' . $selectedYear . ')</div>
                    <table class="meta-table">
                        <tr>
                            <td>พิมพ์โดย: ' . htmlspecialchars($printedBy) . '</td>
                            <td class="text-right">พิมพ์เมื่อ: ' . $printDate . '</td>
                        </tr>
                    </table>
                    <table class="report-table">
                        <thead>
                            <tr>
                                <th>จังหวัดปลายทาง</th>
                                <th class="text-right">จำนวนครั้งที่เดินทางไปปฏิบัติราชการ</th>
                            </tr>
                        </thead>
                        <tbody>';
                if (empty($data)) {
                    $content .= '
                        <tr>
                            <td colspan="2" class="text-center" style="color: #777; padding: 15px;">ไม่พบประวัติการเดินทางในรอบปีงบประมาณนี้</td>
                        </tr>';
                } else {
                    $totalTrips = 0;
                    foreach ($data as $row) {
                        $totalTrips += (int)$row['count_trips'];
                        $content .= '
                            <tr>
                                <td>' . htmlspecialchars($row['province_name']) . '</td>
                                <td class="text-right">' . $row['count_trips'] . ' ทริป</td>
                            </tr>';
                    }
                    $content .= '
                        <tr style="background: #f0f4ff; font-weight: bold; border-top: 2px solid #3b82f6;">
                            <td style="padding: 10px 12px;">รวมทั้งหมด</td>
                            <td class="text-right" style="padding: 10px 12px;">' . $totalTrips . ' ทริป</td>
                        </tr>';
                }
                $content .= '</tbody></table>';
                break;

            case 4: // Vehicle Suspension Report
                $title = "รายงานประวัติการระงับใช้งานยานพาหนะชั่วคราว";
                $stmt = $db->query("
                    SELECT s.*, c.license_plate, c.fuel_type, a.full_name AS admin_name
                    FROM car_suspension s
                    LEFT JOIN car_detail c ON s.car_id = c.id
                    LEFT JOIN admin_users a ON s.created_by = a.id
                    ORDER BY s.start_date DESC
                ");
                $data = $stmt->fetchAll();

                $content = '
                    <h1>' . $title . '</h1>
                    <div class="subtitle">บันทึกคำสั่งปิดซ่อมบำรุงและงดใช้รถยนต์หลวง</div>
                    <table class="meta-table">
                        <tr>
                            <td>พิมพ์โดย: ' . htmlspecialchars($printedBy) . '</td>
                            <td class="text-right">พิมพ์เมื่อ: ' . $printDate . '</td>
                        </tr>
                    </table>
                    <table class="report-table">
                        <thead>
                            <tr>
                                <th>ทะเบียนรถ</th>
                                <th>เริ่มระงับ</th>
                                <th>สิ้นสุดระงับ</th>
                                <th>สาเหตุการปิดปรับปรุง</th>
                                <th>ผู้สั่งคำสั่ง</th>
                            </tr>
                        </thead>
                        <tbody>';
                foreach ($data as $row) {
                    $content .= '
                        <tr>
                            <td>' . htmlspecialchars($row['license_plate']) . '</td>
                            <td>' . date('d/m/Y', strtotime($row['start_date'])) . '</td>
                            <td>' . date('d/m/Y', strtotime($row['end_date'])) . '</td>
                            <td>' . htmlspecialchars($row['reason']) . '</td>
                            <td>' . htmlspecialchars($row['admin_name']) . '</td>
                        </tr>';
                }
                $content .= '</tbody></table>';
                break;

            case 5: // Audit Log Report
                $title = "รายงานสรุปประวัติความปลอดภัยและการทำงานแอดมิน";
                $stmt = $db->query("
                    SELECT a.*, u.full_name AS admin_name
                    FROM audit_logs a
                    LEFT JOIN admin_users u ON a.user_id = u.id
                    ORDER BY a.timestamp DESC
                    LIMIT 100
                ");
                $data = $stmt->fetchAll();

                $content = '
                    <h1>' . $title . '</h1>
                    <div class="subtitle">ประวัติกิจกรรมการบันทึกฐานข้อมูล 100 รายการล่าสุด</div>
                    <table class="meta-table">
                        <tr>
                            <td>พิมพ์โดย: ' . htmlspecialchars($printedBy) . '</td>
                            <td class="text-right">พิมพ์เมื่อ: ' . $printDate . '</td>
                        </tr>
                    </table>
                    <table class="report-table" style="font-size: 10px;">
                        <thead>
                            <tr>
                                <th>วันและเวลา</th>
                                <th>แอดมิน</th>
                                <th>กิจกรรม</th>
                                <th>ตารางข้อมูล</th>
                                <th>ID อ้างอิง</th>
                            </tr>
                        </thead>
                        <tbody>';
                foreach ($data as $row) {
                    $content .= '
                        <tr>
                            <td>' . date('d/m/Y H:i:s', strtotime($row['timestamp'])) . '</td>
                            <td>' . htmlspecialchars($row['admin_name'] ?? 'ระบบ/บุคคลทั่วไป') . '</td>
                            <td>' . htmlspecialchars($row['action']) . '</td>
                            <td>' . htmlspecialchars($row['table_name'] ?? '-') . '</td>
                            <td>#' . ($row['record_id'] ?? '-') . '</td>
                        </tr>';
                }
                $content .= '</tbody></table>';
                break;

            case 6: // Monthly Receipt Report
                $title = "รายงานใบเสร็จค่าน้ำมันประจำเดือนจำแนกรายคัน";
                
                if (!$carId) {
                    $response->html("<script>alert('กรุณาเลือกทะเบียนรถยนต์หลวงที่จะเปิดรายงานใบเสร็จค่าน้ำมันประจำเดือนจำแนกรายคัน!'); history.back();</script>");
                    exit;
                }

                $car = $db->query("SELECT * FROM car_detail WHERE id = {$carId}")->fetch();
                $stmtReceipts = $db->query("
                    SELECT r.*, e.full_name AS employee_name, a.file_path
                    FROM gas_receipt r
                    LEFT JOIN employee e ON r.employee_id = e.id
                    LEFT JOIN receipt_attachment a ON a.receipt_id = r.id
                    WHERE r.car_id = {$carId} AND r.status != 'Cancelled' AND DATE_FORMAT(r.receipt_date, '%m-%Y') = '{$month}-{$year}'
                    ORDER BY r.receipt_date ASC
                ");
                $receipts = $stmtReceipts->fetchAll();

                // Page 1: Summary table
                $content = '
                    <h1>' . $title . '</h1>
                    <div class="subtitle">ประจำทะเบียนรถ ' . htmlspecialchars($car['license_plate']) . ' &bull; รอบเดือน ' . $month . '/' . $year . '</div>
                    <table class="meta-table">
                        <tr>
                            <td>พิมพ์โดย: ' . htmlspecialchars($printedBy) . '</td>
                            <td class="text-right">พิมพ์เมื่อ: ' . $printDate . '</td>
                        </tr>
                    </table>
                    
                    <h3 style="font-size: 13px; font-weight: bold; margin-top: 10px;">ส่วนที่ 1: ตารางสรุปรวมใบเสร็จค่าน้ำมัน</h3>
                    <table class="report-table">
                        <thead>
                            <tr>
                                <th>เลขที่ใบเสร็จ</th>
                                <th>วันที่เติม</th>
                                <th>ผู้เติม</th>
                                <th class="text-right">ปริมาณน้ำมัน</th>
                                <th class="text-right">ยอดเงินรวม</th>
                            </tr>
                        </thead>
                        <tbody>';
                
                if (empty($receipts)) {
                    $content .= '<tr><td colspan="5" class="text-center">ไม่พบใบเสร็จน้ำมันได้รับการบันทึกในรอบเดือนนี้</td></tr>';
                } else {
                    $sumLiters = 0;
                    $sumAmount = 0;
                    foreach ($receipts as $r) {
                        $sumLiters += $r['liters'];
                        $sumAmount += $r['amount'];
                        $content .= '
                            <tr>
                                <td style="font-weight: bold; color: #1e3a8a;">' . htmlspecialchars($r['receipt_number']) . '</td>
                                <td>' . date('d/m/Y', strtotime($r['receipt_date'])) . '</td>
                                <td>' . htmlspecialchars($r['employee_name']) . '</td>
                                <td class="text-right">' . number_format($r['liters'], 2) . ' L</td>
                                <td class="text-right">' . number_format($r['amount'], 2) . ' ฿</td>
                            </tr>';
                    }
                    $content .= '
                        <tr class="total-row">
                            <td colspan="3" class="text-right">รวมสุทธิ (' . count($receipts) . ' ใบเสร็จ):</td>
                            <td class="text-right">' . number_format($sumLiters, 2) . ' L</td>
                            <td class="text-right">' . number_format($sumAmount, 2) . ' ฿</td>
                        </tr>';
                }
                
                $content .= '</tbody></table>';

                // Following Pages: Renders each image attachment centered on its own page
                if (!empty($receipts)) {
                    foreach ($receipts as $r) {
                        if ($r['file_path']) {
                            // Path on disk: normalized slashes for cross-platform Windows/IIS compatibility
                            $diskPath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, dirname(__DIR__, 3) . '/public' . $r['file_path']);
                            $ext = strtolower(pathinfo($r['file_path'], PATHINFO_EXTENSION));
                            
                            if (file_exists($diskPath)) {
                                if ($ext === 'pdf') {
                                    $content .= '
                                        <pagebreak />
                                        <div style="padding: 20px; border: 1px solid #cbd5e1; border-radius: 8px; background-color: #f8fafc; min-height: 500px;">
                                            <div style="text-align: center; margin-bottom: 25px;">
                                                <h2 style="font-size: 15px; font-weight: bold; color: #1e293b; margin: 0 0 5px 0;">ใบรับรองการแนบเอกสารหลักฐานอิเล็กทรอนิกส์</h2>
                                                <div style="font-size: 10px; color: #64748b;">(Electronic Document Attachment Certificate / Fuel Voucher)</div>
                                            </div>
                                            
                                            <table style="width: 100%; font-size: 11px; border-collapse: collapse; margin-bottom: 30px;">
                                                <tr>
                                                    <td style="padding: 8px; font-weight: bold; width: 30%; border-bottom: 1px solid #e2e8f0; color: #475569;">เลขที่ใบเสร็จรับเงิน:</td>
                                                    <td style="padding: 8px; border-bottom: 1px solid #e2e8f0; font-weight: bold; color: #0f172a;">' . htmlspecialchars($r['receipt_number']) . '</td>
                                                </tr>
                                                <tr>
                                                    <td style="padding: 8px; font-weight: bold; border-bottom: 1px solid #e2e8f0; color: #475569;">วันที่ระบุในใบเสร็จ:</td>
                                                    <td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #334155;">' . date('d/m/Y', strtotime($r['receipt_date'])) . '</td>
                                                </tr>
                                                <tr>
                                                    <td style="padding: 8px; font-weight: bold; border-bottom: 1px solid #e2e8f0; color: #475569;">พนักงานผู้ยื่นเติมน้ำมัน:</td>
                                                    <td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #334155;">' . htmlspecialchars($r['employee_name']) . '</td>
                                                </tr>
                                                <tr>
                                                    <td style="padding: 8px; font-weight: bold; border-bottom: 1px solid #e2e8f0; color: #475569;">ยานพาหนะหลวง:</td>
                                                    <td style="padding: 8px; border-bottom: 1px solid #e2e8f0; font-weight: bold; color: #4f46e5;">' . htmlspecialchars($car['license_plate']) . '</td>
                                                </tr>
                                                <tr>
                                                    <td style="padding: 8px; font-weight: bold; border-bottom: 1px solid #e2e8f0; color: #475569;">ประเภทน้ำมัน / ปริมาณ:</td>
                                                    <td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #334155;">' . htmlspecialchars($r['fuel_type']) . ' / ' . number_format($r['liters'], 2) . ' ลิตร</td>
                                                </tr>
                                                <tr>
                                                    <td style="padding: 8px; font-weight: bold; border-bottom: 1px solid #e2e8f0; color: #475569;">ยอดค่าน้ำมันรวม:</td>
                                                    <td style="padding: 8px; border-bottom: 1px solid #e2e8f0; font-weight: bold; color: #0f172a;">' . number_format($r['amount'], 2) . ' ฿</td>
                                                </tr>
                                            </table>
                                            
                                            <div style="border: 2px dashed #cbd5e1; border-radius: 8px; padding: 20px; background-color: #ffffff; text-align: center;">
                                                <div style="font-size: 35px; margin-bottom: 10px; color: #ef4444;">📄</div>
                                                <h3 style="font-size: 13px; font-weight: bold; color: #0f172a; margin: 0 0 8px 0;">หลักฐานแนบเป็นรูปแบบเอกสาร PDF</h3>
                                                <p style="font-size: 10px; color: #475569; line-height: 1.5; margin: 0 auto 15px auto; width: 85%;">
                                                    เนื่องจากข้อมูลใบเสร็จได้รับการบันทึกในรูปแบบไฟล์เอกสาร PDF ระบบจึงได้แปลงและจัดทำใบรับรองใบสําคัญฉบับนี้เข้าเป็นส่วนหนึ่งของรายงานการตรวจสอบอย่างเป็นทางการ
                                                </p>
                                                <div style="font-weight: bold; font-size: 11px;">
                                                    <a href="' . htmlspecialchars(Request::getBasePath() . $r['file_path']) . '" target="_blank" style="text-decoration: underline; color: #2563eb;">คลิกลิงก์เพื่อเปิดดูหรือดาวน์โหลดไฟล์ PDF ต้นฉบับ</a>
                                                </div>
                                            </div>
                                        </div>
                                    ';
                                } else {
                                    $content .= '
                                        <pagebreak />
                                        <div style="text-align: center; padding-top: 15px;">
                                            <h2 style="font-size: 16px; font-weight: bold; margin-bottom: 5px; text-decoration: underline;">หลักฐานใบเสร็จเลขที่: ' . htmlspecialchars($r['receipt_number']) . '</h2>
                                            <p style="font-size: 11px; color: #555; margin-bottom: 20px;">วันที่ลงใบเสร็จ: ' . date('d/m/Y', strtotime($r['receipt_date'])) . ' &bull; ผู้เบิกจ่าย: ' . htmlspecialchars($r['employee_name']) . '</p>
                                            <div style="margin-top: 20px; border: 1px dashed #cbd5e1; padding: 15px; background-color: #fafafa; display: inline-block;">
                                                <img src="' . $diskPath . '" style="max-height: 540px; max-width: 100%; object-fit: contain;" />
                                            </div>
                                        </div>
                                    ';
                                }
                            }
                        }
                    }
                }
                break;

            case 7: // Yearly Fuel Usage Report
                $title = "รายงานการใช้น้ำมันรายปีงบประมาณ";
                $selectedFY = (int)$year; // The year filter is selected as the Fiscal Year

                // Define the 12 months of the fiscal year in order (Oct of FY-1 to Sep of FY)
                $fyMonths = [
                    ['year' => $selectedFY - 1, 'month' => '10', 'name' => 'ตุลาคม'],
                    ['year' => $selectedFY - 1, 'month' => '11', 'name' => 'พฤศจิกายน'],
                    ['year' => $selectedFY - 1, 'month' => '12', 'name' => 'ธันวาคม'],
                    ['year' => $selectedFY, 'month' => '01', 'name' => 'มกราคม'],
                    ['year' => $selectedFY, 'month' => '02', 'name' => 'กุมภาพันธ์'],
                    ['year' => $selectedFY, 'month' => '03', 'name' => 'มีนาคม'],
                    ['year' => $selectedFY, 'month' => '04', 'name' => 'เมษายน'],
                    ['year' => $selectedFY, 'month' => '05', 'name' => 'พฤษภาคม'],
                    ['year' => $selectedFY, 'month' => '06', 'name' => 'มิถุนายน'],
                    ['year' => $selectedFY, 'month' => '07', 'name' => 'กรกฎาคม'],
                    ['year' => $selectedFY, 'month' => '08', 'name' => 'สิงหาคม'],
                    ['year' => $selectedFY, 'month' => '09', 'name' => 'กันยายน'],
                ];

                // Query active cars
                $cars = $db->query("SELECT * FROM car_detail WHERE status = 'Active' ORDER BY license_plate ASC")->fetchAll();

                $content = '
                    <h1>' . $title . '</h1>
                    <div class="subtitle">ประจำปีงบประมาณ ' . $selectedFY . ' (1 ต.ค. ' . ($selectedFY - 1) . ' - 30 ก.ย. ' . $selectedFY . ')</div>
                    <table class="meta-table">
                        <tr>
                            <td>พิมพ์โดย: ' . htmlspecialchars($printedBy) . '</td>
                            <td class="text-right">พิมพ์เมื่อ: ' . $printDate . '</td>
                        </tr>
                    </table>';

                if (empty($cars)) {
                    $content .= '<p class="text-center">ไม่พบข้อมูลรถยนต์หลวงในระบบ</p>';
                } else {
                    $content .= '
                        <table class="report-table">
                            <thead>
                                <tr>
                                    <th>ทะเบียนรถ</th>
                                    <th>ประเภทน้ำมัน</th>
                                    <th class="text-right">โควต้าน้ำมัน (ลิตร)</th>
                                    <th class="text-center">เดือน</th>
                                    <th class="text-right">ใช้ไป (ลิตร)</th>
                                    <th class="text-right">ค่าน้ำมัน (บาท)</th>
                                    <th class="text-right">คงเหลือ (ลิตร)</th>
                                </tr>
                            </thead>
                            <tbody>';

                    foreach ($cars as $car) {
                        $carId = $car['id'];
                        $firstRow = true;
                        
                        $monthlyData = [];
                        $totalQuota = 0;
                        $totalLiters = 0;
                        $totalAmount = 0;

                        foreach ($fyMonths as $mInfo) {
                            $mY = $mInfo['year'];
                            $mStr = $mInfo['month'];
                            $effectiveMonth = "{$mY}-{$mStr}-01";
                            $monthYear = "{$mStr}-{$mY}";

                            // Fetch quota for this month (latest quota effective on or before this month)
                            $stmtQ = $db->prepare("
                                SELECT monthly_quota 
                                FROM car_quota_history 
                                WHERE car_id = ? AND effective_month <= ? 
                                ORDER BY effective_month DESC 
                                LIMIT 1
                            ");
                            $stmtQ->execute([$carId, $effectiveMonth]);
                            $quotaVal = (float)($stmtQ->fetchColumn() ?: 0);

                            // Fetch verified consumption
                            $stmtC = $db->prepare("
                                SELECT SUM(liters) AS total_liters, SUM(amount) AS total_amount 
                                FROM gas_receipt 
                                WHERE car_id = ? AND status = 'Verified' AND DATE_FORMAT(receipt_date, '%m-%Y') = ?
                            ");
                            $stmtC->execute([$carId, $monthYear]);
                            $usage = $stmtC->fetch();
                            $litersVal = (float)($usage['total_liters'] ?: 0);
                            $amountVal = (float)($usage['total_amount'] ?: 0);

                            if ($quotaVal > 0 || $litersVal > 0) {
                                $monthlyData[] = [
                                    'name' => $mInfo['name'],
                                    'quota' => $quotaVal,
                                    'used' => $litersVal,
                                    'amount' => $amountVal,
                                    'remaining' => $quotaVal - $litersVal
                                ];
                                $totalQuota += $quotaVal;
                                $totalLiters += $litersVal;
                                $totalAmount += $amountVal;
                            }
                        }

                        if (empty($monthlyData)) {
                            $monthlyData[] = [
                                'name' => 'ไม่มีการใช้งานในรอบปี',
                                'quota' => 0,
                                'used' => 0,
                                'amount' => 0,
                                'remaining' => 0
                            ];
                        }

                        $rowCount = count($monthlyData);
                        foreach ($monthlyData as $row) {
                            $content .= '<tr>';
                            if ($firstRow) {
                                $content .= '
                                    <td rowspan="' . ($rowCount + 1) . '" style="vertical-align: top; font-weight: bold; background-color: #f8fafc;">' . htmlspecialchars($car['license_plate']) . '</td>
                                    <td rowspan="' . ($rowCount + 1) . '" style="vertical-align: top; font-weight: bold; background-color: #f8fafc;">' . htmlspecialchars($car['fuel_type']) . '</td>';
                                $firstRow = false;
                            }

                            $remainingText = number_format($row['remaining'], 2) . ' L';
                            if ($row['remaining'] < 0) {
                                $remainingText = '<span style="color: #ef4444; font-weight: bold;">' . number_format($row['remaining'], 2) . ' L</span>';
                            }

                            $content .= '
                                <td class="text-right">' . ($row['quota'] > 0 ? number_format($row['quota'], 2) . ' L' : '-') . '</td>
                                <td class="text-center" style="color: #555;">' . $row['name'] . '</td>
                                <td class="text-right">' . ($row['used'] > 0 ? number_format($row['used'], 2) . ' L' : '-') . '</td>
                                <td class="text-right">' . ($row['amount'] > 0 ? number_format($row['amount'], 2) . ' ฿' : '-') . '</td>
                                <td class="text-right">' . ($row['quota'] > 0 || $row['used'] > 0 ? $remainingText : '-') . '</td>
                            </tr>';
                        }

                        // Yearly total row for this vehicle
                        $totalRemaining = $totalQuota - $totalLiters;
                        $totalRemainingText = number_format($totalRemaining, 2) . ' L';
                        if ($totalRemaining < 0) {
                            $totalRemainingText = '<span style="color: #ef4444; font-weight: bold;">' . number_format($totalRemaining, 2) . ' L</span>';
                        }

                        $content .= '
                            <tr style="background-color: #f8fafc; font-weight: bold; border-bottom: 2px solid #64748b;">
                                <td class="text-right" style="color: #4f46e5;">' . number_format($totalQuota, 2) . ' L</td>
                                <td class="text-center" style="color: #4f46e5;">รวมทั้งปี</td>
                                <td class="text-right" style="color: #4f46e5;">' . number_format($totalLiters, 2) . ' L</td>
                                <td class="text-right" style="color: #4f46e5;">' . number_format($totalAmount, 2) . ' ฿</td>
                                <td class="text-right" style="color: #4f46e5;">' . $totalRemainingText . '</td>
                            </tr>';
                    }

                    $content .= '</tbody></table>';
                }
                break;

            case 8: // Central Vehicle Booking Statistics Report
                $title = "รายงานสถิติผู้จองใช้งานรถยนต์ส่วนกลาง";
                
                if (!$startDate || !$endDate) {
                    $_SESSION['report_error'] = 'กรุณาระบุช่วงวันที่ (ตั้งแต่วันที่ และ ถึงวันที่) สำหรับรายงานสถิติ';
                    $response->redirect('/admin/reports');
                    exit;
                }

                if (strtotime($startDate) > strtotime($endDate)) {
                    $_SESSION['report_error'] = 'วันที่เริ่มต้น ห้ามอยู่หลังวันที่สิ้นสุด';
                    $response->redirect('/admin/reports');
                    exit;
                }

                $stmt = $db->prepare("
                    SELECT 
                        e.full_name AS employee_name,
                        d.name AS department_name,
                        divi.name AS division_name,
                        COUNT(b.id) AS booking_count
                    FROM car_booking b
                    INNER JOIN employee e ON b.employee_id = e.id
                    LEFT JOIN department d ON e.department_id = d.id
                    LEFT JOIN division divi ON e.division_id = divi.id
                    WHERE b.status = 'Confirmed'
                      AND b.booking_date >= :start_date
                      AND b.booking_date <= :end_date
                    GROUP BY e.id, e.full_name, d.name, divi.name
                    ORDER BY booking_count DESC
                ");
                $stmt->execute([
                    'start_date' => $startDate,
                    'end_date' => $endDate
                ]);
                $statistics = $stmt->fetchAll();

                $content = '
                    <h1>' . $title . '</h1>
                    <div class="subtitle">ตั้งแต่วันที่ ' . date('d/m/Y', strtotime($startDate)) . ' ถึงวันที่ ' . date('d/m/Y', strtotime($endDate)) . '</div>
                    <table class="meta-table">
                        <tr>
                            <td>พิมพ์โดย: ' . htmlspecialchars($printedBy) . '</td>
                            <td class="text-right">พิมพ์เมื่อ: ' . $printDate . '</td>
                        </tr>
                    </table>';

                if (empty($statistics)) {
                    $content .= '<p class="text-center" style="padding: 40px 0; color: #777;">ไม่พบข้อมูลสถิติการจองใช้งานยานพาหนะในช่วงเวลาดังกล่าว</p>';
                } else {
                    $content .= '
                        <table class="report-table">
                            <thead>
                                <tr>
                                    <th style="width: 8%; text-align: center;">ลำดับ</th>
                                    <th>รายชื่อพนักงาน</th>
                                    <th>แผนก (ฝ่าย)</th>
                                    <th>กอง / สำนัก</th>
                                    <th style="width: 25%; text-align: center;">จำนวนครั้งที่จองรถยนต์</th>
                                </tr>
                            </thead>
                            <tbody>';

                    $rank = 1;
                    $totalBookings = 0;
                    foreach ($statistics as $row) {
                        $content .= '
                            <tr>
                                <td class="text-center">' . $rank++ . '</td>
                                <td style="font-weight: bold;">' . htmlspecialchars($row['employee_name']) . '</td>
                                <td>' . htmlspecialchars($row['department_name'] ?? 'ไม่ระบุ') . '</td>
                                <td>' . htmlspecialchars($row['division_name'] ?? 'ไม่ระบุ') . '</td>
                                <td class="text-center" style="font-weight: bold; color: #4f46e5;">' . number_format($row['booking_count']) . ' ครั้ง</td>
                            </tr>';
                        $totalBookings += (int)$row['booking_count'];
                    }

                    $content .= '
                            <tr class="total-row">
                                <td colspan="4" class="text-right" style="font-weight: bold;">รวมครั้งการจองทั้งหมดในช่วงเวลา</td>
                                <td class="text-center" style="font-weight: bold; color: #4f46e5; font-size: 13px;">' . number_format($totalBookings) . ' ครั้ง</td>
                            </tr>
                            </tbody>
                        </table>';
                }
                break;

            case 9: // Vehicle Booking Cancellation Report
                $title = "รายงานการยกเลิกการจองใช้งานรถ";
                
                if (!$startDate || !$endDate) {
                    $_SESSION['report_error'] = 'กรุณาระบุช่วงวันที่ (ตั้งแต่วันที่ และ ถึงวันที่) สำหรับรายงานการยกเลิกการจองใช้งานรถ';
                    $response->redirect('/admin/reports');
                    exit;
                }

                if (strtotime($startDate) > strtotime($endDate)) {
                    $_SESSION['report_error'] = 'วันที่เริ่มต้น ห้ามอยู่หลังวันที่สิ้นสุด';
                    $response->redirect('/admin/reports');
                    exit;
                }

                // Query cancellations
                $stmt = $db->prepare("
                    SELECT 
                        cl.cancelled_at,
                        b.id AS booking_id,
                        b.booking_date,
                        b.start_time,
                        b.end_time,
                        b.purpose,
                        b.cancel_reason,
                        c.license_plate,
                        c.color AS car_color,
                        e.full_name AS employee_name,
                        d.name AS department_name,
                        divi.name AS division_name,
                        al.username AS admin_username,
                        au.full_name AS admin_fullname
                    FROM booking_cancel_log cl
                    INNER JOIN car_booking b ON cl.booking_id = b.id
                    LEFT JOIN car_detail c ON b.car_id = c.id
                    LEFT JOIN employee e ON b.employee_id = e.id
                    LEFT JOIN department d ON e.department_id = d.id
                    LEFT JOIN division divi ON e.division_id = divi.id
                    LEFT JOIN audit_logs al ON (al.action = 'Cancel booking' AND al.table_name = 'car_booking' AND al.record_id = b.id)
                    LEFT JOIN admin_users au ON al.user_id = au.id
                    WHERE cl.cancelled_at >= :start_date AND cl.cancelled_at <= :end_date
                    ORDER BY cl.cancelled_at DESC
                ");
                $stmt->execute([
                    'start_date' => $startDate . ' 00:00:00',
                    'end_date' => $endDate . ' 23:59:59'
                ]);
                $cancellations = $stmt->fetchAll();

                $content = '
                    <h1>' . $title . '</h1>
                    <div class="subtitle">ตั้งแต่วันที่ ' . date('d/m/Y', strtotime($startDate)) . ' ถึงวันที่ ' . date('d/m/Y', strtotime($endDate)) . '</div>
                    <table class="meta-table">
                        <tr>
                            <td>พิมพ์โดย: ' . htmlspecialchars($printedBy) . '</td>
                            <td class="text-right">พิมพ์เมื่อ: ' . $printDate . '</td>
                        </tr>
                    </table>';

                if (empty($cancellations)) {
                    $content .= '<p class="text-center" style="padding: 40px 0; color: #777;">ไม่พบข้อมูลประวัติการยกเลิกการจองรถในช่วงเวลาดังกล่าว</p>';
                } else {
                    $content .= '
                        <table class="report-table">
                            <thead>
                                <tr>
                                    <th style="width: 5%; text-align: center;">ลำดับ</th>
                                    <th style="width: 10%; text-align: center;">ทะเบียนรถ</th>
                                    <th style="width: 15%;">ผู้จอง / สังกัด</th>
                                    <th style="width: 15%;">วัตถุประสงค์</th>
                                    <th style="width: 15%; text-align: center;">ช่วงเวลาจองเดินทาง</th>
                                    <th style="width: 13%; text-align: center;">วันที่ยกเลิก</th>
                                    <th style="width: 13%;">ผู้ยกเลิก</th>
                                    <th style="width: 14%;">เหตุผลการยกเลิก</th>
                                </tr>
                            </thead>
                            <tbody>';

                    $rank = 1;
                    foreach ($cancellations as $row) {
                        $deptInfo = [];
                        if ($row['department_name']) {
                            $deptInfo[] = $row['department_name'];
                        }
                        if ($row['division_name']) {
                            $deptInfo[] = $row['division_name'];
                        }
                        $deptText = !empty($deptInfo) ? ' (' . implode(', ', $deptInfo) . ')' : '';

                        if ($row['admin_username']) {
                            $cancellerName = $row['admin_fullname'] ? $row['admin_fullname'] : $row['admin_username'];
                            $canceller = '<span style="color: #ef4444; font-weight: bold;">ผู้ดูแลระบบ:</span> ' . htmlspecialchars($cancellerName);
                        } else {
                            $canceller = '<span style="color: #4f46e5;">ผู้ใช้งาน:</span> ' . htmlspecialchars($row['employee_name']);
                        }

                        $reason = trim($row['cancel_reason'] ?? '');
                        if ($reason === '') {
                            $displayReason = !$row['admin_username'] ? 'ผู้ใช้งานขอยกเลิกเอง' : '-';
                        } else {
                            $displayReason = $reason;
                        }

                        $content .= '
                            <tr>
                                <td class="text-center">' . $rank++ . '</td>
                                <td class="text-center" style="font-weight: bold;">' . htmlspecialchars($row['license_plate']) . '</td>
                                <td>' . htmlspecialchars($row['employee_name']) . $deptText . '</td>
                                <td>' . htmlspecialchars($row['purpose']) . '</td>
                                <td class="text-center" style="font-size: 10px;">' . date('d/m/Y H:i', strtotime($row['start_time'])) . '<br>ถึง ' . date('d/m/Y H:i', strtotime($row['end_time'])) . '</td>
                                <td class="text-center" style="font-size: 10px;">' . date('d/m/Y H:i', strtotime($row['cancelled_at'])) . '</td>
                                <td style="font-size: 11px;">' . $canceller . '</td>
                                <td style="font-size: 11px; color: #475569;">' . htmlspecialchars($displayReason) . '</td>
                            </tr>';
                    }

                    $content .= '
                            </tbody>
                        </table>';
                }
                break;

            case 10: // Monthly Vehicle Booking Report
                $title = "รายงานการจองรถยนต์ประจำเดือน";
                
                // Set Thai months
                $thaiFullMonths = ['', 'มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน', 'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'];
                $thaiMonthName = $thaiFullMonths[(int)$month] ?? $month;
                $thaiYear = (int)$year + 543;

                // Validate parameters
                $carIdParam = $body['car_id'] ?? 'all';
                $isAllVehicles = ($carIdParam === 'all' || empty($carIdParam));

                $carInfoText = "";
                if (!$isAllVehicles) {
                    $carId = (int)$carIdParam;
                    $carStmt = $db->prepare("SELECT * FROM car_detail WHERE id = :id");
                    $carStmt->execute(['id' => $carId]);
                    $carDetail = $carStmt->fetch();
                    if (!$carDetail) {
                        $_SESSION['report_error'] = 'ไม่พบข้อมูลรถยนต์ที่เลือก';
                        $response->redirect('/admin/reports');
                        if (!defined('PHPUNIT_COMPOSER_INSTALL') && !defined('__PHPUNIT_PHAR__')) {
                            exit;
                        }
                        return;
                    }
                    $carInfoText = "ประจำทะเบียนรถ " . htmlspecialchars($carDetail['license_plate']);
                } else {
                    $carInfoText = "รถยนต์หลวงทุกคัน";
                }

                // Query bookings
                $sql = "
                    SELECT 
                        b.id AS booking_id,
                        b.booking_date,
                        b.start_time,
                        b.end_time,
                        b.purpose,
                        b.created_at,
                        b.status,
                        c.license_plate,
                        c.color AS car_color,
                        e.full_name AS employee_name,
                        d.name AS department_name,
                        divi.name AS division_name
                    FROM car_booking b
                    INNER JOIN car_detail c ON b.car_id = c.id
                    LEFT JOIN employee e ON b.employee_id = e.id
                    LEFT JOIN department d ON e.department_id = d.id
                    LEFT JOIN division divi ON e.division_id = divi.id
                    WHERE MONTH(b.start_time) = :month 
                      AND YEAR(b.start_time) = :year
                      AND b.status != 'Cancelled'
                ";
                
                if (!$isAllVehicles) {
                    $sql .= " AND b.car_id = :car_id";
                }
                
                $sql .= " ORDER BY b.created_at ASC";
                
                $stmt = $db->prepare($sql);
                $params = [
                    'month' => $month,
                    'year' => $year
                ];
                if (!$isAllVehicles) {
                    $params['car_id'] = $carId;
                }
                $stmt->execute($params);
                $bookings = $stmt->fetchAll();

                // Fetch provinces for each booking
                foreach ($bookings as &$b) {
                    $pStmt = $db->prepare("SELECT province_name FROM car_booking_provinces WHERE booking_id = :booking_id ORDER BY province_name ASC");
                    $pStmt->execute(['booking_id' => $b['booking_id']]);
                    $b['provinces'] = $pStmt->fetchAll(\PDO::FETCH_COLUMN);
                }

                $content = '
                    <h1>' . $title . '</h1>
                    <div class="subtitle">' . $carInfoText . ' &bull; ประจำเดือน ' . $thaiMonthName . ' พ.ศ. ' . $thaiYear . '</div>
                    <table class="meta-table">
                        <tr>
                            <td>พิมพ์โดย: ' . htmlspecialchars($printedBy) . '</td>
                            <td class="text-right">พิมพ์เมื่อ: ' . $printDate . '</td>
                        </tr>
                    </table>';

                if (empty($bookings)) {
                    $content .= '<p class="text-center" style="padding: 40px 0; color: #777;">ไม่พบข้อมูลประวัติการจองใช้งานรถยนต์ในช่วงเวลาดังกล่าว</p>';
                } else {
                    $content .= '
                        <table class="report-table">
                            <thead>
                                <tr>
                                    <th style="width: 5%; text-align: center;">ลำดับ</th>
                                    <th style="width: 15%; text-align: center;">วันที่จอง</th>
                                    <th style="width: 12%; text-align: center;">ทะเบียนรถ</th>
                                    <th style="width: 18%;">ชื่อผู้จองรถ / สังกัด</th>
                                    <th style="width: 18%; text-align: center;">ช่วงวันที่จองรถ</th>
                                    <th style="width: 14%; text-align: center;">จังหวัดปลายทาง</th>
                                    <th style="width: 18%;">วัตถุประสงค์เดินทาง</th>
                                </tr>
                            </thead>
                            <tbody>';

                    $rank = 1;
                    foreach ($bookings as $row) {
                        $deptInfo = [];
                        if ($row['department_name']) {
                            $deptInfo[] = $row['department_name'];
                        }
                        if ($row['division_name']) {
                            $deptInfo[] = $row['division_name'];
                        }
                        $deptText = !empty($deptInfo) ? ' (' . implode(', ', $deptInfo) . ')' : '';
                        
                        $provinceText = !empty($row['provinces']) ? implode(', ', $row['provinces']) : '-';

                        // Display formatted booking creation date/time
                        $createdDateText = date('d/m/Y H:i', strtotime($row['created_at'])) . ' น.';

                        // Display formatted travel period
                        $travelPeriod = date('d/m/Y H:i', strtotime($row['start_time'])) . '<br>ถึง ' . date('d/m/Y H:i', strtotime($row['end_time']));

                        $content .= '
                            <tr>
                                <td class="text-center">' . $rank++ . '</td>
                                <td class="text-center" style="font-size: 11px;">' . htmlspecialchars($createdDateText) . '</td>
                                <td class="text-center" style="font-weight: bold;">' . htmlspecialchars($row['license_plate']) . '</td>
                                <td style="font-size: 11px;">' . htmlspecialchars($row['employee_name']) . '<br><span style="color: #64748b; font-size: 10px;">' . htmlspecialchars($deptText) . '</span></td>
                                <td class="text-center" style="font-size: 10px;">' . $travelPeriod . '</td>
                                <td class="text-center" style="font-size: 11px;">' . htmlspecialchars($provinceText) . '</td>
                                <td style="font-size: 11px;">' . htmlspecialchars($row['purpose']) . '</td>
                            </tr>';
                    }

                    $content .= '
                            </tbody>
                        </table>';
                }
                break;
        }

        // Fetch PDF footer text from database if available (with backward-compatible fallback)
        $pdfFooter = 'รายงานนี้สร้างและพิมพ์โดยระบบควบคุมโควต้าน้ำมันยานพาหนะอัตโนมัติ <strong>FuelFleet™</strong><br>พิมพ์ใบเสร็จและภาพแนบย้อนหลังถูกต้องตามข้อบังคับระเบียบราชการองค์กร';
        try {
            $stmtSetting = $db->query("SELECT setting_value FROM system_settings WHERE setting_key = 'pdf_report_footer' LIMIT 1");
            if ($stmtSetting) {
                $dbFooter = $stmtSetting->fetchColumn();
                if ($dbFooter !== false && $dbFooter !== null) {
                    $pdfFooter = $dbFooter;
                }
            }
        } catch (\PDOException $e) {
            // Fallback to default
        }

        // Add standard FuelFleet report footer
        $content .= '
            <div class="footer">
                ' . $pdfFooter . '
            </div>
        ';

        // Write HTML and output PDF to browser inline
        $mpdf->WriteHTML($htmlStyles . $content);
        $mpdf->Output($title . '.pdf', 'I');
        exit;
    }
}
