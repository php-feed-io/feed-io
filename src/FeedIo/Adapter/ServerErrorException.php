<?php

declare(strict_types=1);

namespace FeedIo\Adapter;

use Psr\Http\Message\ResponseInterface;

class ServerErrorException extends HttpRequestException
{
    public function __construct(
        protected ResponseInterface $response,
        float $duration = 0
    ) {
        $statusCode = $response->getStatusCode();
        $reasonPhrase = trim($response->getReasonPhrase());
        $message = sprintf(
            'Server responded with: %d%s',
            $statusCode,
            $reasonPhrase !== '' ? ' ' . $reasonPhrase : ''
        );

        parent::__construct($message, $duration);
    }

    public function getResponse(): ResponseInterface
    {
        return $this->response;
    }
}
