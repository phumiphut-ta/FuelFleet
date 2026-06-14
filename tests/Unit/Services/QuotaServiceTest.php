<?php
namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use App\Services\QuotaService;
use App\Repositories\Interfaces\QuotaRepositoryInterface;
use App\Repositories\Interfaces\ReceiptRepositoryInterface;
use App\Repositories\Interfaces\CarRepositoryInterface;

class QuotaServiceTest extends TestCase {
    // Tests getCarQuotaStatus returns correct status when under quota
    public function testGetCarQuotaStatusUnderQuota() {
        $quotaRepo = $this->createMock(QuotaRepositoryInterface::class);
        $receiptRepo = $this->createMock(ReceiptRepositoryInterface::class);
        $carRepo = $this->createMock(CarRepositoryInterface::class);

        $quotaRepo->expects($this->once())
            ->method('getCurrentQuotaForCar')
            ->with(1, '2026-05')
            ->willReturn([
                'id' => 10,
                'monthly_quota' => 200.00
            ]);

        $receiptRepo->expects($this->once())
            ->method('getLitersUsedByCarInMonth')
            ->with(1, '2026-05')
            ->willReturn(150.0);

        $service = new QuotaService($quotaRepo, $receiptRepo, $carRepo);

        $result = $service->getCarQuotaStatus(1, '2026-05');

        $this->assertEquals(1, $result['car_id']);
        $this->assertEquals(200.0, $result['quota_liters']);
        $this->assertEquals(150.0, $result['liters_used']);
        $this->assertFalse($result['is_over_quota']);
        $this->assertEquals(75.0, $result['percentage']);
        $this->assertEquals(50.0, $result['remaining_liters']);
    }

    // Tests getCarQuotaStatus returns correct status when over quota
    public function testGetCarQuotaStatusOverQuota() {
        $quotaRepo = $this->createMock(QuotaRepositoryInterface::class);
        $receiptRepo = $this->createMock(ReceiptRepositoryInterface::class);
        $carRepo = $this->createMock(CarRepositoryInterface::class);

        $quotaRepo->expects($this->once())
            ->method('getCurrentQuotaForCar')
            ->with(1, '2026-05')
            ->willReturn([
                'id' => 10,
                'monthly_quota' => 200.00
            ]);

        $receiptRepo->expects($this->once())
            ->method('getLitersUsedByCarInMonth')
            ->with(1, '2026-05')
            ->willReturn(250.0);

        $service = new QuotaService($quotaRepo, $receiptRepo, $carRepo);

        $result = $service->getCarQuotaStatus(1, '2026-05');

        $this->assertEquals(1, $result['car_id']);
        $this->assertEquals(200.0, $result['quota_liters']);
        $this->assertEquals(250.0, $result['liters_used']);
        $this->assertTrue($result['is_over_quota']);
        $this->assertEquals(125.0, $result['percentage']);
        $this->assertEquals(0.0, $result['remaining_liters']);
    }

    // Tests getCarQuotaStatus when no quota is set
    public function testGetCarQuotaStatusNoQuota() {
        $quotaRepo = $this->createMock(QuotaRepositoryInterface::class);
        $receiptRepo = $this->createMock(ReceiptRepositoryInterface::class);
        $carRepo = $this->createMock(CarRepositoryInterface::class);

        $quotaRepo->expects($this->once())
            ->method('getCurrentQuotaForCar')
            ->willReturn(null);

        $receiptRepo->expects($this->once())
            ->method('getLitersUsedByCarInMonth')
            ->willReturn(50.0);

        $service = new QuotaService($quotaRepo, $receiptRepo, $carRepo);

        $result = $service->getCarQuotaStatus(1, '2026-05');

        $this->assertEquals(0.0, $result['quota_liters']);
        $this->assertEquals(50.0, $result['liters_used']);
        $this->assertFalse($result['is_over_quota']);
        $this->assertEquals(0.0, $result['percentage']);
        $this->assertEquals(0.0, $result['remaining_liters']);
    }

    // Tests getOverQuotaCars returns list of only over-quota cars
    public function testGetOverQuotaCars() {
        $quotaRepo = $this->createMock(QuotaRepositoryInterface::class);
        $receiptRepo = $this->createMock(ReceiptRepositoryInterface::class);
        $carRepo = $this->createMock(CarRepositoryInterface::class);

        $carRepo->expects($this->once())
            ->method('all')
            ->willReturn([
                ['id' => 1, 'license_plate' => 'กข-1234', 'fuel_type' => 'Diesel'],
                ['id' => 2, 'license_plate' => 'มค-5678', 'fuel_type' => 'Gasohol 95']
            ]);

        // Mock Quota Repo behaviour:
        // Car 1 (Over Quota): quota = 100, used = 120
        // Car 2 (Under Quota): quota = 200, used = 150
        $quotaRepo->expects($this->exactly(2))
            ->method('getCurrentQuotaForCar')
            ->willReturnMap([
                [1, '2026-05', ['monthly_quota' => 100.0]],
                [2, '2026-05', ['monthly_quota' => 200.0]],
            ]);

        $receiptRepo->expects($this->exactly(2))
            ->method('getLitersUsedByCarInMonth')
            ->willReturnMap([
                [1, '2026-05', 120.0],
                [2, '2026-05', 150.0],
            ]);

        $service = new QuotaService($quotaRepo, $receiptRepo, $carRepo);

        $result = $service->getOverQuotaCars('2026-05');

        $this->assertCount(1, $result);
        $this->assertEquals(1, $result[0]['car_id']);
        $this->assertEquals('กข-1234', $result[0]['license_plate']);
        $this->assertEquals('Diesel', $result[0]['fuel_type']);
        $this->assertTrue($result[0]['is_over_quota']);
    }
}
