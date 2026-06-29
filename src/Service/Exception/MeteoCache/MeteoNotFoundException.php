<?php

namespace App\Service\Exception\MeteoCache;

use Symfony\Component\HttpFoundation\Response;

final class MeteoNotFoundException extends \RuntimeException
{
    private int $statusCode = Response::HTTP_NOT_FOUND;

    public function __construct(string $message = 'No meteo data found for the requested city', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}
