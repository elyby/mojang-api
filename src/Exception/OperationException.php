<?php
declare(strict_types=1);

namespace Ely\Mojang\Exception;

use GuzzleHttp\Exception\ClientException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class OperationException extends ClientException implements MojangApiException {

    public function __construct(string $message, RequestInterface $request, ResponseInterface $response) {
        parent::__construct($message, $request, $response);
    }

}
