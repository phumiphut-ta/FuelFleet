<?php
namespace Tests\Unit\Controllers;

use PHPUnit\Framework\TestCase;
use App\Controllers\Public\CalendarController;

class CalendarControllerTest extends TestCase {
    protected function setUp(): void {
        parent::setUp();
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function testCalendarControllerInstantiation() {
        $mockPdo = $this->createMock(\PDO::class);
        
        $reflection = new \ReflectionClass(\App\Core\Database::class);
        $property = $reflection->getProperty('instance');
        $property->setValue(null, $mockPdo);

        $controller = new CalendarController();
        $this->assertInstanceOf(CalendarController::class, $controller);

        $property->setValue(null, null); // Clean up
    }
}
