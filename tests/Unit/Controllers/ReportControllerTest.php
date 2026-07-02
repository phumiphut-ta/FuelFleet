<?php
namespace Tests\Unit\Controllers;

use PHPUnit\Framework\TestCase;
use App\Controllers\Admin\ReportController;
use App\Core\Request;
use App\Core\Response;

class ReportControllerTest extends TestCase {
    protected function setUp(): void {
        parent::setUp();
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['admin_user'] = [
            'id' => 1,
            'username' => 'admin',
            'role' => 'admin',
            'full_name' => 'Root Admin'
        ];
    }

    protected function tearDown(): void {
        unset($_SESSION['admin_user']);
        parent::tearDown();
    }

    public function testReportControllerInstantiation() {
        $controller = new ReportController();
        $this->assertInstanceOf(ReportController::class, $controller);
    }

    public function testGenerateReport10InvalidCarRedirects() {
        $mockPdo = $this->createMock(\PDO::class);
        $mockStmt = $this->createMock(\PDOStatement::class);
        
        // Simulating that the car query returns false (car not found)
        $mockStmt->method('fetch')->willReturn(false);
        $mockStmt->method('execute')->willReturn(true);
        
        $mockPdo->method('prepare')->willReturn($mockStmt);
        
        // Inject mock PDO into Database
        $reflection = new \ReflectionClass(\App\Core\Database::class);
        $property = $reflection->getProperty('instance');
        $property->setValue(null, $mockPdo);
        
        $controller = new ReportController();
        
        $request = $this->createMock(Request::class);
        $request->method('getBody')->willReturn([
            'report_type' => '10',
            'car_id' => '999', // Non-existent car
            'month' => '05',
            'year' => '2026'
        ]);
        
        $response = $this->createMock(Response::class);
        
        // We expect the controller to redirect to '/admin/reports'
        $response->expects($this->once())
                 ->method('redirect')
                 ->with('/admin/reports');
        
        $controller->generate($request, $response);
        
        $this->assertEquals('ไม่พบข้อมูลรถยนต์ที่เลือก', $_SESSION['report_error']);
        
        // Clean up static database connection instance
        $property->setValue(null, null);
    }

    public function testGenerateReport11MissingEmployeeAlerts() {
        $mockPdo = $this->createMock(\PDO::class);
        $mockStmt = $this->createMock(\PDOStatement::class);
        $mockStmt->method("execute")->willReturn(true);
        $mockPdo->method("prepare")->willReturn($mockStmt);

        // Inject mock PDO into Database
        $reflection = new \ReflectionClass(\App\Core\Database::class);
        $property = $reflection->getProperty("instance");
        $property->setValue(null, $mockPdo);

        $controller = new ReportController();

        $request = $this->createMock(Request::class);
        $request->method("getBody")->willReturn([
            "report_type" => "11",
            "employee_id" => "",
            "month" => "05",
            "year" => "2026"
        ]);

        $response = $this->createMock(Response::class);
        $response->expects($this->once())
                 ->method("html")
                 ->with($this->stringContains("กรุณาเลือกพนักงานที่จะเปิดรายงานใบเสร็จน้ำมันจำแนกรายพนักงาน"));

        $controller->generate($request, $response);

        // Clean up static database connection instance
        $property->setValue(null, null);
    }

    public function testGenerateReport11MissingDateRangeAlerts() {
        $mockPdo = $this->createMock(\PDO::class);
        $mockStmt = $this->createMock(\PDOStatement::class);
        $mockStmt->method("execute")->willReturn(true);
        $mockPdo->method("prepare")->willReturn($mockStmt);

        // Inject mock PDO into Database
        $reflection = new \ReflectionClass(\App\Core\Database::class);
        $property = $reflection->getProperty("instance");
        $property->setValue(null, $mockPdo);

        $controller = new ReportController();

        $request = $this->createMock(Request::class);
        $request->method("getBody")->willReturn([
            "report_type" => "11",
            "employee_id" => "1",
            "start_date" => "",
            "end_date" => "",
            "month" => "05",
            "year" => "2026"
        ]);

        $response = $this->createMock(Response::class);
        $response->expects($this->once())
                 ->method("html")
                 ->with($this->stringContains("กรุณาระบุช่วงวันที่"));

        $controller->generate($request, $response);

        // Clean up static database connection instance
        $property->setValue(null, null);
    }

    public function testGenerateReport8ValidationWithInvalidDates() {
        $mockPdo = $this->createMock(\PDO::class);
        $mockStmt = $this->createMock(\PDOStatement::class);
        $mockStmt->method('execute')->willReturn(true);
        $mockPdo->method('prepare')->willReturn($mockStmt);
        $mockPdo->method('lastInsertId')->willReturn('1');

        $reflection = new \ReflectionClass(\App\Core\Database::class);
        $property = $reflection->getProperty('instance');
        $property->setValue(null, $mockPdo);

        $controller = new ReportController();

        $request = $this->createMock(Request::class);
        $request->method('getBody')->willReturn([
            'report_type' => '8',
            'start_date' => '2026-06-16',
            'end_date' => '2026-06-15'
        ]);

        $response = $this->createMock(Response::class);
        $response->expects($this->once())
                 ->method('redirect')
                 ->with('/admin/reports');

        $controller->generate($request, $response);

        $this->assertEquals('วันที่เริ่มต้น ห้ามอยู่หลังวันที่สิ้นสุด', $_SESSION['report_error']);
        unset($_SESSION['report_error']);

        $property->setValue(null, null);
    }

    public function testGenerateReport8SameDayDateRangePermitted() {
        $mockPdo = $this->createMock(\PDO::class);
        $mockStmt = $this->createMock(\PDOStatement::class);
        
        $mockStmt->method('execute')->willReturn(true);
        $mockStmt->method('fetchAll')->willReturn([]);
        $mockStmt->method('fetchColumn')->willReturn('Test Footer');
        
        $mockPdo->method('prepare')->willReturn($mockStmt);
        $mockPdo->method('query')->willReturn($mockStmt);
        $mockPdo->method('lastInsertId')->willReturn('1');
        
        $reflection = new \ReflectionClass(\App\Core\Database::class);
        $property = $reflection->getProperty('instance');
        $property->setValue(null, $mockPdo);
        
        $controller = new ReportController();
        
        $request = $this->createMock(Request::class);
        $request->method('getBody')->willReturn([
            'report_type' => '8',
            'start_date' => '2026-06-15',
            'end_date' => '2026-06-15'
        ]);
        
        $response = $this->createMock(Response::class);
        $response->expects($this->never())->method('redirect');
        $response->expects($this->never())->method('html');
        
        ob_start();
        try {
            $controller->generate($request, $response);
        } catch (\Exception $e) {
            // Ignore mPDF rendering errors in test environment if they occur
        }
        ob_end_clean();
        
        $property->setValue(null, null);
    }

    public function testGenerateReport9ValidationWithInvalidDates() {
        $mockPdo = $this->createMock(\PDO::class);
        $mockStmt = $this->createMock(\PDOStatement::class);
        $mockStmt->method('execute')->willReturn(true);
        $mockPdo->method('prepare')->willReturn($mockStmt);
        $mockPdo->method('lastInsertId')->willReturn('1');

        $reflection = new \ReflectionClass(\App\Core\Database::class);
        $property = $reflection->getProperty('instance');
        $property->setValue(null, $mockPdo);

        $controller = new ReportController();

        $request = $this->createMock(Request::class);
        $request->method('getBody')->willReturn([
            'report_type' => '9',
            'start_date' => '2026-06-16',
            'end_date' => '2026-06-15'
        ]);

        $response = $this->createMock(Response::class);
        $response->expects($this->once())
                 ->method('redirect')
                 ->with('/admin/reports');

        $controller->generate($request, $response);

        $this->assertEquals('วันที่เริ่มต้น ห้ามอยู่หลังวันที่สิ้นสุด', $_SESSION['report_error']);
        unset($_SESSION['report_error']);

        $property->setValue(null, null);
    }

    public function testGenerateReport9SameDayDateRangePermitted() {
        $mockPdo = $this->createMock(\PDO::class);
        $mockStmt = $this->createMock(\PDOStatement::class);
        
        $mockStmt->method('execute')->willReturn(true);
        $mockStmt->method('fetchAll')->willReturn([]);
        $mockStmt->method('fetchColumn')->willReturn('Test Footer');
        
        $mockPdo->method('prepare')->willReturn($mockStmt);
        $mockPdo->method('query')->willReturn($mockStmt);
        $mockPdo->method('lastInsertId')->willReturn('1');
        
        $reflection = new \ReflectionClass(\App\Core\Database::class);
        $property = $reflection->getProperty('instance');
        $property->setValue(null, $mockPdo);
        
        $controller = new ReportController();
        
        $request = $this->createMock(Request::class);
        $request->method('getBody')->willReturn([
            'report_type' => '9',
            'start_date' => '2026-06-15',
            'end_date' => '2026-06-15'
        ]);
        
        $response = $this->createMock(Response::class);
        $response->expects($this->never())->method('redirect');
        $response->expects($this->never())->method('html');
        
        ob_start();
        try {
            $controller->generate($request, $response);
        } catch (\Exception $e) {
            // Ignore mPDF rendering errors in test environment if they occur
        }
        ob_end_clean();
        
        $property->setValue(null, null);
    }

    public function testGenerateReport11ValidationWithInvalidDates() {
        $mockPdo = $this->createMock(\PDO::class);
        $mockStmt = $this->createMock(\PDOStatement::class);
        $mockStmt->method('execute')->willReturn(true);
        $mockPdo->method('prepare')->willReturn($mockStmt);
        $mockPdo->method('lastInsertId')->willReturn('1');

        $reflection = new \ReflectionClass(\App\Core\Database::class);
        $property = $reflection->getProperty('instance');
        $property->setValue(null, $mockPdo);

        $controller = new ReportController();

        $request = $this->createMock(Request::class);
        $request->method('getBody')->willReturn([
            'report_type' => '11',
            'employee_id' => '1',
            'start_date' => '2026-06-16',
            'end_date' => '2026-06-15'
        ]);

        $response = $this->createMock(Response::class);
        $response->expects($this->once())
                 ->method('html')
                 ->with($this->stringContains('วันที่เริ่มต้น ห้ามอยู่หลังวันที่สิ้นสุด!'));

        $controller->generate($request, $response);

        $property->setValue(null, null);
    }

    public function testGenerateReport11SameDayDateRangePermitted() {
        $mockPdo = $this->createMock(\PDO::class);
        $mockStmt = $this->createMock(\PDOStatement::class);
        
        $mockStmt->method('execute')->willReturn(true);
        
        // Simulating matching employee row, then empty receipts row
        $mockStmt->method('fetch')->willReturn([
            'id' => 1,
            'full_name' => 'Somchai Jaidee',
            'employee_code' => 'EMP001'
        ]);
        $mockStmt->method('fetchAll')->willReturn([]);
        $mockStmt->method('fetchColumn')->willReturn('Test Footer');
        
        $mockPdo->method('prepare')->willReturn($mockStmt);
        $mockPdo->method('query')->willReturn($mockStmt);
        $mockPdo->method('lastInsertId')->willReturn('1');
        
        $reflection = new \ReflectionClass(\App\Core\Database::class);
        $property = $reflection->getProperty('instance');
        $property->setValue(null, $mockPdo);
        
        $controller = new ReportController();
        
        $request = $this->createMock(Request::class);
        $request->method('getBody')->willReturn([
            'report_type' => '11',
            'employee_id' => '1',
            'start_date' => '2026-06-15',
            'end_date' => '2026-06-15'
        ]);
        
        $response = $this->createMock(Response::class);
        $response->expects($this->never())->method('redirect');
        $response->expects($this->never())->method('html');
        
        ob_start();
        try {
            $controller->generate($request, $response);
        } catch (\Exception $e) {
            // Ignore mPDF rendering errors in test environment if they occur
        }
        ob_end_clean();
        
        $property->setValue(null, null);
    }

    public function testGenerateReport6WithPdfAttachment() {
        $mockPdo = $this->createMock(\PDO::class);
        $mockStmt = $this->createMock(\PDOStatement::class);
        
        $mockStmt->method('execute')->willReturn(true);
        
        $mockStmt->method('fetch')->willReturn([
            'id' => 1,
            'license_plate' => 'กข 1234',
            'fuel_type' => 'Gasohol 95'
        ]);
        $mockStmt->method('fetchAll')->willReturn([
            [
                'id' => 101,
                'receipt_number' => 'REC-001',
                'receipt_date' => '2026-06-15',
                'employee_name' => 'Somchai Jaidee',
                'liters' => 30.00,
                'amount' => 1000.00,
                'fuel_type' => 'Gasohol 95',
                'file_path' => '/uploads/test.pdf'
            ]
        ]);
        $mockStmt->method('fetchColumn')->willReturn('Test Footer');
        
        $mockPdo->method('prepare')->willReturn($mockStmt);
        $mockPdo->method('query')->willReturn($mockStmt);
        $mockPdo->method('lastInsertId')->willReturn('1');
        
        $reflection = new \ReflectionClass(\App\Core\Database::class);
        $property = $reflection->getProperty('instance');
        $property->setValue(null, $mockPdo);
        
        $pdfDir = dirname(__DIR__, 3) . '/public/uploads';
        if (!is_dir($pdfDir)) {
            mkdir($pdfDir, 0777, true);
        }
        $pdfPath = $pdfDir . '/test.pdf';
        file_put_contents($pdfPath, '%PDF-1.4 dummy pdf');
        
        $controller = new ReportController();
        
        $request = $this->createMock(Request::class);
        $request->method('getBody')->willReturn([
            'report_type' => '6',
            'car_id' => '1',
            'month' => '06',
            'year' => '2026'
        ]);
        
        $response = $this->createMock(Response::class);
        $response->expects($this->never())->method('redirect');
        $response->expects($this->never())->method('html');
        
        ob_start();
        try {
            $controller->generate($request, $response);
        } catch (\Exception $e) {
            // Ignore mPDF rendering errors
        }
        $output = ob_get_clean();
        
        @unlink($pdfPath);
        $property->setValue(null, null);
        
        $this->assertNotEmpty($output);
    }

    public function testGenerateReport11WithPdfAttachment() {
        $mockPdo = $this->createMock(\PDO::class);
        $mockStmt = $this->createMock(\PDOStatement::class);
        
        $mockStmt->method('execute')->willReturn(true);
        
        $mockStmt->method('fetch')->willReturn([
            'id' => 1,
            'full_name' => 'Somchai Jaidee',
            'employee_code' => 'EMP001'
        ]);
        $mockStmt->method('fetchAll')->willReturn([
            [
                'id' => 101,
                'receipt_number' => 'REC-001',
                'receipt_date' => '2026-06-15',
                'employee_name' => 'Somchai Jaidee',
                'license_plate' => 'กข 1234',
                'liters' => 30.00,
                'amount' => 1000.00,
                'fuel_type' => 'Gasohol 95',
                'file_path' => '/uploads/test.pdf'
            ]
        ]);
        $mockStmt->method('fetchColumn')->willReturn('Test Footer');
        
        $mockPdo->method('prepare')->willReturn($mockStmt);
        $mockPdo->method('query')->willReturn($mockStmt);
        $mockPdo->method('lastInsertId')->willReturn('1');
        
        $reflection = new \ReflectionClass(\App\Core\Database::class);
        $property = $reflection->getProperty('instance');
        $property->setValue(null, $mockPdo);
        
        $pdfDir = dirname(__DIR__, 3) . '/public/uploads';
        if (!is_dir($pdfDir)) {
            mkdir($pdfDir, 0777, true);
        }
        $pdfPath = $pdfDir . '/test.pdf';
        file_put_contents($pdfPath, '%PDF-1.4 dummy pdf');
        
        $controller = new ReportController();
        
        $request = $this->createMock(Request::class);
        $request->method('getBody')->willReturn([
            'report_type' => '11',
            'employee_id' => '1',
            'start_date' => '2026-06-15',
            'end_date' => '2026-06-15'
        ]);
        
        $response = $this->createMock(Response::class);
        $response->expects($this->never())->method('redirect');
        $response->expects($this->never())->method('html');
        
        ob_start();
        try {
            $controller->generate($request, $response);
        } catch (\Exception $e) {
            // Ignore mPDF rendering errors
        }
        $output = ob_get_clean();
        
        @unlink($pdfPath);
        $property->setValue(null, null);
        
        $this->assertNotEmpty($output);
    }
}


