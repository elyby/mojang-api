<?php
declare(strict_types=1);

namespace Ely\Mojang\Test\Middleware;

use Ely\Mojang\Middleware\RetryMiddleware;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

class RetryMiddlewareTest extends TestCase {

    public function testShouldRetry() {
        $r = new Request('GET', 'http://localhost');
        $this->assertFalse(RetryMiddleware::shouldRetry(0, $r, new Response(200), null), 'not retry on success response');
        $this->assertFalse(RetryMiddleware::shouldRetry(0, $r, new Response(403), null), 'not retry on client error');
        $this->assertTrue(RetryMiddleware::shouldRetry(0, $r, null, new ConnectException('', $r)), 'retry when network error happens');
        $this->assertTrue(RetryMiddleware::shouldRetry(0, $r, new Response(503), null), 'retry when 50x error 1 time');
        $this->assertTrue(RetryMiddleware::shouldRetry(1, $r, new Response(503), null), 'retry when 50x error 2 time');
        $this->assertFalse(RetryMiddleware::shouldRetry(2, $r, new Response(503), null), 'don\'t retry when 50x error 3 time');
    }

}
