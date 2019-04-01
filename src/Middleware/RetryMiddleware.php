<?php
declare(strict_types=1);

namespace Ely\Mojang\Middleware;

use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Middleware;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class RetryMiddleware {

    public static function create(): callable {
        return Middleware::retry([static::class, 'shouldRetry']);
    }

    public static function shouldRetry(
        int $retries,
        RequestInterface $request,
        ?ResponseInterface $response,
        ?GuzzleException $reason
    ): bool {
        if ($retries >= 2) {
            return false;
        }

        if ($reason instanceof ConnectException) {
            return true;
        }

        if ($response !== null && (int)floor($response->getStatusCode() / 100) === 5) {
            return true;
        }

        return false;
    }

}
