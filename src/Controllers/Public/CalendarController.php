<?php
namespace App\Controllers\Public;

use App\Core\Request;
use App\Core\Response;
use App\Core\Database;
use App\Core\Router;
use App\Repositories\MySQL\BookingRepository;
use PDO;

class CalendarController {
    protected BookingRepository $bookingRepo;

    public function __construct() {
        $this->bookingRepo = new BookingRepository();
    }

    public function index(Request $request, Response $response) {
        $success = $_SESSION['booking_success'] ?? null;
        $error = $_SESSION['booking_error'] ?? null;
        unset($_SESSION['booking_success'], $_SESSION['booking_error']);

        $db = Database::getConnection();
        $cars = $db->query("SELECT license_plate, color, status FROM car_detail ORDER BY license_plate ASC")->fetchAll();

        $router = new Router($request, $response);
        return $router->renderView('public/calendar', [
            'success' => $success,
            'error' => $error,
            'cars' => $cars
        ]);
    }

    public function getEvents(Request $request, Response $response) {
        $events = $this->bookingRepo->getCalendarEvents();
        return $response->json($events);
    }

    public function heatmap(Request $request, Response $response) {
        $db = Database::getConnection();

        // 1. Determine min and max booking date to build fiscal years list
        $minMax = $db->query("SELECT MIN(start_time) AS min_date, MAX(start_time) AS max_date FROM car_booking")->fetch();
        $currentYear = (int)date('Y');
        $currentMonth = (int)date('m');
        $latestFiscalYear = $currentMonth >= 10 ? $currentYear + 1 : $currentYear;

        $minYear = $minMax && $minMax['min_date'] ? (int)date('Y', strtotime($minMax['min_date'])) : $currentYear;
        
        // Build list of fiscal years (include at least the last few years and the current one)
        $fiscalYears = [];
        for ($y = $latestFiscalYear; $y >= $minYear - 1; $y--) {
            if ($y >= 2020) { // Keep it sensible
                $fiscalYears[] = $y;
            }
        }
        if (empty($fiscalYears)) {
            $fiscalYears[] = $latestFiscalYear;
        }
        $fiscalYears = array_unique($fiscalYears);
        sort($fiscalYears);
        $fiscalYears = array_reverse($fiscalYears);

        // 2. Get selected fiscal year from query param
        $queryParams = $request->getBody();
        $selectedFY = isset($queryParams['fy']) ? (int)$queryParams['fy'] : $latestFiscalYear;

        // Calculate date range for the selected fiscal year (Oct 1 of FY-1 to Sep 30 of FY)
        $startDate = ($selectedFY - 1) . "-10-01 00:00:00";
        $endDate = $selectedFY . "-09-30 23:59:59";

        // 3. Query stats for provinces
        $stmtProvinces = $db->prepare("
            SELECT province_name, COUNT(*) AS travel_count
            FROM car_booking_provinces p
            LEFT JOIN car_booking b ON p.booking_id = b.id
            WHERE b.status = 'Confirmed' 
              AND b.start_time >= :start_date 
              AND b.start_time <= :end_date
            GROUP BY province_name
            ORDER BY travel_count DESC
        ");
        $stmtProvinces->execute([
            'start_date' => $startDate,
            'end_date' => $endDate
        ]);
        $stats = $stmtProvinces->fetchAll();

        // 4. Query stats for top bookers
        $stmtBookers = $db->prepare("
            SELECT e.full_name AS employee_name, COUNT(b.id) AS booking_count
            FROM car_booking b
            LEFT JOIN employee e ON b.employee_id = e.id
            WHERE b.status = 'Confirmed'
              AND b.start_time >= :start_date
              AND b.start_time <= :end_date
            GROUP BY b.employee_id, e.full_name
            ORDER BY booking_count DESC
        ");
        $stmtBookers->execute([
            'start_date' => $startDate,
            'end_date' => $endDate
        ]);
        $bookersRaw = $stmtBookers->fetchAll();

        // Process top 5 bookers and group the rest into "อื่นๆ"
        $bookers = [];
        $othersCount = 0;
        foreach ($bookersRaw as $idx => $row) {
            $name = $row['employee_name'] ?? 'ไม่ระบุ';
            $count = (int)$row['booking_count'];
            if ($idx < 5) {
                $bookers[] = [
                    'name' => $name,
                    'count' => $count
                ];
            } else {
                $othersCount += $count;
            }
        }
        if ($othersCount > 0) {
            $bookers[] = [
                'name' => 'อื่นๆ',
                'count' => $othersCount
            ];
        }

        // 5. Monthly fuel usage per car (current month)
        $currentMonthStart = date('Y-m-01');
        $currentMonthEnd   = date('Y-m-t');
        $quotaStats = $db->query("
            SELECT
                c.license_plate,
                c.fuel_type,
                COALESCE(q.monthly_quota, 0)                                       AS quota_liters,
                COALESCE(SUM(r.liters), 0)                                         AS used_liters,
                COALESCE(q.monthly_quota, 0) - COALESCE(SUM(r.liters), 0)         AS remaining_liters
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

        $router = new Router($request, $response);
        return $router->renderView('public/heatmap', [
            'stats'       => $stats,
            'bookers'     => $bookers,
            'fiscalYears' => $fiscalYears,
            'selectedFY'  => $selectedFY,
            'quotaStats'  => $quotaStats,
        ]);
    }
}
