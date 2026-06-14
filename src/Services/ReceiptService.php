<?php
namespace App\Services;

use App\Repositories\Interfaces\ReceiptRepositoryInterface;
use App\Repositories\Interfaces\CarRepositoryInterface;
use App\Repositories\MySQL\ReceiptRepository;
use App\Repositories\MySQL\CarRepository;

class ReceiptService {
    protected ReceiptRepositoryInterface $receiptRepo;
    protected CarRepositoryInterface $carRepo;

    public function __construct(
        ?ReceiptRepositoryInterface $receiptRepo = null,
        ?CarRepositoryInterface $carRepo = null
    ) {
        $this->receiptRepo = $receiptRepo ?? new ReceiptRepository();
        $this->carRepo = $carRepo ?? new CarRepository();
    }

    public function recordReceipt(array $data, ?string $filePath): array {
        $existing = $this->receiptRepo->findByReceiptNumber($data['receipt_number']);
        if ($existing) {
            return [
                'success' => false,
                'message' => 'มีเลขที่ใบเสร็จ "' . $data['receipt_number'] . '" ในระบบแล้ว ไม่สามารถป้อนซ้ำได้'
            ];
        }

        $car = $this->carRepo->find((int)$data['car_id']);
        if (!$car) {
            return [
                'success' => false,
                'message' => 'ไม่พบข้อมูลยานพาหนะดังกล่าว'
            ];
        }

        $carFuel = trim(strtolower($car['fuel_type']));
        $receiptFuel = trim(strtolower($data['fuel_type'] ?? ''));

        if ($carFuel !== $receiptFuel) {
            return [
                'success' => false,
                'message' => "ประเภทน้ำมันไม่ตรงกับสเปกของรถ: รถยนต์คันนี้ใช้ {$car['fuel_type']} แต่ใบเสร็จระบุว่าเป็น {$data['fuel_type']}"
            ];
        }

        $receiptId = $this->receiptRepo->create($data, $filePath);
        return [
            'success' => true,
            'receipt_id' => $receiptId,
            'message' => 'บันทึกใบเสร็จน้ำมันสำเร็จ'
        ];
    }

    public function updateReceipt(int $id, array $data, ?string $filePath): array {
        $current = $this->receiptRepo->find($id);
        if (!$current) {
            return [
                'success' => false,
                'message' => 'ไม่พบข้อมูลใบเสร็จที่ต้องการแก้ไข'
            ];
        }

        // Only check duplicate if receipt number has changed
        if ($current['receipt_number'] !== $data['receipt_number']) {
            $existing = $this->receiptRepo->findByReceiptNumber($data['receipt_number']);
            if ($existing) {
                return [
                    'success' => false,
                    'message' => 'มีเลขที่ใบเสร็จ "' . $data['receipt_number'] . '" ในระบบแล้ว ไม่สามารถป้อนซ้ำได้'
                ];
            }
        }

        $car = $this->carRepo->find((int)$data['car_id']);
        if (!$car) {
            return [
                'success' => false,
                'message' => 'ไม่พบข้อมูลยานพาหนะดังกล่าว'
            ];
        }

        $carFuel = trim(strtolower($car['fuel_type']));
        $receiptFuel = trim(strtolower($data['fuel_type'] ?? ''));

        if ($carFuel !== $receiptFuel) {
            return [
                'success' => false,
                'message' => "ประเภทน้ำมันไม่ตรงกับสเปกของรถ: รถยนต์คันนี้ใช้ {$car['fuel_type']} แต่ใบเสร็จระบุว่าเป็น {$data['fuel_type']}"
            ];
        }

        $success = $this->receiptRepo->update($id, $data, $filePath);
        return [
            'success' => $success,
            'message' => 'แก้ไขรายละเอียดใบเสร็จน้ำมันสำเร็จ'
        ];
    }
}
