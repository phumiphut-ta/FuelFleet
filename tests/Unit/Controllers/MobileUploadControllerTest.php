<?php
namespace Tests\Unit\Controllers;

use PHPUnit\Framework\TestCase;
use App\Controllers\Public\MobileUploadController;

class MobileUploadControllerTest extends TestCase {
    
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
}
