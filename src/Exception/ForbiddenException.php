<?php
declare(strict_types=1);

namespace Ely\Mojang\Exception;

use GuzzleHttp\Exception\ClientException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class ForbiddenException extends ClientException implements MojangApiException {

    public function __construct(RequestInterface $request, ResponseInterface $response) {
        parent::__construct(
            'The request was executed with a non-existent or expired access token',
            $request,
            $response
        );
    }

}
