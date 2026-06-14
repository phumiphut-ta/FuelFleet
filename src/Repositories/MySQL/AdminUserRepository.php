<?php
namespace App\Repositories\MySQL;

use App\Repositories\Interfaces\AdminUserRepositoryInterface;
use App\Core\Database;
use PDO;

class AdminUserRepository implements AdminUserRepositoryInterface {
    protected PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    public function findByUsername(string $username): ?array {
        $stmt = $this->db->prepare("SELECT * FROM admin_users WHERE username = :username LIMIT 1");
        $stmt->execute(['username' => $username]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public function find(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM admin_users WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }
}
