<?php
namespace App\Repositories\MySQL;

use App\Repositories\Interfaces\AuditLogRepositoryInterface;
use App\Core\Database;
use PDO;

class AuditLogRepository implements AuditLogRepositoryInterface {
    protected PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    public function all(): array {
        $stmt = $this->db->query("
            SELECT a.*, u.full_name AS admin_name
            FROM audit_logs a
            LEFT JOIN admin_users u ON a.user_id = u.id
            ORDER BY a.timestamp DESC
        ");
        return $stmt->fetchAll();
    }

    public function create(array $data): int {
        $stmt = $this->db->prepare("
            INSERT INTO audit_logs (user_id, username, action, table_name, record_id, previous_value, new_value)
            VALUES (:user_id, :username, :action, :table_name, :record_id, :previous_value, :new_value)
        ");
        $stmt->execute([
            'user_id' => $data['user_id'] ?? null,
            'username' => $data['username'] ?? null,
            'action' => $data['action'],
            'table_name' => $data['table_name'] ?? null,
            'record_id' => $data['record_id'] ?? null,
            'previous_value' => $data['previous_value'] ?? null,
            'new_value' => $data['new_value'] ?? null
        ]);
        return (int)$this->db->lastInsertId();
    }
}
