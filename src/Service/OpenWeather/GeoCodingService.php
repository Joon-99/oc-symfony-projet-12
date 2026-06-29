<?php

namespace App\Service\OpenWeather;

use App\Service\OpenWeather\Exception\OpenWeatherApiErrorException;
use App\Service\OpenWeather\Exception\OpenWeatherApiZipNotFoundException;
use App\Service\OpenWeather\OpenWeatherApiClient;

final class GeoCodingService
{
    public function __construct(private OpenWeatherApiClient $openWeatherApiClient)
    {
    }

    public function geoCodeByZip(string $zip, string $country): array
    {
        $response = $this->openWeatherApiClient->get('/geo/1.0/zip', [
            'zip' => "{$zip},{$country}",
        ]);

        $status = $response->getStatusCode();
        $data = $response->getPayload();

        if ($status >= 400) {
            $message = $data['message'] ?? 'unknown error from OpenWeather';
            if ($status === 404) {
                throw new OpenWeatherApiZipNotFoundException("No city found for zip code {$zip}", $status);
            }

            throw new OpenWeatherApiErrorException("OpenWeather returned {$status}: {$message}", $status);
        }

        return $data;
    }
}