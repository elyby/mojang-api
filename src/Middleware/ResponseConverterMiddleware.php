<?php
declare(strict_types=1);

namespace Ely\Mojang\Middleware;

use Ely\Mojang\Exception;
use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class ResponseConverterMiddleware {

    /**
     * @var callable
     */
    private $nextHandler;

    public function __construct(callable $nextHandler) {
        $this->nextHandler = $nextHandler;
    }

    public function __invoke(RequestInterface $request, array $options): PromiseInterface {
        $fn = $this->nextHandler;
        /** @var PromiseInterface $promise */
        $promise = $fn($request, $options);

        return $promise->then(static function($response) use ($request) {
            if ($response instanceof ResponseInterface) {
                $method = $request->getMethod();
                $statusCode = $response->getStatusCode();
                if ($method === 'GET' && $statusCode === 204) {
                    throw new Exception\NoContentException($request, $response);
                }

                if ($statusCode === 403) {
                    throw new Exception\ForbiddenException($request, $response);
                }

                if ($statusCode === 429) {
                    throw new Exception\TooManyRequestsException($request, $response);
                }
            }

            return $response;
        });
    }

    public static function create(): callable {
        return static function(callable $handler): callable {
            return new static($handler);
        };
    }

}
