<?php
namespace Tests\Unit\Controllers;

use PHPUnit\Framework\TestCase;
use App\Controllers\Public\MobileUploadController;
use App\Core\Request;
use App\Core\Response;
use App\Core\Database;

class MobileUploadControllerTest extends TestCase {
    
    protected function setUp(): void {
        parent::setUp();
        $_GET = [];
        $_POST = [];
        $_FILES = [];
    }

    protected function tearDown(): void {
        $_GET = [];
        $_POST = [];
        $_FILES = [];
        parent::tearDown();
    }

    public function testMobileUploadControllerInstantiation() {
        $controller = new MobileUploadController();
        $this->assertInstanceOf(MobileUploadController::class, $controller);
    }
    
    public function testMobileUploadControllerMethodsExist() {
        $controller = new MobileUploadController();
        $this->assertTrue(method_exists($controller, 'showUploadForm'));
        $this->assertTrue(method_exists($controller, 'checkToken'));
        $this->assertTrue(method_exists($controller, 'saveUpload'));
    }

    public function testSaveUploadMissingToken() {
        $controller = new MobileUploadController();
        $request = $this->createMock(Request::class);
        $request->method('getFiles')->willReturn([]);
        
        $response = $this->createMock(Response::class);
        $response->expects($this->once())
            ->method('json')
            ->with($this->callback(function($data) {
                return isset($data['success']) && $data['success'] === false && str_contains($data['message'], 'token');
            }));

        $controller->saveUpload($request, $response);
    }

    public function testSaveUploadNoFile() {
        $mockPdo = $this->createMock(\PDO::class);
        $mockStmt = $this->createMock(\PDOStatement::class);
        
        $mockStmt->method('execute')->willReturn(true);
        $mockStmt->method('fetch')->willReturn([
            'token' => 'valid_token',
            'expires_at' => date('Y-m-d H:i:s', strtotime('+10 minutes'))
        ]);
        
        $mockPdo->method('prepare')->willReturn($mockStmt);
        
        $reflection = new \ReflectionClass(Database::class);
        $property = $reflection->getProperty('instance');
        $property->setValue(null, $mockPdo);

        $_GET['token'] = 'valid_token';

        $controller = new MobileUploadController();
        $request = $this->createMock(Request::class);
        $request->method('getFiles')->willReturn([]);
        
        $response = $this->createMock(Response::class);
        $response->expects($this->once())
            ->method('json')
            ->with($this->callback(function($data) {
                return isset($data['success']) && $data['success'] === false && str_contains($data['message'], 'file');
            }));

        $controller->saveUpload($request, $response);

        $property->setValue(null, null); // Clean up
    }

    public function testCheckTokenInvalidToken() {
        $mockPdo = $this->createMock(\PDO::class);
        $mockStmt = $this->createMock(\PDOStatement::class);
        
        $mockStmt->method('execute')->willReturn(true);
        $mockStmt->method('fetch')->willReturn(false); // Token not found
        
        $mockPdo->method('prepare')->willReturn($mockStmt);
        
        $reflection = new \ReflectionClass(Database::class);
        $property = $reflection->getProperty('instance');
        $property->setValue(null, $mockPdo);

        $_GET['token'] = 'invalid_token';

        $controller = new MobileUploadController();
        $request = $this->createMock(Request::class);
        $response = $this->createMock(Response::class);
        
        $response->expects($this->once())
            ->method('json')
            ->with($this->callback(function($data) {
                return isset($data['success']) && $data['success'] === false && str_contains($data['message'], 'expired');
            }));

        $controller->checkToken($request, $response);

        $property->setValue(null, null); // Clean up
    }
}
