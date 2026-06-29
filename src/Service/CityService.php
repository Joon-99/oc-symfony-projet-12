<?php

namespace App\Service;

use App\Entity\City;
use App\Repository\CityRepository;
use App\Service\Exception\City\CityLookupFailedException;
use App\Service\Exception\City\CityNotFoundException;
use App\Service\Exception\City\CityProviderUnavailableException;
use App\Service\OpenWeather\GeoCodingService;
use App\Service\OpenWeather\Exception\OpenWeatherApiErrorException;
use App\Service\OpenWeather\Exception\OpenWeatherApiKeyNotConfiguredException;
use App\Service\OpenWeather\Exception\OpenWeatherApiUnavailableException;
use App\Service\OpenWeather\Exception\OpenWeatherApiZipNotFoundException;

final class CityService
{
    public function __construct(private CityRepository $cityRepo, private GeoCodingService $geoCodingService)
    {
    }

    public function getCityFromZipCode(string $zipCode): City
    {
        $city = $this->cityRepo->findOneBy(['zipCode' => $zipCode]);
        if ($city) {
            return $city;
        }

        try {
            $cityData = $this->geoCodingService->geoCodeByZip($zipCode, 'FR');
        } catch (OpenWeatherApiZipNotFoundException $e) {
            throw new CityNotFoundException(previous: $e);
        } catch (OpenWeatherApiUnavailableException $e) {
            throw new CityProviderUnavailableException(previous: $e);
        } catch (OpenWeatherApiErrorException | OpenWeatherApiKeyNotConfiguredException $e) {
            throw new CityLookupFailedException(previous: $e);
        }

        $city = new City();
        $city->setName($cityData['name']);
        $city->setZipCode($cityData['zip']);
        $city->setLatitude($cityData['lat']);
        $city->setLongitude($cityData['lon']);

        return $city;
    }
}