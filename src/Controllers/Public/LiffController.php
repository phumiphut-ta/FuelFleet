<?php
namespace App\Controllers\Public;

use App\Core\Request;
use App\Core\Response;
use App\Core\Router;
use App\Repositories\Interfaces\CarRepositoryInterface;
use App\Repositories\MySQL\CarRepository;
use App\Services\QuotaService;

class LiffController {
    protected CarRepositoryInterface $carRepo;
    protected QuotaService $quotaService;

    public function __construct(
        ?CarRepositoryInterface $carRepo = null,
        ?QuotaService $quotaService = null
    ) {
        $this->carRepo = $carRepo ?? new CarRepository();
        $this->quotaService = $quotaService ?? new QuotaService();
    }

    public function quotas(Request $request, Response $response) {
        // Fetch all cars and filter for 'Active' status only
        $allCars = $this->carRepo->all();
        $activeCars = array_filter($allCars, function($car) {
            return ($car['status'] ?? '') === 'Active';
        });

        // Current month in 'Y-m' format
        $currentMonth = date('Y-m');

        // Compile quota status for each active vehicle
        $carQuotas = [];
        foreach ($activeCars as $car) {
            $status = $this->quotaService->getCarQuotaStatus((int)$car['id'], $currentMonth);
            
            // Allow negative values for remaining fuel if usage exceeds quota
            $actualRemaining = $status['quota_liters'] - $status['liters_used'];
            
            $hasQuota = $status['quota_liters'] > 0;
            $isOver = $status['liters_used'] > $status['quota_liters'];
            
            // Determine remaining percentage
            if ($hasQuota) {
                $percentage = round(($actualRemaining / $status['quota_liters']) * 100, 2);
            } else {
                $percentage = 0.00;
            }

            $carQuotas[] = [
                'id' => $car['id'],
                'license_plate' => $car['license_plate'],
                'fuel_type' => $car['fuel_type'],
                'color' => $car['color'] ?? '#4f46e5',
                'note' => $car['note'] ?? '',
                'quota_liters' => $status['quota_liters'],
                'liters_used' => $status['liters_used'],
                'remaining_liters' => $actualRemaining,
                'has_quota' => $hasQuota,
                'is_over_quota' => $isOver,
                'percentage' => $percentage
            ];
        }

        $router = new Router($request, $response);
        return $router->renderView('public/liff_quotas', [
            'carQuotas' => $carQuotas,
            'currentMonth' => $currentMonth
        ]);
    }
}
