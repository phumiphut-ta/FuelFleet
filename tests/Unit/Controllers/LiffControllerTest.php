<?php
namespace Tests\Unit\Controllers;

use PHPUnit\Framework\TestCase;
use App\Controllers\Public\LiffController;
use App\Repositories\Interfaces\CarRepositoryInterface;
use App\Services\QuotaService;

class LiffControllerTest extends TestCase {
    public function testLiffControllerInstantiationWithMocks() {
        $carRepo = $this->createMock(CarRepositoryInterface::class);
        $quotaService = $this->createMock(QuotaService::class);

        $controller = new LiffController($carRepo, $quotaService);
        $this->assertInstanceOf(LiffController::class, $controller);
    }
}
