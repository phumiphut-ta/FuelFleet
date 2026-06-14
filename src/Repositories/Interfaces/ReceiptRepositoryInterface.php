<?php
namespace App\Repositories\Interfaces;

interface ReceiptRepositoryInterface {
    public function all(): array;
    public function find(int $id): ?array;
    public function findByReceiptNumber(string $receiptNumber): ?array;
    public function create(array $data, ?string $filePath): int;
    public function updateStatus(int $id, string $status): bool;
    public function update(int $id, array $data, ?string $filePath): bool;
    public function getLitersUsedByCarInMonth(int $carId, string $yearMonth): float;
    public function search(string $search, int $limit, int $offset, ?int $carId = null, ?int $employeeId = null, ?string $startDate = null, ?string $endDate = null): array;
    public function count(string $search, ?int $carId = null, ?int $employeeId = null, ?string $startDate = null, ?string $endDate = null): int;
    public function getSearchTotals(string $search, ?int $carId = null, ?int $employeeId = null, ?string $startDate = null, ?string $endDate = null): array;
    public function exportAll(string $search, ?int $carId = null, ?int $employeeId = null, ?string $startDate = null, ?string $endDate = null): array;
}
