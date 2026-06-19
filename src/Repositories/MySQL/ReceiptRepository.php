<?php
namespace App\Repositories\MySQL;

use App\Repositories\Interfaces\ReceiptRepositoryInterface;
use App\Core\Database;
use PDO;
use Exception;

class ReceiptRepository implements ReceiptRepositoryInterface {
    protected PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    public function all(): array {
        $stmt = $this->db->query("
            SELECT r.*, e.full_name AS employee_name, c.license_plate, c.fuel_type, a.file_path
            FROM gas_receipt r
            LEFT JOIN employee e ON r.employee_id = e.id
            LEFT JOIN car_detail c ON r.car_id = c.id
            LEFT JOIN receipt_attachment a ON a.receipt_id = r.id
            ORDER BY r.receipt_date DESC, r.created_at DESC
        ");
        return $stmt->fetchAll();
    }

    public function find(int $id): ?array {
        $stmt = $this->db->prepare("
            SELECT r.*, e.full_name AS employee_name, c.license_plate, c.fuel_type AS car_fuel_type, a.file_path
            FROM gas_receipt r
            LEFT JOIN employee e ON r.employee_id = e.id
            LEFT JOIN car_detail c ON r.car_id = c.id
            LEFT JOIN receipt_attachment a ON a.receipt_id = r.id
            WHERE r.id = :id
        ");
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public function findByReceiptNumber(string $receiptNumber): ?array {
        $stmt = $this->db->prepare("SELECT * FROM gas_receipt WHERE receipt_number = :receipt_number AND status != 'Cancelled'");
        $stmt->execute(['receipt_number' => $receiptNumber]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public function create(array $data, ?string $filePath): int {
        try {
            $this->db->beginTransaction();

            $pricePerLiter = (float)$data['amount'] / (float)$data['liters'];

            $stmt = $this->db->prepare("
                INSERT INTO gas_receipt (receipt_number, receipt_date, record_date, employee_id, car_id, amount, liters, price_per_liter, mileage, status)
                VALUES (:receipt_number, :receipt_date, :record_date, :employee_id, :car_id, :amount, :liters, :price_per_liter, :mileage, :status)
            ");
            
            $stmt->execute([
                'receipt_number' => $data['receipt_number'],
                'receipt_date' => $data['receipt_date'],
                'record_date' => $data['record_date'] ?? date('Y-m-d'),
                'employee_id' => $data['employee_id'],
                'car_id' => $data['car_id'],
                'amount' => $data['amount'],
                'liters' => $data['liters'],
                'price_per_liter' => $pricePerLiter,
                'mileage' => $data['mileage'] ?? null,
                'status' => $data['status'] ?? 'Pending verification'
            ]);
            $receiptId = (int)$this->db->lastInsertId();

            if ($filePath) {
                $stmtAttach = $this->db->prepare("
                    INSERT INTO receipt_attachment (receipt_id, file_path)
                    VALUES (:receipt_id, :file_path)
                ");
                $stmtAttach->execute([
                    'receipt_id' => $receiptId,
                    'file_path' => $filePath
                ]);
            }

            $this->db->commit();
            return $receiptId;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function updateStatus(int $id, string $status): bool {
        $stmt = $this->db->prepare("UPDATE gas_receipt SET status = :status WHERE id = :id");
        return $stmt->execute(['id' => $id, 'status' => $status]);
    }

    public function update(int $id, array $data, ?string $filePath): bool {
        try {
            $this->db->beginTransaction();

            $pricePerLiter = (float)$data['amount'] / (float)$data['liters'];

            $stmt = $this->db->prepare("
                UPDATE gas_receipt 
                SET receipt_number = :receipt_number,
                    receipt_date = :receipt_date,
                    employee_id = :employee_id,
                    car_id = :car_id,
                    amount = :amount,
                    liters = :liters,
                    price_per_liter = :price_per_liter,
                    mileage = :mileage,
                    status = :status
                WHERE id = :id
            ");
            
            $stmt->execute([
                'id' => $id,
                'receipt_number' => $data['receipt_number'],
                'receipt_date' => $data['receipt_date'],
                'employee_id' => $data['employee_id'],
                'car_id' => $data['car_id'],
                'amount' => $data['amount'],
                'liters' => $data['liters'],
                'price_per_liter' => $pricePerLiter,
                'mileage' => $data['mileage'] ?? null,
                'status' => $data['status'] ?? 'Verified'
            ]);

            if ($filePath !== null) {
                // Remove old attachment if exists and insert/update the new one
                $stmtCheck = $this->db->prepare("SELECT file_path FROM receipt_attachment WHERE receipt_id = :receipt_id");
                $stmtCheck->execute(['receipt_id' => $id]);
                $oldFile = $stmtCheck->fetchColumn();

                if ($oldFile) {
                    $oldFilePath = dirname(__DIR__, 3) . '/public' . $oldFile;
                    if (file_exists($oldFilePath) && is_file($oldFilePath)) {
                        @unlink($oldFilePath);
                    }
                    $stmtUpdate = $this->db->prepare("UPDATE receipt_attachment SET file_path = :file_path WHERE receipt_id = :receipt_id");
                    $stmtUpdate->execute([
                        'receipt_id' => $id,
                        'file_path' => $filePath
                    ]);
                } else {
                    $stmtInsert = $this->db->prepare("INSERT INTO receipt_attachment (receipt_id, file_path) VALUES (:receipt_id, :file_path)");
                    $stmtInsert->execute([
                        'receipt_id' => $id,
                        'file_path' => $filePath
                    ]);
                }
            }

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function getLitersUsedByCarInMonth(int $carId, string $yearMonth): float {
        // yearMonth e.g. '2026-05'
        $stmt = $this->db->prepare("
            SELECT SUM(liters) AS total_liters 
            FROM gas_receipt 
            WHERE car_id = :car_id 
              AND status = 'Verified'
              AND DATE_FORMAT(receipt_date, '%Y-%m') = :year_month
        ");
        $stmt->execute([
            'car_id' => $carId,
            'year_month' => $yearMonth
        ]);
        $row = $stmt->fetch();
        return (float)($row['total_liters'] ?? 0.0);
    }

    public function search(string $search, int $limit, int $offset, ?int $carId = null, ?int $employeeId = null, ?string $startDate = null, ?string $endDate = null): array {
        $searchLike = '%' . $search . '%';
        
        $sql = "
            SELECT r.*, e.full_name AS employee_name, c.license_plate, c.fuel_type, a.file_path
            FROM gas_receipt r
            LEFT JOIN employee e ON r.employee_id = e.id
            LEFT JOIN car_detail c ON r.car_id = c.id
            LEFT JOIN receipt_attachment a ON a.receipt_id = r.id
            WHERE (:search = '' 
               OR r.receipt_number LIKE :sl1 
               OR c.license_plate LIKE :sl2 
               OR e.full_name LIKE :sl3 
               OR r.status LIKE :sl4 
               OR c.fuel_type LIKE :sl5)
        ";
        
        if ($carId !== null) {
            $sql .= " AND r.car_id = :car_id ";
        }
        if ($employeeId !== null) {
            $sql .= " AND r.employee_id = :employee_id ";
        }
        if ($startDate !== null && $startDate !== '') {
            $sql .= " AND r.receipt_date >= :start_date ";
        }
        if ($endDate !== null && $endDate !== '') {
            $sql .= " AND r.receipt_date <= :end_date ";
        }
        
        $sql .= "
            ORDER BY r.receipt_date DESC, r.created_at DESC
            LIMIT :limit OFFSET :offset
        ";
        
        $stmt = $this->db->prepare($sql);
        
        $stmt->bindValue(':search', $search, PDO::PARAM_STR);
        $stmt->bindValue(':sl1', $searchLike, PDO::PARAM_STR);
        $stmt->bindValue(':sl2', $searchLike, PDO::PARAM_STR);
        $stmt->bindValue(':sl3', $searchLike, PDO::PARAM_STR);
        $stmt->bindValue(':sl4', $searchLike, PDO::PARAM_STR);
        $stmt->bindValue(':sl5', $searchLike, PDO::PARAM_STR);
        
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
        
        return $stmt->fetchAll();
    }

    public function count(string $search, ?int $carId = null, ?int $employeeId = null, ?string $startDate = null, ?string $endDate = null): int {
        $searchLike = '%' . $search . '%';
        
        $sql = "
            SELECT COUNT(*)
            FROM gas_receipt r
            LEFT JOIN employee e ON r.employee_id = e.id
            LEFT JOIN car_detail c ON r.car_id = c.id
            WHERE (:search = '' 
               OR r.receipt_number LIKE :sl1 
               OR c.license_plate LIKE :sl2 
               OR e.full_name LIKE :sl3 
               OR r.status LIKE :sl4 
               OR c.fuel_type LIKE :sl5)
        ";
        
        if ($carId !== null) {
            $sql .= " AND r.car_id = :car_id ";
        }
        if ($employeeId !== null) {
            $sql .= " AND r.employee_id = :employee_id ";
        }
        if ($startDate !== null && $startDate !== '') {
            $sql .= " AND r.receipt_date >= :start_date ";
        }
        if ($endDate !== null && $endDate !== '') {
            $sql .= " AND r.receipt_date <= :end_date ";
        }
        
        $stmt = $this->db->prepare($sql);
        
        $stmt->bindValue(':search', $search, PDO::PARAM_STR);
        $stmt->bindValue(':sl1', $searchLike, PDO::PARAM_STR);
        $stmt->bindValue(':sl2', $searchLike, PDO::PARAM_STR);
        $stmt->bindValue(':sl3', $searchLike, PDO::PARAM_STR);
        $stmt->bindValue(':sl4', $searchLike, PDO::PARAM_STR);
        $stmt->bindValue(':sl5', $searchLike, PDO::PARAM_STR);
        
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

    public function getSearchTotals(string $search, ?int $carId = null, ?int $employeeId = null, ?string $startDate = null, ?string $endDate = null): array {
        $searchLike = '%' . $search . '%';
        
        $sql = "
            SELECT SUM(r.liters) AS total_liters, SUM(r.amount) AS total_amount
            FROM gas_receipt r
            LEFT JOIN employee e ON r.employee_id = e.id
            LEFT JOIN car_detail c ON r.car_id = c.id
            WHERE r.status != 'Cancelled'
              AND (:search = '' 
                 OR r.receipt_number LIKE :sl1 
                 OR c.license_plate LIKE :sl2 
                 OR e.full_name LIKE :sl3 
                 OR r.status LIKE :sl4 
                 OR c.fuel_type LIKE :sl5)
        ";
        
        if ($carId !== null) {
            $sql .= " AND r.car_id = :car_id ";
        }
        if ($employeeId !== null) {
            $sql .= " AND r.employee_id = :employee_id ";
        }
        if ($startDate !== null && $startDate !== '') {
            $sql .= " AND r.receipt_date >= :start_date ";
        }
        if ($endDate !== null && $endDate !== '') {
            $sql .= " AND r.receipt_date <= :end_date ";
        }
        
        $stmt = $this->db->prepare($sql);
        
        $stmt->bindValue(':search', $search, PDO::PARAM_STR);
        $stmt->bindValue(':sl1', $searchLike, PDO::PARAM_STR);
        $stmt->bindValue(':sl2', $searchLike, PDO::PARAM_STR);
        $stmt->bindValue(':sl3', $searchLike, PDO::PARAM_STR);
        $stmt->bindValue(':sl4', $searchLike, PDO::PARAM_STR);
        $stmt->bindValue(':sl5', $searchLike, PDO::PARAM_STR);
        
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
        
        $row = $stmt->fetch();
        return [
            'total_liters' => (float)($row['total_liters'] ?? 0.0),
            'total_amount' => (float)($row['total_amount'] ?? 0.0)
        ];
    }

    public function exportAll(string $search, ?int $carId = null, ?int $employeeId = null, ?string $startDate = null, ?string $endDate = null): array {
        $searchLike = '%' . $search . '%';
        
        $sql = "
            SELECT r.*, e.full_name AS employee_name, c.license_plate, c.fuel_type, a.file_path
            FROM gas_receipt r
            LEFT JOIN employee e ON r.employee_id = e.id
            LEFT JOIN car_detail c ON r.car_id = c.id
            LEFT JOIN receipt_attachment a ON a.receipt_id = r.id
            WHERE (:search = '' 
               OR r.receipt_number LIKE :sl1 
               OR c.license_plate LIKE :sl2 
               OR e.full_name LIKE :sl3 
               OR r.status LIKE :sl4 
               OR c.fuel_type LIKE :sl5)
        ";
        
        if ($carId !== null) {
            $sql .= " AND r.car_id = :car_id ";
        }
        if ($employeeId !== null) {
            $sql .= " AND r.employee_id = :employee_id ";
        }
        if ($startDate !== null && $startDate !== '') {
            $sql .= " AND r.receipt_date >= :start_date ";
        }
        if ($endDate !== null && $endDate !== '') {
            $sql .= " AND r.receipt_date <= :end_date ";
        }
        
        $sql .= "
            ORDER BY r.receipt_date DESC, r.created_at DESC
        ";
        
        $stmt = $this->db->prepare($sql);
        
        $stmt->bindValue(':search', $search, PDO::PARAM_STR);
        $stmt->bindValue(':sl1', $searchLike, PDO::PARAM_STR);
        $stmt->bindValue(':sl2', $searchLike, PDO::PARAM_STR);
        $stmt->bindValue(':sl3', $searchLike, PDO::PARAM_STR);
        $stmt->bindValue(':sl4', $searchLike, PDO::PARAM_STR);
        $stmt->bindValue(':sl5', $searchLike, PDO::PARAM_STR);
        
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
        return $stmt->fetchAll();
    }
}
