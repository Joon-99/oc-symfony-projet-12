<?php

namespace App\Service\Exception\City;

use Symfony\Component\HttpFoundation\Response;

final class CityNotFoundException extends \RuntimeException
{
    private int $statusCode = Response::HTTP_NOT_FOUND;

    public function __construct(string $message = 'No city found for the requested zip code', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}