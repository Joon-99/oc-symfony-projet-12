<?php

namespace App\Service\Exception\MeteoCache;

use Symfony\Component\HttpFoundation\Response;

final class MeteoLookupFailedException extends \RuntimeException
{
    private int $statusCode = Response::HTTP_INTERNAL_SERVER_ERROR;

    public function __construct(string $message = 'Meteo lookup failed', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}
