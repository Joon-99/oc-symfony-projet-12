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
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Service\Attribute\Required;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Entity\User;


final class MeteoController extends AbstractController
{
    #[Required]
    public CityService $cityService;
    #[Required]
    public MeteoCacheService $meteoCacheService;
    #[Required]
    public EntityManagerInterface $em;

    #[Route(path: '/meteo/{zipCode}', methods: ['GET'], requirements: ['zipCode' => '\d+'])]
    #[IsGranted('ROLE_USER', message: "You're not authorized to access meteo data.")]
    public function getMeteoByZipCode(string $zipCode): JsonResponse
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
            $this->em->persist($city);
            $this->em->flush();
        } catch (Exception $e) {
            return new JsonResponse(['error' => "Failed to register the city: {$e->getMessage()}"], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        try {
            $meteoCache = $this->meteoCacheService->getMeteoCacheByCity($city);
        } catch (MeteoNotFoundException $e) {
            return new JsonResponse(['error' => $e->getMessage()], $e->getStatusCode());
        } catch (Exception $e) {
            return new JsonResponse(['error' => "Failed to fetch weather data: {$e->getMessage()}"], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new JsonResponse([
            'meteoData' => $meteoCache->getData(),
        ]);
    }

    #[Route(path: '/meteo', methods: ['GET'])]
    #[IsGranted('ROLE_USER', message: "You're not authorized to access meteo data.")]
    public function getMeteoForCurrentUser(): JsonResponse
    {
        /** @var User $currentUser */
        $currentUser = $this->getUser();
        $city = $currentUser->getCity();

        if (!$city) {
            return new JsonResponse(['error' => 'Current user does not have a city.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        try {
            $meteoCache = $this->meteoCacheService->getMeteoCacheByCity($city);
        } catch (MeteoNotFoundException $e) {
            return new JsonResponse(['error' => $e->getMessage()], $e->getStatusCode());
        } catch (Exception $e) {
            return new JsonResponse(['error' => "Failed to fetch weather data: {$e->getMessage()}"], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new JsonResponse([
            'meteoData' => $meteoCache->getData(),
        ]);
    }
}
