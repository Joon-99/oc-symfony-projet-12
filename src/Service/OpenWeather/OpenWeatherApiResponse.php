<?php

namespace App\Service\OpenWeather;

final class OpenWeatherApiResponse
{
    public function __construct(
        private readonly int $statusCode,
        private readonly array $payload,
    ) {
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getPayload(): array
    {
        return $this->payload;
    }
}