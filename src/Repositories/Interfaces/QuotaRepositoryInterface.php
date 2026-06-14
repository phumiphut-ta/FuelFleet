<?php
namespace App\Repositories\Interfaces;

interface QuotaRepositoryInterface {
    public function all(): array;
    public function find(int $id): ?array;
    public function getCurrentQuotaForCar(int $carId, string $yearMonth): ?array;
    public function create(array $data): int;
}
