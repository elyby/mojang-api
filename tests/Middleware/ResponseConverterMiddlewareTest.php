<?php
declare(strict_types=1);

namespace Ely\Mojang\Test\Middleware;

use Ely\Mojang\Exception;
use Ely\Mojang\Middleware\ResponseConverterMiddleware;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class ResponseConverterMiddlewareTest extends TestCase {

    /**
     * @param ResponseInterface $response
     * @dataProvider getResponses
     */
    public function testInvoke(RequestInterface $request, ResponseInterface $response, string $expectedException) {
        $this->expectException($expectedException);
        $handler = new MockHandler([$response]);
        $middleware = new ResponseConverterMiddleware($handler);
        $middleware($request, [])->wait();
    }

    public function getResponses(): iterable {
        yield [
            new Request('GET', 'http://localhost'),
            new Response(204, [], ''),
            Exception\NoContentException::class,
        ];

        yield [
            new Request('GET', 'http://localhost'),
            new Response(
                403,
                ['Content-Type' => 'application/json'],
                '{"error":"ForbiddenOperationException","errorMessage":"Invalid token"}'
            ),
            Exception\ForbiddenException::class,
        ];

        yield [
            new Request('GET', 'http://localhost'),
            new Response(
                429,
                ['Content-Type' => 'application/json'],
                '{"error":"TooManyRequestsException","errorMessage":"The client has sent too many requests within a certain amount of time"}'
            ),
            Exception\TooManyRequestsException::class,
        ];
    }

}
