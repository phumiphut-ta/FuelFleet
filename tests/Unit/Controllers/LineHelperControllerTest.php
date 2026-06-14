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
}
