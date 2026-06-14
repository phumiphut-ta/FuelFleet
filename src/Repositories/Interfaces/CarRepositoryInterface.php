<?php
namespace App\Repositories\Interfaces;

interface CarRepositoryInterface {
    public function all(): array;
    public function find(int $id): ?array;
    public function findByPlate(string $plate): ?array;
    public function create(array $data): int;
    public function update(int $id, array $data): bool;
    
    // History
    public function getBookingHistory(int $carId): array;
    public function getFuelUsageHistory(int $carId): array;
    public function getQuotaHistory(int $carId): array;
}
