<?php
namespace App\Repositories\Interfaces;

interface AuditLogRepositoryInterface {
    public function all(): array;
    public function create(array $data): int;
}
