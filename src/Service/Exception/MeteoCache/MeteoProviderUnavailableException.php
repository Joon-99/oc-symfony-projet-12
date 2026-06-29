<?php

namespace App\Service\Exception\MeteoCache;

use Symfony\Component\HttpFoundation\Response;

final class MeteoProviderUnavailableException extends \RuntimeException
{
    private int $statusCode = Response::HTTP_SERVICE_UNAVAILABLE;

    public function __construct(string $message = 'Meteo provider is currently unavailable', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}
