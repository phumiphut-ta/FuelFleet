<?php
namespace Tests\Unit\Controllers;

use PHPUnit\Framework\TestCase;
use App\Controllers\Admin\DiscordSettingsController;

class DiscordSettingsControllerTest extends TestCase {
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

    public function testDiscordSettingsControllerInstantiation() {
        $controller = new DiscordSettingsController();
        $this->assertInstanceOf(DiscordSettingsController::class, $controller);
    }

    public function testDiscordSettingsControllerIndexWithMockDatabase() {
        $mockPdo = $this->createMock(\PDO::class);
        $mockStmt = $this->createMock(\PDOStatement::class);
        
        $mockStmt->method('execute')->willReturn(true);
        $mockStmt->method('fetchColumn')->willReturn(json_encode([
            'channels' => [
                'booking_alerts' => [
                    'webhook_url' => 'https://discord.com/api/webhooks/test',
                    'topics' => ['new_booking' => '1', 'cancel_booking' => '0']
                ]
            ]
        ]));

        $mockPdo->method('prepare')->willReturn($mockStmt);
        
        // Use reflection to inject mock PDO into Database
        $reflection = new \ReflectionClass(\App\Core\Database::class);
        $property = $reflection->getProperty('instance');
        $property->setValue(null, $mockPdo);
        
        $controller = new DiscordSettingsController();
        $request = $this->createMock(\App\Core\Request::class);
        $response = $this->createMock(\App\Core\Response::class);
        
        ob_start();
        $controller->index($request, $response);
        $output = ob_get_clean();
        
        $this->assertNotEmpty($output);
        $this->assertStringContainsString('ตั้งค่าการแจ้งเตือน Discord Webhook', $output);
        
        // Clean up static database connection instance
        $property->setValue(null, null);
    }

    public function testDiscordSettingsControllerSave() {
        $mockPdo = $this->createMock(\PDO::class);
        $mockStmt = $this->createMock(\PDOStatement::class);
        
        $mockStmt->method('execute')->willReturn(true);
        $mockPdo->method('prepare')->willReturn($mockStmt);
        
        $reflection = new \ReflectionClass(\App\Core\Database::class);
        $property = $reflection->getProperty('instance');
        $property->setValue(null, $mockPdo);
        
        $controller = new DiscordSettingsController();
        
        $request = $this->createMock(\App\Core\Request::class);
        $request->method('getBody')->willReturn([
            'channels' => [
                'booking_alerts' => [
                    'webhook_url' => 'https://discord.com/api/webhooks/123',
                    'topics' => [
                        'new_booking' => '1',
                        'cancel_booking' => '0'
                    ]
                ]
            ]
        ]);
        
        $response = $this->createMock(\App\Core\Response::class);
        $response->expects($this->once())
            ->method('redirect')
            ->with($this->equalTo('/admin/discord-settings'));
            
        $controller->save($request, $response);
        
        $this->assertNotEmpty($_SESSION['discord_success']);
        
        $property->setValue(null, null);
    }
}
