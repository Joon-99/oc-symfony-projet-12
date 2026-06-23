<?php

namespace App\Service;

use App\Entity\City;
use App\Repository\CityRepository;
use App\Service\GeoCodingService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final class CityService
{
    public function __construct(private CityRepository $cityRepo, private GeoCodingService $geoCodingService)
    {
    }

    public function getCityFromZipCode(string $zipCode): City|Response
    {
        $city = $this->cityRepo->findOneBy(['zipCode' => $zipCode]);
        if (!$city) {
            try {
                $cityData = $this->geoCodingService->geoCodeByZip($zipCode, 'FR');
            } catch (\Exception $e) {
                if ($e->getCode() === 404) {
                    return new JsonResponse(['error' => "No city found at zip code: $zipCode"], Response::HTTP_NOT_FOUND);
                }
                return new JsonResponse(['error' => "Failed to geocode zip code: {$e->getMessage()}"], Response::HTTP_BAD_REQUEST);
            }
            $city = new City();
            $city->setName($cityData['name']);
            $city->setZipCode($cityData['zip']);
            $city->setLatitude($cityData['lat']);
            $city->setLongitude($cityData['lon']);
        }
        return $city;
    }
}
