<?php
namespace App\Repositories\MySQL;

use App\Repositories\Interfaces\EmployeeRepositoryInterface;
use App\Core\Database;
use PDO;

class EmployeeRepository implements EmployeeRepositoryInterface {
    protected PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    public function all(): array {
        $stmt = $this->db->query("
            SELECT e.*, 
                   divi.name AS division_name, 
                   dept.name AS department_name, 
                   pos.name AS position_name 
            FROM employee e
            LEFT JOIN division divi ON e.division_id = divi.id
            LEFT JOIN department dept ON e.department_id = dept.id
            LEFT JOIN position pos ON e.position_id = pos.id
            ORDER BY e.employee_code ASC
        ");
        return $stmt->fetchAll();
    }

    public function find(int $id): ?array {
        $stmt = $this->db->prepare("
            SELECT e.*, 
                   divi.name AS division_name, 
                   dept.name AS department_name, 
                   pos.name AS position_name 
            FROM employee e
            LEFT JOIN division divi ON e.division_id = divi.id
            LEFT JOIN department dept ON e.department_id = dept.id
            LEFT JOIN position pos ON e.position_id = pos.id
            WHERE e.id = :id
        ");
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public function findByCode(string $code): ?array {
        $stmt = $this->db->prepare("SELECT * FROM employee WHERE employee_code = :code");
        $stmt->execute(['code' => $code]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public function create(array $data): int {
        $stmt = $this->db->prepare("
            INSERT INTO employee (employee_code, full_name, division_id, department_id, position_id, status)
            VALUES (:employee_code, :full_name, :division_id, :department_id, :position_id, :status)
        ");
        $stmt->execute([
            'employee_code' => $data['employee_code'],
            'full_name' => $data['full_name'],
            'division_id' => $data['division_id'],
            'department_id' => $data['department_id'],
            'position_id' => $data['position_id'],
            'status' => $data['status'] ?? 'Active'
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool {
        $stmt = $this->db->prepare("
            UPDATE employee 
            SET employee_code = :employee_code, 
                full_name = :full_name, 
                division_id = :division_id, 
                department_id = :department_id, 
                position_id = :position_id, 
                status = :status
            WHERE id = :id
        ");
        return $stmt->execute([
            'id' => $id,
            'employee_code' => $data['employee_code'],
            'full_name' => $data['full_name'],
            'division_id' => $data['division_id'],
            'department_id' => $data['department_id'],
            'position_id' => $data['position_id'],
            'status' => $data['status']
        ]);
    }

    public function delete(int $id): bool {
        $stmt = $this->db->prepare("UPDATE employee SET status = 'Resigned' WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    public function getAssignments(int $employeeId): array {
        $stmt = $this->db->prepare("
            SELECT ea.*, 
                   divi.name AS division_name, 
                   dept.name AS department_name, 
                   pos.name AS position_name
            FROM employee_assignment ea
            LEFT JOIN division divi ON ea.division_id = divi.id
            LEFT JOIN department dept ON ea.department_id = dept.id
            LEFT JOIN position pos ON ea.position_id = pos.id
            WHERE ea.employee_id = :employee_id
            ORDER BY ea.start_date DESC
        ");
        $stmt->execute(['employee_id' => $employeeId]);
        return $stmt->fetchAll();
    }

    public function assign(int $employeeId, ?int $divisionId, ?int $departmentId, int $positionId, string $startDate): int {
        $endDate = date('Y-m-d', strtotime($startDate . ' -1 day'));
        $stmt1 = $this->db->prepare("
            UPDATE employee_assignment 
            SET end_date = :end_date 
            WHERE employee_id = :employee_id AND end_date IS NULL
        ");
        $stmt1->execute(['employee_id' => $employeeId, 'end_date' => $endDate]);

        $stmt2 = $this->db->prepare("
            INSERT INTO employee_assignment (employee_id, division_id, department_id, position_id, start_date)
            VALUES (:employee_id, :division_id, :department_id, :position_id, :start_date)
        ");
        $stmt2->execute([
            'employee_id' => $employeeId,
            'division_id' => $divisionId,
            'department_id' => $departmentId,
            'position_id' => $positionId,
            'start_date' => $startDate
        ]);
        return (int)$this->db->lastInsertId();
    }
}
