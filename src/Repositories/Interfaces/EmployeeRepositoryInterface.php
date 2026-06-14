<?php
namespace App\Repositories\Interfaces;

interface EmployeeRepositoryInterface {
    public function all(): array;
    public function find(int $id): ?array;
    public function findByCode(string $code): ?array;
    public function create(array $data): int;
    public function update(int $id, array $data): bool;
    public function delete(int $id): bool;
    
    // Assignment history
    public function getAssignments(int $employeeId): array;
    public function assign(int $employeeId, ?int $divisionId, ?int $departmentId, int $positionId, string $startDate): int;
}
