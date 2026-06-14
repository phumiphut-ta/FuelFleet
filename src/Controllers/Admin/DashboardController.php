<?php
namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Response;
use App\Core\Database;
use App\Core\Router;
use App\Core\AuthMiddleware;
use App\Services\QuotaService;
use PDO;

class DashboardController {
    protected QuotaService $quotaService;

    public function __construct() {
        AuthMiddleware::checkAdmin();
        $this->quotaService = new QuotaService();
    }

    public function index(Request $request, Response $response) {
        $db = Database::getConnection();

        // 1. Total Vehicles
        $totalVehicles = (int)$db->query("SELECT COUNT(*) FROM car_detail")->fetchColumn();

        // 2. Available Vehicles (Active)
        $availableVehicles = (int)$db->query("SELECT COUNT(*) FROM car_detail WHERE status = 'Active'")->fetchColumn();

        // 3. Suspended Vehicles
        $suspendedVehicles = (int)$db->query("SELECT COUNT(*) FROM car_detail WHERE status = 'Suspended'")->fetchColumn();

        // 4. Today's Bookings
        $todayBookings = (int)$db->query("
            SELECT COUNT(*) FROM car_booking 
            WHERE status = 'Confirmed' AND DATE(start_time) <= CURDATE() AND DATE(end_time) >= CURDATE()
        ")->fetchColumn();

        // 5. Monthly Fuel Usage (Liters sum of verified receipts this month)
        $thisMonth = date('Y-m');
        $monthlyFuelLiters = (float)$db->query("
            SELECT SUM(liters) FROM gas_receipt 
            WHERE status = 'Verified' AND DATE_FORMAT(receipt_date, '%Y-%m') = '{$thisMonth}'
        ")->fetchColumn();

        // 6. Over-quota Vehicles
        $overQuotaList = $this->quotaService->getOverQuotaCars(date('Y-m'));
        $overQuotaCount = count($overQuotaList);

        // 7. Pending receipts
        $pendingReceipts = (int)$db->query("SELECT COUNT(*) FROM gas_receipt WHERE status = 'Pending verification'")->fetchColumn();

        // ==========================================
        // FISCAL YEAR BOUNDARIES
        // ==========================================
        $fiscalStart = (date('m') >= 10) ? date('Y') . '-10-01' : (date('Y') - 1) . '-10-01';
        $fiscalEnd   = (date('m') >= 10) ? (date('Y') + 1) . '-09-30' : date('Y') . '-09-30';

        // ==========================================
        // CHARTS DATA PREPARATION
        // ==========================================

        // Chart 1: Monthly fuel liters for the last 12 months (11 months ago to current month), split by car
        $months = [];
        $monthLabels = [];
        for ($i = 11; $i >= 0; $i--) {
            $ts = strtotime("-$i months");
            $months[] = date('Y-m', $ts);
            
            $thaiShortMonths = ['', 'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
            $mIndex = (int)date('n', $ts);
            $yearThaiShort = substr((string)(date('Y', $ts) + 543), -2);
            $monthLabels[] = $thaiShortMonths[$mIndex] . ' ' . $yearThaiShort;
        }

        // Fetch all cars (active or suspended)
        $carsForChart = $db->query("SELECT id, license_plate, color FROM car_detail ORDER BY license_plate ASC")->fetchAll();
        
        $startDate = date('Y-m-01', strtotime("-11 months"));
        $endDate = date('Y-m-t'); // End of current month
        
        $usageData = $db->query("
            SELECT car_id, DATE_FORMAT(receipt_date, '%Y-%m') AS ym, SUM(liters) AS total_liters
            FROM gas_receipt
            WHERE status = 'Verified' AND receipt_date BETWEEN '{$startDate}' AND '{$endDate}'
            GROUP BY car_id, DATE_FORMAT(receipt_date, '%Y-%m')
        ")->fetchAll();

        $usageMap = [];
        foreach ($usageData as $row) {
            $usageMap[$row['car_id']][$row['ym']] = (float)$row['total_liters'];
        }

        $chartDatasets = [];
        foreach ($carsForChart as $car) {
            $carId = (int)$car['id'];
            $color = !empty($car['color']) ? $car['color'] : '#6366f1';
            
            $datasetData = [];
            foreach ($months as $month) {
                $datasetData[] = $usageMap[$carId][$month] ?? 0.0;
            }
            
            $chartDatasets[] = [
                'label' => $car['license_plate'],
                'data' => $datasetData,
                'borderColor' => $color,
                'backgroundColor' => $color . '15',
                'fill' => false,
                'tension' => 0.4,
                'borderWidth' => 2
            ];
        }

        // Chart 2: Province travel frequencies — Top 5 + อื่นๆ
        $provinceRaw = $db->query("
            SELECT province_name, COUNT(*) AS travel_count
            FROM car_booking_provinces p
            LEFT JOIN car_booking b ON p.booking_id = b.id
            WHERE b.status = 'Confirmed'
            GROUP BY province_name
            ORDER BY travel_count DESC
        ")->fetchAll();

        $top5     = array_slice($provinceRaw, 0, 5);
        $others   = array_slice($provinceRaw, 5);
        $othersCount = array_sum(array_column($others, 'travel_count'));
        if ($othersCount > 0) {
            $top5[] = ['province_name' => 'อื่นๆ', 'travel_count' => $othersCount];
        }
        $provinceTravelStats = $top5;

        // ==========================================
        // NEW WIDGETS DATA
        // ==========================================

        // Widget A: Remaining fuel quota per car (current month)
        // car_quota_history stores monthly_quota — compare against current month's usage
        $currentMonthStart = date('Y-m-01');

        $currentMonthEnd   = date('Y-m-t');
        $quotaRemaining = $db->query("
            SELECT
                c.license_plate,
                c.fuel_type,
                COALESCE(q.monthly_quota, 0)                                         AS quota_liters,
                COALESCE(SUM(r.liters), 0)                                           AS used_liters,
                COALESCE(q.monthly_quota, 0) - COALESCE(SUM(r.liters), 0)           AS remaining_liters
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
            GROUP BY c.id, c.license_plate, c.fuel_type, q.monthly_quota
            ORDER BY remaining_liters ASC
        ")->fetchAll();



        // Widget B: Latest 5 cancelled bookings
        $cancelledBookings = $db->query("
            SELECT
                b.id,
                e.full_name     AS booker_name,
                c.license_plate,
                b.start_time    AS booking_date,
                b.updated_at    AS cancelled_at
            FROM car_booking b
            LEFT JOIN employee e ON b.employee_id = e.id
            LEFT JOIN car_detail c ON b.car_id = c.id
            WHERE b.status = 'Cancelled'
            ORDER BY b.updated_at DESC
            LIMIT 5
        ")->fetchAll();

        // Widget C: Top 5 bookers in current fiscal year
        $topBookers = $db->query("
            SELECT
                e.full_name,
                COUNT(b.id) AS booking_count
            FROM car_booking b
            LEFT JOIN employee e ON b.employee_id = e.id
            WHERE b.status IN ('Confirmed', 'Completed')
              AND b.start_time BETWEEN '{$fiscalStart}' AND '{$fiscalEnd} 23:59:59'
            GROUP BY b.employee_id, e.full_name
            ORDER BY booking_count DESC
            LIMIT 5
        ")->fetchAll();

        $router = new Router($request, $response);
        return $router->renderView('admin/dashboard/index', [
            'totalVehicles'       => $totalVehicles,
            'availableVehicles'   => $availableVehicles,
            'suspendedVehicles'   => $suspendedVehicles,
            'todayBookings'       => $todayBookings,
            'monthlyFuelLiters'   => $monthlyFuelLiters,
            'overQuotaCount'      => $overQuotaCount,
            'pendingReceipts'     => $pendingReceipts,
            'overQuotaList'       => $overQuotaList,
            'chartMonthLabels'     => $monthLabels,
            'chartDatasets'        => $chartDatasets,
            'provinceTravelStats' => $provinceTravelStats,
            'quotaRemaining'      => $quotaRemaining,
            'cancelledBookings'   => $cancelledBookings,
            'topBookers'          => $topBookers,
        ]);
    }
}
