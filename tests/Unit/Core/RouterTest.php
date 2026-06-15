<?php
namespace Tests\Unit\Core;

use PHPUnit\Framework\TestCase;
use App\Core\Router;
use App\Core\Request;
use App\Core\Response;
use App\Core\Csrf;

class RouterTest extends TestCase {
    protected function setUp(): void {
        parent::setUp();
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION = [];
        $_POST = [];
    }

    protected function tearDown(): void {
        $_SESSION = [];
        $_POST = [];
        parent::tearDown();
    }

    public function testResolvePostRequestFailsWithMissingCsrf() {
        $request = $this->createMock(Request::class);
        $request->method('getPath')->willReturn('/booking/create');
        $request->method('getMethod')->willReturn('POST');

        $response = $this->createMock(Response::class);
        // Expecting setStatusCode(403) to be called
        $response->expects($this->once())
            ->method('setStatusCode')
            ->with(403);

        $router = $this->getMockBuilder(Router::class)
            ->setConstructorArgs([$request, $response])
            ->onlyMethods(['renderView'])
            ->getMock();

        // Expect renderView('error', ...)
        $router->expects($this->once())
            ->method('renderView')
            ->with('error', $this->callback(function($params) {
                return isset($params['message']) && strpos($params['message'], 'CSRF') !== false;
            }))
            ->willReturn('error-content');

        $router->resolve();
    }

    public function testResolvePostRequestSucceedsWithValidCsrf() {
        $token = Csrf::generateToken();
        $_POST['csrf_token'] = $token;

        $request = $this->createMock(Request::class);
        $request->method('getPath')->willReturn('/booking/create');
        $request->method('getMethod')->willReturn('POST');

        $response = $this->createMock(Response::class);
        $response->expects($this->never())->method('setStatusCode');

        $router = new Router($request, $response);
        $called = false;
        $router->post('/booking/create', function() use (&$called) {
            $called = true;
            return 'success';
        });

        $router->resolve();
        $this->assertTrue($called);
    }
}
