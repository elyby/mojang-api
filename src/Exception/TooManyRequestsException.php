<?php
declare(strict_types=1);

namespace Ely\Mojang\Exception;

use GuzzleHttp\Exception\ClientException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class TooManyRequestsException extends ClientException implements MojangApiException {

    public function __construct(RequestInterface $request, ResponseInterface $response) {
        parent::__construct(
            'The request limit was exceeded. ' .
            'Read the documentation for the method requested to find out which RPS is allowed.',
            $request,
            $response
        );
    }

}
