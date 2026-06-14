<?php
namespace App\Services;

use App\Repositories\Interfaces\QuotaRepositoryInterface;
use App\Repositories\Interfaces\ReceiptRepositoryInterface;
use App\Repositories\Interfaces\CarRepositoryInterface;
use App\Repositories\MySQL\QuotaRepository;
use App\Repositories\MySQL\ReceiptRepository;
use App\Repositories\MySQL\CarRepository;

class QuotaService {
    protected QuotaRepositoryInterface $quotaRepo;
    protected ReceiptRepositoryInterface $receiptRepo;
    protected CarRepositoryInterface $carRepo;

    public function __construct(
        ?QuotaRepositoryInterface $quotaRepo = null,
        ?ReceiptRepositoryInterface $receiptRepo = null,
        ?CarRepositoryInterface $carRepo = null
    ) {
        $this->quotaRepo = $quotaRepo ?? new QuotaRepository();
        $this->receiptRepo = $receiptRepo ?? new ReceiptRepository();
        $this->carRepo = $carRepo ?? new CarRepository();
    }

    public function getCarQuotaStatus(int $carId, string $yearMonth): array {
        $quotaRecord = $this->quotaRepo->getCurrentQuotaForCar($carId, $yearMonth);
        $quotaLiters = $quotaRecord ? (float)$quotaRecord['monthly_quota'] : 0.0;

        $litersUsed = $this->receiptRepo->getLitersUsedByCarInMonth($carId, $yearMonth);

        $isOver = $quotaLiters > 0 && $litersUsed > $quotaLiters;
        $percentage = $quotaLiters > 0 ? ($litersUsed / $quotaLiters) * 100 : 0;

        return [
            'car_id' => $carId,
            'quota_liters' => $quotaLiters,
            'liters_used' => $litersUsed,
            'is_over_quota' => $isOver,
            'percentage' => round($percentage, 2),
            'remaining_liters' => max(0.0, $quotaLiters - $litersUsed),
            'quota_record' => $quotaRecord
        ];
    }

    public function getOverQuotaCars(string $yearMonth): array {
        $cars = $this->carRepo->all();
        $overQuotaList = [];

        foreach ($cars as $car) {
            $status = $this->getCarQuotaStatus((int)$car['id'], $yearMonth);
            if ($status['is_over_quota']) {
                $status['license_plate'] = $car['license_plate'];
                $status['fuel_type'] = $car['fuel_type'];
                $overQuotaList[] = $status;
            }
        }

        return $overQuotaList;
    }
}
