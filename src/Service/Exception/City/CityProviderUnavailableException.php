<?php

namespace App\Service\Exception\City;

use Symfony\Component\HttpFoundation\Response;

final class CityProviderUnavailableException extends \RuntimeException
{
    private int $statusCode = Response::HTTP_SERVICE_UNAVAILABLE;

    public function __construct(string $message = 'City provider is currently unavailable', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}