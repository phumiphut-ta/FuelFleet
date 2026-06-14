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
}
