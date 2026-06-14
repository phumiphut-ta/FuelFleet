<?php
namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use App\Services\ReceiptService;
use App\Repositories\Interfaces\ReceiptRepositoryInterface;
use App\Repositories\Interfaces\CarRepositoryInterface;

class ReceiptServiceTest extends TestCase {
    // Tests recordReceipt successfully
    public function testRecordReceiptSuccess() {
        $receiptRepo = $this->createMock(ReceiptRepositoryInterface::class);
        $carRepo = $this->createMock(CarRepositoryInterface::class);

        $receiptRepo->expects($this->once())
            ->method('findByReceiptNumber')
            ->with('REC-1001')
            ->willReturn(null);

        $carRepo->expects($this->once())
            ->method('find')
            ->with(5)
            ->willReturn([
                'id' => 5,
                'license_plate' => 'AB-123',
                'fuel_type' => 'Diesel'
            ]);

        $data = [
            'receipt_number' => 'REC-1001',
            'car_id' => 5,
            'fuel_type' => 'Diesel',
            'amount' => 1200.00,
            'liters' => 35.5
        ];

        $receiptRepo->expects($this->once())
            ->method('create')
            ->with($data, '/uploads/receipt.jpg')
            ->willReturn(101);

        $service = new ReceiptService($receiptRepo, $carRepo);

        $result = $service->recordReceipt($data, '/uploads/receipt.jpg');

        $this->assertTrue($result['success']);
        $this->assertEquals(101, $result['receipt_id']);
        $this->assertEquals('บันทึกใบเสร็จน้ำมันสำเร็จ', $result['message']);
    }

    // Tests recordReceipt fails when duplicate receipt number
    public function testRecordReceiptDuplicateNumber() {
        $receiptRepo = $this->createMock(ReceiptRepositoryInterface::class);
        $carRepo = $this->createMock(CarRepositoryInterface::class);

        $receiptRepo->expects($this->once())
            ->method('findByReceiptNumber')
            ->with('REC-1001')
            ->willReturn(['id' => 99]);

        $carRepo->expects($this->never())
            ->method('find');

        $service = new ReceiptService($receiptRepo, $carRepo);

        $data = [
            'receipt_number' => 'REC-1001'
        ];

        $result = $service->recordReceipt($data, null);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('มีเลขที่ใบเสร็จ "REC-1001" ในระบบแล้ว', $result['message']);
    }

    // Tests recordReceipt fails when car is not found
    public function testRecordReceiptCarNotFound() {
        $receiptRepo = $this->createMock(ReceiptRepositoryInterface::class);
        $carRepo = $this->createMock(CarRepositoryInterface::class);

        $receiptRepo->expects($this->once())
            ->method('findByReceiptNumber')
            ->willReturn(null);

        $carRepo->expects($this->once())
            ->method('find')
            ->with(5)
            ->willReturn(null);

        $service = new ReceiptService($receiptRepo, $carRepo);

        $data = [
            'receipt_number' => 'REC-1001',
            'car_id' => 5
        ];

        $result = $service->recordReceipt($data, null);

        $this->assertFalse($result['success']);
        $this->assertEquals('ไม่พบข้อมูลยานพาหนะดังกล่าว', $result['message']);
    }

    // Tests recordReceipt fails when fuel type does not match car's spec
    public function testRecordReceiptFuelTypeMismatch() {
        $receiptRepo = $this->createMock(ReceiptRepositoryInterface::class);
        $carRepo = $this->createMock(CarRepositoryInterface::class);

        $receiptRepo->expects($this->once())
            ->method('findByReceiptNumber')
            ->willReturn(null);

        $carRepo->expects($this->once())
            ->method('find')
            ->with(5)
            ->willReturn([
                'id' => 5,
                'license_plate' => 'AB-123',
                'fuel_type' => 'Diesel'
            ]);

        $service = new ReceiptService($receiptRepo, $carRepo);

        $data = [
            'receipt_number' => 'REC-1001',
            'car_id' => 5,
            'fuel_type' => 'Gasohol 95'
        ];

        $result = $service->recordReceipt($data, null);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('ประเภทน้ำมันไม่ตรงกับสเปกของรถ', $result['message']);
    }

    // Tests updateReceipt successfully
    public function testUpdateReceiptSuccess() {
        $receiptRepo = $this->createMock(ReceiptRepositoryInterface::class);
        $carRepo = $this->createMock(CarRepositoryInterface::class);

        $receiptRepo->expects($this->once())
            ->method('find')
            ->with(101)
            ->willReturn([
                'id' => 101,
                'receipt_number' => 'REC-1001',
                'car_id' => 5
            ]);

        // Same receipt number, so findByReceiptNumber should not be called
        $receiptRepo->expects($this->never())
            ->method('findByReceiptNumber');

        $carRepo->expects($this->once())
            ->method('find')
            ->with(5)
            ->willReturn([
                'id' => 5,
                'license_plate' => 'AB-123',
                'fuel_type' => 'Diesel'
            ]);

        $data = [
            'receipt_number' => 'REC-1001',
            'car_id' => 5,
            'fuel_type' => 'Diesel',
            'amount' => 1500.00,
            'liters' => 45.0
        ];

        $receiptRepo->expects($this->once())
            ->method('update')
            ->with(101, $data, '/uploads/new_receipt.jpg')
            ->willReturn(true);

        $service = new ReceiptService($receiptRepo, $carRepo);

        $result = $service->updateReceipt(101, $data, '/uploads/new_receipt.jpg');

        $this->assertTrue($result['success']);
        $this->assertEquals('แก้ไขรายละเอียดใบเสร็จน้ำมันสำเร็จ', $result['message']);
    }

    // Tests updateReceipt fails when number is changed and already exists on another record
    public function testUpdateReceiptFailsWhenDuplicateNumber() {
        $receiptRepo = $this->createMock(ReceiptRepositoryInterface::class);
        $carRepo = $this->createMock(CarRepositoryInterface::class);

        $receiptRepo->expects($this->once())
            ->method('find')
            ->with(101)
            ->willReturn([
                'id' => 101,
                'receipt_number' => 'REC-1001',
                'car_id' => 5
            ]);

        // Changed receipt number, so findByReceiptNumber should check and return existing record
        $receiptRepo->expects($this->once())
            ->method('findByReceiptNumber')
            ->with('REC-1002')
            ->willReturn(['id' => 102]);

        $carRepo->expects($this->never())
            ->method('find');

        $data = [
            'receipt_number' => 'REC-1002',
            'car_id' => 5
        ];

        $service = new ReceiptService($receiptRepo, $carRepo);

        $result = $service->updateReceipt(101, $data, null);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('มีเลขที่ใบเสร็จ "REC-1002" ในระบบแล้ว', $result['message']);
    }
}
