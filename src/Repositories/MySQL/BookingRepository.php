<?php
namespace App\Repositories\MySQL;

use App\Repositories\Interfaces\BookingRepositoryInterface;
use App\Core\Database;
use PDO;
use Exception;

class BookingRepository implements BookingRepositoryInterface {
    protected PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    public function all(): array {
        $stmt = $this->db->query("
            SELECT b.*, e.full_name AS employee_name, c.license_plate 
            FROM car_booking b
            LEFT JOIN employee e ON b.employee_id = e.id
            LEFT JOIN car_detail c ON b.car_id = c.id
            ORDER BY b.start_time DESC
        ");
        return $stmt->fetchAll();
    }

    public function find(int $id): ?array {
        $stmt = $this->db->prepare("
            SELECT b.*, e.full_name AS employee_name, e.employee_code, c.license_plate, c.fuel_type
            FROM car_booking b
            LEFT JOIN employee e ON b.employee_id = e.id
            LEFT JOIN car_detail c ON b.car_id = c.id
            WHERE b.id = :id
        ");
        $stmt->execute(['id' => $id]);
        $booking = $stmt->fetch();
        if ($booking) {
            $pStmt = $this->db->prepare("SELECT province_name FROM car_booking_provinces WHERE booking_id = :booking_id");
            $pStmt->execute(['booking_id' => $id]);
            $booking['provinces'] = $pStmt->fetchAll(PDO::FETCH_COLUMN);
        }
        return $booking ?: null;
    }

    public function create(array $data, array $provinces): int {
        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare("
                INSERT INTO car_booking (employee_id, car_id, booking_date, start_time, end_time, purpose, cancellation_password, status)
                VALUES (:employee_id, :car_id, :booking_date, :start_time, :end_time, :purpose, :cancellation_password, 'Confirmed')
            ");
            $stmt->execute([
                'employee_id' => $data['employee_id'],
                'car_id' => $data['car_id'],
                'booking_date' => $data['booking_date'],
                'start_time' => $data['start_time'],
                'end_time' => $data['end_time'],
                'purpose' => $data['purpose'],
                'cancellation_password' => password_hash($data['cancellation_password'], PASSWORD_BCRYPT)
            ]);
            $bookingId = (int)$this->db->lastInsertId();

            $pStmt = $this->db->prepare("INSERT INTO car_booking_provinces (booking_id, province_name) VALUES (:booking_id, :province_name)");
            foreach ($provinces as $province) {
                $pStmt->execute([
                    'booking_id' => $bookingId,
                    'province_name' => $province
                ]);
            }

            $this->db->commit();
            return $bookingId;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function cancel(int $id): bool {
        $stmt = $this->db->prepare("UPDATE car_booking SET status = 'Cancelled' WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    public function update(int $id, array $data, array $provinces): bool {
        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare("
                UPDATE car_booking
                SET employee_id = :employee_id,
                    car_id = :car_id,
                    booking_date = :booking_date,
                    start_time = :start_time,
                    end_time = :end_time,
                    purpose = :purpose
                WHERE id = :id
            ");
            $stmt->execute([
                'id' => $id,
                'employee_id' => $data['employee_id'],
                'car_id' => $data['car_id'],
                'booking_date' => $data['booking_date'],
                'start_time' => $data['start_time'],
                'end_time' => $data['end_time'],
                'purpose' => $data['purpose']
            ]);

            // Clear old provinces
            $deleteStmt = $this->db->prepare("DELETE FROM car_booking_provinces WHERE booking_id = :booking_id");
            $deleteStmt->execute(['booking_id' => $id]);

            // Insert new provinces
            $pStmt = $this->db->prepare("INSERT INTO car_booking_provinces (booking_id, province_name) VALUES (:booking_id, :province_name)");
            foreach ($provinces as $province) {
                $pStmt->execute([
                    'booking_id' => $id,
                    'province_name' => $province
                ]);
            }

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function getOverlappingBookings(int $carId, string $startTime, string $endTime, ?int $excludeId = null): array {
        $sql = "
            SELECT b.*, e.full_name AS employee_name
            FROM car_booking b
            LEFT JOIN employee e ON b.employee_id = e.id
            WHERE b.car_id = :car_id 
              AND b.status = 'Confirmed'
              AND b.start_time < :end_time 
              AND b.end_time > :start_time
        ";
        $params = [
            'car_id' => $carId,
            'start_time' => $startTime,
            'end_time' => $endTime
        ];
        if ($excludeId !== null) {
            $sql .= " AND b.id != :exclude_id";
            $params['exclude_id'] = $excludeId;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getCalendarEvents(): array {
        $events = [];

        $stmtBookings = $this->db->query("
            SELECT b.id, b.start_time, b.end_time, b.purpose, e.full_name AS booker_name, c.license_plate, c.color AS car_color
            FROM car_booking b
            LEFT JOIN employee e ON b.employee_id = e.id
            LEFT JOIN car_detail c ON b.car_id = c.id
            WHERE b.status = 'Confirmed'
        ");
        foreach ($stmtBookings->fetchAll() as $row) {
            $events[] = [
                'id'         => 'booking_' . $row['id'],
                'booking_id' => $row['id'],
                'title'      => '🚗 ' . $row['license_plate'] . ' - ' . $row['booker_name'],
                'start'      => $row['start_time'],
                'end'        => $row['end_time'],
                'color'      => !empty($row['car_color']) ? $row['car_color'] : '#6366f1',
                'extendedProps' => [
                    'type'    => 'booking',
                    'booker'  => $row['booker_name'],
                    'vehicle' => $row['license_plate'],
                    'purpose' => $row['purpose'],
                ],
            ];

        }

        $stmtSuspensions = $this->db->query("
            SELECT s.id, s.start_date, s.end_date, s.reason, c.license_plate
            FROM car_suspension s
            LEFT JOIN car_detail c ON s.car_id = c.id
            WHERE s.status = 'Active'
        ");
        foreach ($stmtSuspensions->fetchAll() as $row) {
            $endDate = date('Y-m-d', strtotime($row['end_date'] . ' +1 day'));
            $events[] = [
                'id' => 'suspension_' . $row['id'],
                'type' => 'suspension',
                'suspension_id' => $row['id'],
                'title' => '⚠️ งดใช้ ' . $row['license_plate'],
                'start' => $row['start_date'],
                'end' => $endDate,
                'allDay' => true,
                'color' => '#f43f5e',
                'extendedProps' => [
                    'vehicle' => $row['license_plate'],
                    'reason' => $row['reason'],
                    'type' => 'ระงับการใช้งานชั่วคราว'
                ]
            ];
        }

        return $events;
    }

    public function addCancelLog(int $bookingId): int {
        $stmt = $this->db->prepare("INSERT INTO booking_cancel_log (booking_id) VALUES (:booking_id)");
        $stmt->execute(['booking_id' => $bookingId]);
        return (int)$this->db->lastInsertId();
    }

    public function searchBookings(string $search, int $limit, int $offset, ?int $carId = null, ?int $employeeId = null, ?string $startDate = null, ?string $endDate = null): array {
        $searchLike = '%' . $search . '%';
        $sql = "
            SELECT b.*, e.full_name AS employee_name, e.employee_code, c.license_plate, c.color AS car_color
            FROM car_booking b
            LEFT JOIN employee e ON b.employee_id = e.id
            LEFT JOIN car_detail c ON b.car_id = c.id
            WHERE (:search = '' 
               OR e.full_name LIKE :sl1 
               OR c.license_plate LIKE :sl2 
               OR b.purpose LIKE :sl3)
        ";
        if ($carId !== null) {
            $sql .= " AND b.car_id = :car_id";
        }
        if ($employeeId !== null) {
            $sql .= " AND b.employee_id = :employee_id";
        }
        if ($startDate !== null && $startDate !== '') {
            $sql .= " AND DATE(b.start_time) >= :start_date";
        }
        if ($endDate !== null && $endDate !== '') {
            $sql .= " AND DATE(b.end_time) <= :end_date";
        }
        $sql .= "
            ORDER BY b.start_time DESC
            LIMIT :limit OFFSET :offset
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':search', $search, PDO::PARAM_STR);
        $stmt->bindValue(':sl1', $searchLike, PDO::PARAM_STR);
        $stmt->bindValue(':sl2', $searchLike, PDO::PARAM_STR);
        $stmt->bindValue(':sl3', $searchLike, PDO::PARAM_STR);
        if ($carId !== null) {
            $stmt->bindValue(':car_id', $carId, PDO::PARAM_INT);
        }
        if ($employeeId !== null) {
            $stmt->bindValue(':employee_id', $employeeId, PDO::PARAM_INT);
        }
        if ($startDate !== null && $startDate !== '') {
            $stmt->bindValue(':start_date', $startDate, PDO::PARAM_STR);
        }
        if ($endDate !== null && $endDate !== '') {
            $stmt->bindValue(':end_date', $endDate, PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $bookings = $stmt->fetchAll();
        
        // Fetch provinces for each booking
        foreach ($bookings as &$b) {
            $pStmt = $this->db->prepare("SELECT province_name FROM car_booking_provinces WHERE booking_id = :booking_id");
            $pStmt->execute(['booking_id' => $b['id']]);
            $b['provinces'] = $pStmt->fetchAll(PDO::FETCH_COLUMN);
        }
        return $bookings;
    }

    public function countBookings(string $search, ?int $carId = null, ?int $employeeId = null, ?string $startDate = null, ?string $endDate = null): int {
        $searchLike = '%' . $search . '%';
        $sql = "
            SELECT COUNT(*)
            FROM car_booking b
            LEFT JOIN employee e ON b.employee_id = e.id
            LEFT JOIN car_detail c ON b.car_id = c.id
            WHERE (:search = '' 
               OR e.full_name LIKE :sl1 
               OR c.license_plate LIKE :sl2 
               OR b.purpose LIKE :sl3)
        ";
        if ($carId !== null) {
            $sql .= " AND b.car_id = :car_id";
        }
        if ($employeeId !== null) {
            $sql .= " AND b.employee_id = :employee_id";
        }
        if ($startDate !== null && $startDate !== '') {
            $sql .= " AND DATE(b.start_time) >= :start_date";
        }
        if ($endDate !== null && $endDate !== '') {
            $sql .= " AND DATE(b.end_time) <= :end_date";
        }
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':search', $search, PDO::PARAM_STR);
        $stmt->bindValue(':sl1', $searchLike, PDO::PARAM_STR);
        $stmt->bindValue(':sl2', $searchLike, PDO::PARAM_STR);
        $stmt->bindValue(':sl3', $searchLike, PDO::PARAM_STR);
        if ($carId !== null) {
            $stmt->bindValue(':car_id', $carId, PDO::PARAM_INT);
        }
        if ($employeeId !== null) {
            $stmt->bindValue(':employee_id', $employeeId, PDO::PARAM_INT);
        }
        if ($startDate !== null && $startDate !== '') {
            $stmt->bindValue(':start_date', $startDate, PDO::PARAM_STR);
        }
        if ($endDate !== null && $endDate !== '') {
            $stmt->bindValue(':end_date', $endDate, PDO::PARAM_STR);
        }
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    public function exportAllBookings(string $search, ?int $carId = null, ?int $employeeId = null, ?string $startDate = null, ?string $endDate = null): array {
        $searchLike = '%' . $search . '%';
        $sql = "
            SELECT b.*, e.full_name AS employee_name, e.employee_code, c.license_plate
            FROM car_booking b
            LEFT JOIN employee e ON b.employee_id = e.id
            LEFT JOIN car_detail c ON b.car_id = c.id
            WHERE (:search = '' 
               OR e.full_name LIKE :sl1 
               OR c.license_plate LIKE :sl2 
               OR b.purpose LIKE :sl3)
        ";
        if ($carId !== null) {
            $sql .= " AND b.car_id = :car_id";
        }
        if ($employeeId !== null) {
            $sql .= " AND b.employee_id = :employee_id";
        }
        if ($startDate !== null && $startDate !== '') {
            $sql .= " AND DATE(b.start_time) >= :start_date";
        }
        if ($endDate !== null && $endDate !== '') {
            $sql .= " AND DATE(b.end_time) <= :end_date";
        }
        $sql .= "
            ORDER BY b.start_time DESC
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':search', $search, PDO::PARAM_STR);
        $stmt->bindValue(':sl1', $searchLike, PDO::PARAM_STR);
        $stmt->bindValue(':sl2', $searchLike, PDO::PARAM_STR);
        $stmt->bindValue(':sl3', $searchLike, PDO::PARAM_STR);
        if ($carId !== null) {
            $stmt->bindValue(':car_id', $carId, PDO::PARAM_INT);
        }
        if ($employeeId !== null) {
            $stmt->bindValue(':employee_id', $employeeId, PDO::PARAM_INT);
        }
        if ($startDate !== null && $startDate !== '') {
            $stmt->bindValue(':start_date', $startDate, PDO::PARAM_STR);
        }
        if ($endDate !== null && $endDate !== '') {
            $stmt->bindValue(':end_date', $endDate, PDO::PARAM_STR);
        }
        $stmt->execute();
        $bookings = $stmt->fetchAll();
        
        // Fetch provinces for each booking
        foreach ($bookings as &$b) {
            $pStmt = $this->db->prepare("SELECT province_name FROM car_booking_provinces WHERE booking_id = :booking_id");
            $pStmt->execute(['booking_id' => $b['id']]);
            $b['provinces'] = $pStmt->fetchAll(PDO::FETCH_COLUMN);
        }
        return $bookings;
    }
}
