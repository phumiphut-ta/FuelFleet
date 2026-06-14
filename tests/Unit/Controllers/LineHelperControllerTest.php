<?php
namespace Tests\Unit\Controllers;

use PHPUnit\Framework\TestCase;
use App\Controllers\Admin\LineHelperController;

class LineHelperControllerTest extends TestCase {
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

    public function testLineHelperControllerInstantiation() {
        $controller = new LineHelperController();
        $this->assertInstanceOf(LineHelperController::class, $controller);
    }

    public function testLineHelperControllerIndexWithMockDatabase() {
        $mockPdo = $this->createMock(\PDO::class);
        $mockStmt1 = $this->createMock(\PDOStatement::class);
        
        $mockStmt1->method('fetchAll')->willReturn([
            [
                'id' => 1,
                'license_plate' => 'กข-1234',
                'fuel_type' => 'Diesel',
                'status' => 'Active',
                'threshold' => 20.00,
                'quota_liters' => 300.00,
                'used_liters' => 290.00
            ],
            [
                'id' => 2,
                'license_plate' => 'มค-5678',
                'fuel_type' => 'Gasohol 95',
                'status' => 'Active',
                'threshold' => 10.00,
                'quota_liters' => 200.00,
                'used_liters' => 100.00
            ]
        ]);
        
        $mockStmt2 = $this->createMock(\PDOStatement::class);
        $mockStmt2->method('execute')->willReturn(true);
        $mockStmt2->method('fetchColumn')->willReturn('📢 อัปเดตโควต้าน้ำมัน (ประจำวันที่ {date})\n\n{vehicle_list}\n\nโควต้ารถ มค-5678: {used:มค-5678}/{quota:มค-5678} คงเหลือ {remaining:มค-5678}');

        $mockPdo->method('query')->willReturn($mockStmt1);
        $mockPdo->method('prepare')->willReturn($mockStmt2);
        
        // Use reflection to inject mock PDO into Database
        $reflection = new \ReflectionClass(\App\Core\Database::class);
        $property = $reflection->getProperty('instance');
        $property->setValue(null, $mockPdo);
        
        $controller = new LineHelperController();
        $request = $this->createMock(\App\Core\Request::class);
        $response = $this->createMock(\App\Core\Response::class);
        
        ob_start();
        $controller->index($request, $response);
        $output = ob_get_clean();
        
        $this->assertNotEmpty($output);
        // Clean up static database connection instance
        $property->setValue(null, null);
    }
}
