<?php
declare(strict_types=1);

namespace Ely\Mojang\Exception;

use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class NoContentException extends RequestException implements MojangApiException {

    public function __construct(RequestInterface $request, ResponseInterface $response) {
        parent::__construct('No data were received in the response.', $request, $response);
    }

}
