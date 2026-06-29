<?php

namespace App\Service\OpenWeather;

use App\Service\OpenWeather\Exception\OpenWeatherApiKeyNotConfiguredException;
use App\Service\OpenWeather\Exception\OpenWeatherApiUnavailableException;
use App\Service\OpenWeather\OpenWeatherApiResponse;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

final class OpenWeatherApiClient
{
    private const BASE_URL = 'https://api.openweathermap.org';

    public function __construct(private HttpClientInterface $httpClient, private LoggerInterface $logger)
    {
    }

    public function get(string $path, array $query = []): OpenWeatherApiResponse
    {
        $apiKey = $_ENV['OPENWEATHER_API_KEY'] ?? getenv('OPENWEATHER_API_KEY');
        if (!$apiKey) {
            throw new OpenWeatherApiKeyNotConfiguredException();
        }

        try {
            $response = $this->httpClient->request('GET', self::BASE_URL . $path, [
                'query' => [
                    ...$query,
                    'appid' => $apiKey,
                ],
                'timeout' => 10,
            ]);

            return new OpenWeatherApiResponse(
                $response->getStatusCode(),
                $response->toArray(false),
            );
        } catch (TransportExceptionInterface $e) {
            $this->logger->error('Error while making request to OpenWeather API', ['exception' => $e]);
            throw new OpenWeatherApiUnavailableException(previous: $e);
        }
    }
}