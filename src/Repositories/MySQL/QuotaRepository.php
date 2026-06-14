<?php
namespace App\Repositories\MySQL;

use App\Repositories\Interfaces\QuotaRepositoryInterface;
use App\Core\Database;
use PDO;

class QuotaRepository implements QuotaRepositoryInterface {
    protected PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    public function all(): array {
        $stmt = $this->db->query("
            SELECT q.*, c.license_plate, c.fuel_type
            FROM car_quota_history q
            LEFT JOIN car_detail c ON q.car_id = c.id
            ORDER BY q.effective_month DESC, c.license_plate ASC
        ");
        return $stmt->fetchAll();
    }

    public function find(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM car_quota_history WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public function getCurrentQuotaForCar(int $carId, string $yearMonth): ?array {
        // e.g. yearMonth is '2026-05' -> effective_date is '2026-05-01'
        $effectiveDate = $yearMonth . '-01';
        $stmt = $this->db->prepare("
            SELECT * FROM car_quota_history 
            WHERE car_id = :car_id 
              AND effective_month <= :effective_date
            ORDER BY effective_month DESC 
            LIMIT 1
        ");
        $stmt->execute([
            'car_id' => $carId,
            'effective_date' => $effectiveDate
        ]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public function create(array $data): int {
        $stmt = $this->db->prepare("
            INSERT INTO car_quota_history (car_id, monthly_quota, effective_month)
            VALUES (:car_id, :monthly_quota, :effective_month)
        ");
        $stmt->execute([
            'car_id' => $data['car_id'],
            'monthly_quota' => $data['monthly_quota'],
            'effective_month' => $data['effective_month']
        ]);
        return (int)$this->db->lastInsertId();
    }
}
