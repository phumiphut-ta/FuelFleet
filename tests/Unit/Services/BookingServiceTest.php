<?php
namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use App\Services\BookingService;
use App\Repositories\Interfaces\BookingRepositoryInterface;
use App\Repositories\Interfaces\SuspensionRepositoryInterface;

class BookingServiceTest extends TestCase {
    // Tests createBooking succeeds when not suspended and no overlaps
    public function testCreateBookingSuccess() {
        $bookingRepo = $this->createMock(BookingRepositoryInterface::class);
        $suspensionRepo = $this->createMock(SuspensionRepositoryInterface::class);

        $suspensionRepo->expects($this->once())
            ->method('isCarSuspended')
            ->with(1, '2026-06-01 00:00:00', '2026-06-01 23:59:59')
            ->willReturn(false);

        $bookingRepo->expects($this->once())
            ->method('getOverlappingBookings')
            ->with(1, '2026-06-01 00:00:00', '2026-06-01 23:59:59')
            ->willReturn([]);

        $bookingRepo->expects($this->once())
            ->method('create')
            ->willReturn(10);

        $service = new BookingService($bookingRepo, $suspensionRepo);

        $data = [
            'car_id' => 1,
            'start_time' => '2026-06-01 00:00:00',
            'end_time' => '2026-06-01 23:59:59',
            'employee_id' => 1,
            'purpose' => 'Meeting',
            'cancellation_password' => 'pass'
        ];
        $provinces = ['Bangkok'];

        $result = $service->createBooking($data, $provinces);

        $this->assertTrue($result['success']);
        $this->assertEquals(10, $result['booking_id']);
        $this->assertEquals('บันทึกการจองรถยนต์ราชการเรียบร้อยแล้ว', $result['message']);
    }

    // Tests createBooking fails when car is suspended
    public function testCreateBookingFailsWhenSuspended() {
        $bookingRepo = $this->createMock(BookingRepositoryInterface::class);
        $suspensionRepo = $this->createMock(SuspensionRepositoryInterface::class);

        $suspensionRepo->expects($this->once())
            ->method('isCarSuspended')
            ->willReturn(true);

        $bookingRepo->expects($this->never())
            ->method('getOverlappingBookings');

        $bookingRepo->expects($this->never())
            ->method('create');

        $service = new BookingService($bookingRepo, $suspensionRepo);

        $data = [
            'car_id' => 1,
            'start_time' => '2026-06-01 00:00:00',
            'end_time' => '2026-06-01 23:59:59'
        ];

        $result = $service->createBooking($data, []);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('ถูกระงับการใช้งานชั่วคราว', $result['message']);
    }

    // Tests createBooking fails when overlaps exist
    public function testCreateBookingFailsOnOverlaps() {
        $bookingRepo = $this->createMock(BookingRepositoryInterface::class);
        $suspensionRepo = $this->createMock(SuspensionRepositoryInterface::class);

        $suspensionRepo->expects($this->once())
            ->method('isCarSuspended')
            ->willReturn(false);

        $bookingRepo->expects($this->once())
            ->method('getOverlappingBookings')
            ->willReturn([
                [
                    'employee_name' => 'John Doe',
                    'start_time' => '2026-06-01 00:00:00',
                    'end_time' => '2026-06-01 23:59:59'
                ]
            ]);

        $bookingRepo->expects($this->never())
            ->method('create');

        $service = new BookingService($bookingRepo, $suspensionRepo);

        $data = [
            'car_id' => 1,
            'start_time' => '2026-06-01 00:00:00',
            'end_time' => '2026-06-01 23:59:59'
        ];

        $result = $service->createBooking($data, []);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('ทับซ้อนกับการจองที่มีอยู่แล้ว', $result['message']);
    }

    // Tests cancelBooking successfully with correct password
    public function testCancelBookingSuccess() {
        $bookingRepo = $this->createMock(BookingRepositoryInterface::class);
        $suspensionRepo = $this->createMock(SuspensionRepositoryInterface::class);

        $hashedPassword = password_hash('secret_pass', PASSWORD_BCRYPT);
        $bookingRepo->expects($this->once())
            ->method('find')
            ->with(10)
            ->willReturn([
                'id' => 10,
                'status' => 'Confirmed',
                'cancellation_password' => $hashedPassword
            ]);

        $bookingRepo->expects($this->once())
            ->method('cancel')
            ->with(10)
            ->willReturn(true);

        $bookingRepo->expects($this->once())
            ->method('addCancelLog')
            ->with(10)
            ->willReturn(1);

        $service = new BookingService($bookingRepo, $suspensionRepo);

        $result = $service->cancelBooking(10, 'secret_pass');

        $this->assertTrue($result['success']);
        $this->assertEquals('ยกเลิกการจองเรียบร้อยแล้ว', $result['message']);
    }

    // Tests cancelBooking fails with wrong password
    public function testCancelBookingWrongPassword() {
        $bookingRepo = $this->createMock(BookingRepositoryInterface::class);
        $suspensionRepo = $this->createMock(SuspensionRepositoryInterface::class);

        $hashedPassword = password_hash('secret_pass', PASSWORD_BCRYPT);
        $bookingRepo->expects($this->once())
            ->method('find')
            ->with(10)
            ->willReturn([
                'id' => 10,
                'status' => 'Confirmed',
                'cancellation_password' => $hashedPassword
            ]);

        $bookingRepo->expects($this->never())
            ->method('cancel');

        $service = new BookingService($bookingRepo, $suspensionRepo);

        $result = $service->cancelBooking(10, 'wrong_pass');

        $this->assertFalse($result['success']);
        $this->assertEquals('รหัสผ่านสำหรับยกเลิกการจองไม่ถูกต้อง', $result['message']);
    }

    // Tests cancelBooking fails when already cancelled
    public function testCancelBookingAlreadyCancelled() {
        $bookingRepo = $this->createMock(BookingRepositoryInterface::class);
        $suspensionRepo = $this->createMock(SuspensionRepositoryInterface::class);

        $bookingRepo->expects($this->once())
            ->method('find')
            ->with(10)
            ->willReturn([
                'id' => 10,
                'status' => 'Cancelled',
                'cancellation_password' => 'hash'
            ]);

        $bookingRepo->expects($this->never())
            ->method('cancel');

        $service = new BookingService($bookingRepo, $suspensionRepo);

        $result = $service->cancelBooking(10, 'secret_pass');

        $this->assertFalse($result['success']);
        $this->assertEquals('การจองนี้ถูกยกเลิกไปก่อนหน้านี้แล้ว', $result['message']);
    }

    // Tests cancelBooking fails when booking not found
    public function testCancelBookingNotFound() {
        $bookingRepo = $this->createMock(BookingRepositoryInterface::class);
        $suspensionRepo = $this->createMock(SuspensionRepositoryInterface::class);

        $bookingRepo->expects($this->once())
            ->method('find')
            ->with(10)
            ->willReturn(null);

        $service = new BookingService($bookingRepo, $suspensionRepo);

        $result = $service->cancelBooking(10, 'secret_pass');

        $this->assertFalse($result['success']);
        $this->assertEquals('ไม่พบข้อมูลการจองที่ต้องการยกเลิก', $result['message']);
    }

    // Tests updateBooking succeeds when not suspended and no overlaps
    public function testUpdateBookingSuccess() {
        $bookingRepo = $this->createMock(BookingRepositoryInterface::class);
        $suspensionRepo = $this->createMock(SuspensionRepositoryInterface::class);

        $suspensionRepo->expects($this->once())
            ->method('isCarSuspended')
            ->with(1, '2026-06-01 00:00:00', '2026-06-01 23:59:59')
            ->willReturn(false);

        $bookingRepo->expects($this->once())
            ->method('getOverlappingBookings')
            ->with(1, '2026-06-01 00:00:00', '2026-06-01 23:59:59', 10)
            ->willReturn([]);

        $bookingRepo->expects($this->once())
            ->method('update')
            ->with(10, [
                'car_id' => 1,
                'start_time' => '2026-06-01 00:00:00',
                'end_time' => '2026-06-01 23:59:59',
                'employee_id' => 1,
                'purpose' => 'Meeting Updated'
            ], ['Bangkok'])
            ->willReturn(true);

        $service = new BookingService($bookingRepo, $suspensionRepo);

        $data = [
            'car_id' => 1,
            'start_time' => '2026-06-01 00:00:00',
            'end_time' => '2026-06-01 23:59:59',
            'employee_id' => 1,
            'purpose' => 'Meeting Updated'
        ];
        $provinces = ['Bangkok'];

        $result = $service->updateBooking(10, $data, $provinces);

        $this->assertTrue($result['success']);
        $this->assertEquals('แก้ไขรายละเอียดการจองเรียบร้อยแล้ว', $result['message']);
    }

    // Tests updateBooking fails when car is suspended
    public function testUpdateBookingFailsWhenSuspended() {
        $bookingRepo = $this->createMock(BookingRepositoryInterface::class);
        $suspensionRepo = $this->createMock(SuspensionRepositoryInterface::class);

        $suspensionRepo->expects($this->once())
            ->method('isCarSuspended')
            ->willReturn(true);

        $bookingRepo->expects($this->never())
            ->method('getOverlappingBookings');

        $bookingRepo->expects($this->never())
            ->method('update');

        $service = new BookingService($bookingRepo, $suspensionRepo);

        $data = [
            'car_id' => 1,
            'start_time' => '2026-06-01 00:00:00',
            'end_time' => '2026-06-01 23:59:59'
        ];

        $result = $service->updateBooking(10, $data, []);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('ถูกระงับการใช้งานชั่วคราว', $result['message']);
    }

    // Tests updateBooking fails when overlaps exist
    public function testUpdateBookingFailsOnOverlaps() {
        $bookingRepo = $this->createMock(BookingRepositoryInterface::class);
        $suspensionRepo = $this->createMock(SuspensionRepositoryInterface::class);

        $suspensionRepo->expects($this->once())
            ->method('isCarSuspended')
            ->willReturn(false);

        $bookingRepo->expects($this->once())
            ->method('getOverlappingBookings')
            ->with(1, '2026-06-01 00:00:00', '2026-06-01 23:59:59', 10)
            ->willReturn([
                [
                    'employee_name' => 'Jane Smith',
                    'start_time' => '2026-06-01 00:00:00',
                    'end_time' => '2026-06-01 23:59:59'
                ]
            ]);

        $bookingRepo->expects($this->never())
            ->method('update');

        $service = new BookingService($bookingRepo, $suspensionRepo);

        $data = [
            'car_id' => 1,
            'start_time' => '2026-06-01 00:00:00',
            'end_time' => '2026-06-01 23:59:59'
        ];

        $result = $service->updateBooking(10, $data, []);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('ทับซ้อนกับการจองที่มีอยู่แล้ว', $result['message']);
    }
}
