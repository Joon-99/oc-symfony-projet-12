<?php

namespace App\Service;

use App\Entity\City;
use App\Entity\MeteoCache;
use App\Repository\MeteoCacheRepository;
use App\Service\Exception\MeteoCache\MeteoLookupFailedException;
use App\Service\Exception\MeteoCache\MeteoNotFoundException;
use App\Service\Exception\MeteoCache\MeteoProviderUnavailableException;
use App\Service\OpenWeather\Exception\OpenWeatherApiErrorException;
use App\Service\OpenWeather\Exception\OpenWeatherApiKeyNotConfiguredException;
use App\Service\OpenWeather\Exception\OpenWeatherApiUnavailableException;
use App\Service\OpenWeather\Exception\OpenWeatherApiZipNotFoundException;
use App\Service\OpenWeather\WeatherService;
use Symfony\Contracts\Service\Attribute\Required;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use DateTimeImmutable;
use DateInterval;
use Psr\Log\LoggerInterface;

final class MeteoCacheService
{
    #[Required]
    public MeteoCacheRepository $meteoRepo;
    #[Required]
    public WeatherService $weatherService;
    #[Required]
    public EntityManagerInterface $em;
    #[Required]
    public LoggerInterface $logger;

    public function getMeteoCacheByCity(City $city): MeteoCache
    {
        $meteoCache = $this->meteoRepo->findOneBy(['city' => $city]);
        if ($meteoCache) {
            return $meteoCache;
        }
        try {
            $meteoData = $this->weatherService->getWeatherByCoordinates($city->getLatitude(), $city->getLongitude());
        } catch (OpenWeatherApiZipNotFoundException $e) {
            throw new MeteoNotFoundException("No weather data found for city {$city->getName()}", previous: $e);
        } catch (OpenWeatherApiUnavailableException $e) {
            throw new MeteoProviderUnavailableException(previous: $e);
        } catch (OpenWeatherApiErrorException | OpenWeatherApiKeyNotConfiguredException $e) {
            throw new MeteoLookupFailedException(previous: $e);
        }
        $meteoCache = new MeteoCache();
        $meteoCache->setCity($city);
        $meteoCache->setData($meteoData);
        $meteoCache->setFetchedAt(new DateTimeImmutable());
        // Set the expiration time for the cached data (e.g., 1 hour from now)
        $meteoCache->setExpiresAt((new DateTimeImmutable())->add(DateInterval::createFromDateString( '1 hour' )));
        try {
            $this->em->persist($meteoCache);
            $this->em->flush();
        } catch (Exception $e) {
            throw new Exception("Failed to save meteo cache for city {$city->getName()}: {$e->getMessage()}", previous: $e);
        }
        return $meteoCache;
    }
}
