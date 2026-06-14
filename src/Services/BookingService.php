<?php
namespace App\Services;

use App\Repositories\Interfaces\BookingRepositoryInterface;
use App\Repositories\Interfaces\SuspensionRepositoryInterface;
use App\Repositories\MySQL\BookingRepository;
use App\Repositories\MySQL\SuspensionRepository;
use Exception;

class BookingService {
    protected BookingRepositoryInterface $bookingRepo;
    protected SuspensionRepositoryInterface $suspensionRepo;

    public function __construct(
        ?BookingRepositoryInterface $bookingRepo = null,
        ?SuspensionRepositoryInterface $suspensionRepo = null
    ) {
        $this->bookingRepo = $bookingRepo ?? new BookingRepository();
        $this->suspensionRepo = $suspensionRepo ?? new SuspensionRepository();
    }

    public function createBooking(array $data, array $provinces): array {
        $carId = (int)$data['car_id'];
        $startTime = $data['start_time'];
        $endTime = $data['end_time'];

        if ($this->suspensionRepo->isCarSuspended($carId, $startTime, $endTime)) {
            return [
                'success' => false,
                'message' => 'ไม่สามารถจองได้ เนื่องจากรถคันนี้ถูกระงับการใช้งานชั่วคราวในช่วงเวลาดังกล่าว'
            ];
        }

        $overlaps = $this->bookingRepo->getOverlappingBookings($carId, $startTime, $endTime);
        if (!empty($overlaps)) {
            $overlapList = [];
            foreach ($overlaps as $overlap) {
                $overlapList[] = $overlap['employee_name'] . ' (' . date('d/m/Y', strtotime($overlap['start_time'])) . ' - ' . date('d/m/Y', strtotime($overlap['end_time'])) . ')';
            }
            return [
                'success' => false,
                'message' => 'ช่วงเวลาดังกล่าวทับซ้อนกับการจองที่มีอยู่แล้วโดย: ' . implode(', ', $overlapList)
            ];
        }

        $bookingId = $this->bookingRepo->create($data, $provinces);
        return [
            'success' => true,
            'booking_id' => $bookingId,
            'message' => 'บันทึกการจองรถยนต์ราชการเรียบร้อยแล้ว'
        ];
    }

    public function cancelBooking(int $bookingId, string $password): array {
        $booking = $this->bookingRepo->find($bookingId);
        if (!$booking) {
            return [
                'success' => false,
                'message' => 'ไม่พบข้อมูลการจองที่ต้องการยกเลิก'
            ];
        }

        if ($booking['status'] === 'Cancelled') {
            return [
                'success' => false,
                'message' => 'การจองนี้ถูกยกเลิกไปก่อนหน้านี้แล้ว'
            ];
        }

        if (!password_verify($password, $booking['cancellation_password'])) {
            return [
                'success' => false,
                'message' => 'รหัสผ่านสำหรับยกเลิกการจองไม่ถูกต้อง'
            ];
        }

        $this->bookingRepo->cancel($bookingId);
        $this->bookingRepo->addCancelLog($bookingId);

        return [
            'success' => true,
            'message' => 'ยกเลิกการจองเรียบร้อยแล้ว'
        ];
    }

    public function updateBooking(int $bookingId, array $data, array $provinces): array {
        $carId = (int)$data['car_id'];
        $startTime = $data['start_time'];
        $endTime = $data['end_time'];

        if ($this->suspensionRepo->isCarSuspended($carId, $startTime, $endTime)) {
            return [
                'success' => false,
                'message' => 'ไม่สามารถจองได้ เนื่องจากรถคันนี้ถูกระงับการใช้งานชั่วคราวในช่วงเวลาดังกล่าว'
            ];
        }

        $overlaps = $this->bookingRepo->getOverlappingBookings($carId, $startTime, $endTime, $bookingId);
        if (!empty($overlaps)) {
            $overlapList = [];
            foreach ($overlaps as $overlap) {
                $overlapList[] = $overlap['employee_name'] . ' (' . date('d/m/Y', strtotime($overlap['start_time'])) . ' - ' . date('d/m/Y', strtotime($overlap['end_time'])) . ')';
            }
            return [
                'success' => false,
                'message' => 'ช่วงเวลาดังกล่าวทับซ้อนกับการจองที่มีอยู่แล้วโดย: ' . implode(', ', $overlapList)
            ];
        }

        $this->bookingRepo->update($bookingId, $data, $provinces);
        return [
            'success' => true,
            'message' => 'แก้ไขรายละเอียดการจองเรียบร้อยแล้ว'
        ];
    }
}
