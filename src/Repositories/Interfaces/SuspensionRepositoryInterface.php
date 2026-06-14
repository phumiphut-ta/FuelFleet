<?php
namespace App\Repositories\Interfaces;

interface SuspensionRepositoryInterface {
    public function all(): array;
    public function find(int $id): ?array;
    public function create(array $data): int;
    public function cancel(int $id): bool;
    
    // Safety checks
    public function isCarSuspended(int $carId, string $startTime, string $endTime): bool;
}
