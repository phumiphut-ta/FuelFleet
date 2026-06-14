<?php
namespace App\Repositories\Interfaces;

interface BookingRepositoryInterface {
    public function all(): array;
    public function find(int $id): ?array;
    public function create(array $data, array $provinces): int;
    public function cancel(int $id): bool;
    public function cancelWithReason(int $id, string $reason): bool;
    public function approve(int $id): bool;
    
    public function update(int $id, array $data, array $provinces): bool;

    public function searchBookings(string $search, int $limit, int $offset, ?int $carId = null, ?int $employeeId = null, ?string $startDate = null, ?string $endDate = null): array;
    public function countBookings(string $search, ?int $carId = null, ?int $employeeId = null, ?string $startDate = null, ?string $endDate = null): int;
    public function exportAllBookings(string $search, ?int $carId = null, ?int $employeeId = null, ?string $startDate = null, ?string $endDate = null): array;

    // Safety checks
    public function getOverlappingBookings(int $carId, string $startTime, string $endTime, ?int $excludeId = null): array;
    
    // Calendar events
    public function getCalendarEvents(): array;
    
    // Log
    public function addCancelLog(int $bookingId): int;
}
