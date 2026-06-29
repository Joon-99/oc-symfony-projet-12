<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use App\Service\Exception\City\CityNotFoundException;
use App\Service\Exception\MeteoCache\MeteoNotFoundException;
use App\Service\CityService;
use App\Service\MeteoCacheService;
use Exception;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Service\Attribute\Required;


final class MeteoController extends AbstractController
{
    #[Required]
    public CityService $cityService;
    #[Required]
    public MeteoCacheService $meteoCacheService;

    #[Route(path: '/meteo/{zipCode}', methods: ['GET'])]
    public function getMeteoByZipCode(int $zipCode): JsonResponse
    {
        $city = null;
        try {
            $city = $this->cityService->getCityFromZipCode($zipCode);
        } catch (CityNotFoundException $e) {
            return new JsonResponse(['error' => $e->getMessage()], $e->getStatusCode());
        } catch (Exception $e) {
            return new JsonResponse(['error' => "Failed to geocode zip code: {$e->getMessage()}"], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
        try {
            $meteoCache = $this->meteoCacheService->getMeteoCacheByCity($city);
        } catch (MeteoNotFoundException $e) {
            return new JsonResponse(['error' => $e->getMessage()], $e->getStatusCode());
        } catch (Exception $e) {
            return new JsonResponse(['error' => "Failed to fetch weather data: {$e->getMessage()}"], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
        return new JsonResponse([
            'city' => $city->getName(),
            'temperature' => $meteoCache->getData()['main']['temp'] ?? null,
            'wind_speed' => $meteoCache->getData()['wind']['speed'] ?? null,
        ]);
    }
}
