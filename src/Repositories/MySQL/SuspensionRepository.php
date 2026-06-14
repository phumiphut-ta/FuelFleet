<?php
namespace App\Repositories\MySQL;

use App\Repositories\Interfaces\SuspensionRepositoryInterface;
use App\Core\Database;
use PDO;

class SuspensionRepository implements SuspensionRepositoryInterface {
    protected PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    public function all(): array {
        $stmt = $this->db->query("
            SELECT s.*, c.license_plate, a.full_name AS admin_name
            FROM car_suspension s
            LEFT JOIN car_detail c ON s.car_id = c.id
            LEFT JOIN admin_users a ON s.created_by = a.id
            ORDER BY s.start_date DESC
        ");
        return $stmt->fetchAll();
    }

    public function find(int $id): ?array {
        $stmt = $this->db->prepare("
            SELECT s.*, c.license_plate, a.full_name AS admin_name
            FROM car_suspension s
            LEFT JOIN car_detail c ON s.car_id = c.id
            LEFT JOIN admin_users a ON s.created_by = a.id
            WHERE s.id = :id
        ");
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public function create(array $data): int {
        $stmt = $this->db->prepare("
            INSERT INTO car_suspension (car_id, start_date, end_date, reason, created_by, status)
            VALUES (:car_id, :start_date, :end_date, :reason, :created_by, 'Active')
        ");
        $stmt->execute([
            'car_id' => $data['car_id'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'reason' => $data['reason'],
            'created_by' => $data['created_by']
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function cancel(int $id): bool {
        $stmt = $this->db->prepare("UPDATE car_suspension SET status = 'Cancelled' WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    public function isCarSuspended(int $carId, string $startTime, string $endTime): bool {
        // Checks if there is any active suspension that overlaps with the requested booking period
        $stmt = $this->db->prepare("
            SELECT COUNT(*) AS cnt 
            FROM car_suspension 
            WHERE car_id = :car_id 
              AND status = 'Active'
              AND start_date <= DATE(:end_time) 
              AND end_date >= DATE(:start_time)
        ");
        $stmt->execute([
            'car_id' => $carId,
            'start_time' => $startTime,
            'end_time' => $endTime
        ]);
        $row = $stmt->fetch();
        return (int)($row['cnt'] ?? 0) > 0;
    }
}
