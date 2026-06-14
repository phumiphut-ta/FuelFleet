<?php
namespace Tests\Unit\Controllers;

use PHPUnit\Framework\TestCase;
use App\Controllers\Admin\AdminUserController;
use App\Repositories\Interfaces\AdminUserRepositoryInterface;
use App\Core\Request;
use App\Core\Response;

class AdminUserControllerTest extends TestCase {
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

    public function testAdminUserControllerInstantiation() {
        $mockRepo = $this->createMock(AdminUserRepositoryInterface::class);
        $controller = new AdminUserController($mockRepo);
        $this->assertInstanceOf(AdminUserController::class, $controller);
    }

    public function testEditRootAdminBlocksAccess() {
        $mockRepo = $this->createMock(AdminUserRepositoryInterface::class);
        
        $controller = new AdminUserController($mockRepo);
        
        $request = $this->createMock(Request::class);
        $response = $this->createMock(Response::class);
        
        $response->expects($this->once())
            ->method('redirect')
            ->with('/admin/users');
            
        $controller->edit($request, $response, 1);
        $this->assertEquals('ไม่สามารถแก้ไขผู้ดูแลระบบหลักของระบบได้', $_SESSION['user_error']);
    }

    public function testUpdateRootAdminBlocksAccess() {
        $mockRepo = $this->createMock(AdminUserRepositoryInterface::class);
        
        $controller = new AdminUserController($mockRepo);
        
        $request = $this->createMock(Request::class);
        $response = $this->createMock(Response::class);
        
        $response->expects($this->once())
            ->method('redirect')
            ->with('/admin/users');
            
        $controller->update($request, $response, 1);
        $this->assertEquals('ไม่สามารถแก้ไขผู้ดูแลระบบหลักของระบบได้', $_SESSION['user_error']);
    }

    public function testDeleteRootAdminBlocksAccess() {
        $mockRepo = $this->createMock(AdminUserRepositoryInterface::class);
        
        $controller = new AdminUserController($mockRepo);
        
        $request = $this->createMock(Request::class);
        $response = $this->createMock(Response::class);
        
        $response->expects($this->once())
            ->method('redirect')
            ->with('/admin/users');
            
        $controller->delete($request, $response, 1);
        $this->assertEquals('ไม่สามารถลบผู้ดูแลระบบหลักของระบบได้', $_SESSION['user_error']);
    }
}
