<?php
namespace App\Repositories\MySQL;

use App\Repositories\Interfaces\CarRepositoryInterface;
use App\Core\Database;
use PDO;

class CarRepository implements CarRepositoryInterface {
    protected PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    public function all(): array {
        $stmt = $this->db->query("SELECT * FROM car_detail ORDER BY license_plate ASC");
        return $stmt->fetchAll();
    }

    public function find(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM car_detail WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public function findByPlate(string $plate): ?array {
        $stmt = $this->db->prepare("SELECT * FROM car_detail WHERE license_plate = :plate");
        $stmt->execute(['plate' => $plate]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public function create(array $data): int {
        $stmt = $this->db->prepare("
            INSERT INTO car_detail (license_plate, fuel_type, status, note, color)
            VALUES (:license_plate, :fuel_type, :status, :note, :color)
        ");
        $stmt->execute([
            'license_plate' => $data['license_plate'],
            'fuel_type' => $data['fuel_type'],
            'status' => $data['status'] ?? 'Active',
            'note' => $data['note'] ?? null,
            'color' => $data['color'] ?? '#4f46e5'
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool {
        $stmt = $this->db->prepare("
            UPDATE car_detail 
            SET license_plate = :license_plate, 
                fuel_type = :fuel_type, 
                status = :status, 
                note = :note,
                color = :color
            WHERE id = :id
        ");
        return $stmt->execute([
            'id' => $id,
            'license_plate' => $data['license_plate'],
            'fuel_type' => $data['fuel_type'],
            'status' => $data['status'],
            'note' => $data['note'] ?? null,
            'color' => $data['color'] ?? '#4f46e5'
        ]);
    }

    public function getBookingHistory(int $carId): array {
        $stmt = $this->db->prepare("
            SELECT b.*, e.full_name AS employee_name
            FROM car_booking b
            LEFT JOIN employee e ON b.employee_id = e.id
            WHERE b.car_id = :car_id
            ORDER BY b.start_time DESC
        ");
        $stmt->execute(['car_id' => $carId]);
        return $stmt->fetchAll();
    }

    public function getFuelUsageHistory(int $carId): array {
        $stmt = $this->db->prepare("
            SELECT r.*, e.full_name AS employee_name
            FROM gas_receipt r
            LEFT JOIN employee e ON r.employee_id = e.id
            WHERE r.car_id = :car_id
            ORDER BY r.receipt_date DESC
        ");
        $stmt->execute(['car_id' => $carId]);
        return $stmt->fetchAll();
    }

    public function getQuotaHistory(int $carId): array {
        $stmt = $this->db->prepare("
            SELECT * FROM car_quota_history 
            WHERE car_id = :car_id 
            ORDER BY effective_month DESC
        ");
        $stmt->execute(['car_id' => $carId]);
        return $stmt->fetchAll();
    }
}
