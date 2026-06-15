<?php
namespace Tests\Unit\Core;

use PHPUnit\Framework\TestCase;
use App\Core\Csrf;

class CsrfTest extends TestCase {
    protected function setUp(): void {
        parent::setUp();
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION = [];
    }

    protected function tearDown(): void {
        $_SESSION = [];
        parent::tearDown();
    }

    public function testGenerateTokenCreatesToken() {
        $token = Csrf::generateToken();
        $this->assertNotEmpty($token);
        $this->assertEquals($token, $_SESSION['csrf_token']);
    }

    public function testValidateTokenWithCorrectToken() {
        $token = Csrf::generateToken();
        $this->assertTrue(Csrf::validateToken($token));
    }

    public function testValidateTokenWithIncorrectToken() {
        Csrf::generateToken();
        $this->assertFalse(Csrf::validateToken('wrong-token'));
    }

    public function testValidateTokenWithEmptyOrNullToken() {
        Csrf::generateToken();
        $this->assertFalse(Csrf::validateToken(null));
        $this->assertFalse(Csrf::validateToken(''));
    }
}
