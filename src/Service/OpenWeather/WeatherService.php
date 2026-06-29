<?php

namespace App\Service\OpenWeather;

use App\Service\OpenWeather\Exception\OpenWeatherApiErrorException;
use App\Service\OpenWeather\Exception\OpenWeatherApiZipNotFoundException;

final class WeatherService
{
    public function __construct(private OpenWeatherApiClient $openWeatherApiClient)
    {
    }

    public function getWeatherByCoordinates(float $lat, float $lon): array
    {
        $response = $this->openWeatherApiClient->get('/data/2.5/weather', [
            'lat' => $lat,
            'lon' => $lon,
            'units' => 'metric',
            'lang' => 'fr',
        ]);

        $status = $response->getStatusCode();
        $data = $response->getPayload();

        if ($status >= 400) {
            $message = $data['message'] ?? 'unknown error from OpenWeather';
            if ($status === 404) {
                throw new OpenWeatherApiZipNotFoundException("No weather data found for coordinates {$lat}, {$lon}", $status);
            }

            throw new OpenWeatherApiErrorException("OpenWeather returned {$status}: {$message}", $status);
        }

        return $data;
    }
}
