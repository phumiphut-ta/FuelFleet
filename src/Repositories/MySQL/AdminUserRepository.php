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

    public function all(): array {
        $stmt = $this->db->prepare("SELECT * FROM admin_users ORDER BY id ASC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(array $data): int {
        $stmt = $this->db->prepare("
            INSERT INTO admin_users (username, password, full_name, role)
            VALUES (:username, :password, :full_name, :role)
        ");
        $stmt->execute([
            'username' => $data['username'],
            'password' => $data['password'],
            'full_name' => $data['full_name'],
            'role' => $data['role'] ?? 'admin'
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool {
        if (!empty($data['password'])) {
            $stmt = $this->db->prepare("
                UPDATE admin_users
                SET username = :username,
                    password = :password,
                    full_name = :full_name,
                    role = :role
                WHERE id = :id
            ");
            return $stmt->execute([
                'username' => $data['username'],
                'password' => $data['password'],
                'full_name' => $data['full_name'],
                'role' => $data['role'] ?? 'admin',
                'id' => $id
            ]);
        } else {
            $stmt = $this->db->prepare("
                UPDATE admin_users
                SET username = :username,
                    full_name = :full_name,
                    role = :role
                WHERE id = :id
            ");
            return $stmt->execute([
                'username' => $data['username'],
                'full_name' => $data['full_name'],
                'role' => $data['role'] ?? 'admin',
                'id' => $id
            ]);
        }
    }

    public function delete(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM admin_users WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }
}
