<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;

class GeoCodingService
{
    public function __construct(private HttpClientInterface $httpClient)
    {
    }

    /**
     * Geocodes a zip code via OpenWeather Geo API.
     * Returns associative array on success.
     *
     * @throws HttpException on upstream error
     */
    public function geoCodeByZip(string $zip, string $country): array
    {
        $apiKey = $_ENV['OPENWEATHER_API_KEY'] ?? getenv('OPENWEATHER_API_KEY');
        if (!$apiKey) {
            throw new HttpException(500, 'OpenWeather API key not configured (OPENWEATHER_API_KEY)');
        }

        $queryZip = "{$zip},{$country}";

        try {
            $response = $this->httpClient->request('GET', 'https://api.openweathermap.org/geo/1.0/zip', [
                'query' => [
                    'zip' => $queryZip,
                    'appid' => $apiKey,
                ],
                'timeout' => 10,
            ]);

            $status = $response->getStatusCode();
            $content = $response->getContent(false);
            $data = json_decode($content, true);

            if ($status >= 400) {
                $message = $data['message'] ?? 'unknown error from OpenWeather';
                throw new HttpException($status, 'OpenWeather error: ' . $message);
            }

            return $data;
        } catch (\Exception $e) {
            throw new HttpException(502, 'Failed to contact OpenWeather API: ' . $e->getMessage());
        }
    }
}
