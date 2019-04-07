<?php
declare(strict_types=1);

namespace Ely\Mojang\Test\Response;

use Ely\Mojang\Response\ApiStatus;
use PHPUnit\Framework\TestCase;

class ApiStatusTest extends TestCase {

    public function testGetters() {
        $response = new ApiStatus('minecraft.net', 'green');
        $this->assertSame('minecraft.net', $response->getServiceName());
        $this->assertSame('green', $response->getStatus());
    }

    public function testIsGreen() {
        $response = new ApiStatus('minecraft.net', 'green');
        $this->assertTrue($response->isGreen());
        $this->assertFalse($response->isYellow());
        $this->assertFalse($response->isRed());
    }

    public function testIsYellow() {
        $response = new ApiStatus('minecraft.net', 'yellow');
        $this->assertFalse($response->isGreen());
        $this->assertTrue($response->isYellow());
        $this->assertFalse($response->isRed());
    }

    public function testIsRed() {
        $response = new ApiStatus('minecraft.net', 'red');
        $this->assertFalse($response->isGreen());
        $this->assertFalse($response->isYellow());
        $this->assertTrue($response->isRed());
    }

}
