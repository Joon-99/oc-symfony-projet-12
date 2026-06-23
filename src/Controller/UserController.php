<?php

namespace App\Controller;

use App\Entity\City;
use App\Entity\User;
use App\Repository\CityRepository;
use App\Repository\UserRepository;
use App\Service\GeoCodingService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class UserController extends AbstractController
{
    #[Route(path: '/user', methods: ['POST'])]
    public function create(Request $request,
        EntityManagerInterface $em, CityRepository $cityRepo, UserRepository $userRepo,
        UserPasswordHasherInterface $hasher, GeoCodingService $geoCodingService): Response
    {
        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            return new JsonResponse(['error' => 'Invalid JSON'], Response::HTTP_BAD_REQUEST);
        }

        $username = $data['username'] ?? null;
        $password = $data['password'] ?? null;
        $zipCode = $data['zipCode'] ?? null;

        if (!$username || !$password) {
            return new JsonResponse(['error' => 'username and password are required'], Response::HTTP_BAD_REQUEST);
        }

        // ensure username unique
        if ($userRepo->findOneBy(['username' => $username])) {
            return new JsonResponse(['error' => 'username already exists'], Response::HTTP_BAD_REQUEST);
        }

        // requires zipCode
        if (!$zipCode) {
            return new JsonResponse(['error' => 'zipCode is required'], Response::HTTP_BAD_REQUEST);
        }

        // find or create city
        $city = null;
        if ($zipCode) {
            $city = $cityRepo->findOneBy(['zipCode' => $zipCode]);
        }
        if (!$city) {
            $city = new City();
            try {
                $cityData = $geoCodingService->geoCodeByZip($zipCode, 'FR');
            } catch (\Exception $e) {
                if ($e->getCode() === 404) {
                    return new JsonResponse(['error' => 'No city found at zip code : ' . $zipCode], Response::HTTP_NOT_FOUND);
                }
                return new JsonResponse(['error' => 'Failed to geocode zip code: ' . $e->getMessage()], Response::HTTP_BAD_REQUEST);
            }
            $city->setName($cityData['name']);
            $city->setZipCode($cityData['zip']);
            $city->setLatitude($cityData['lat']);
            $city->setLongitude($cityData['lon']);
            // country defaults to FR for now
            $em->persist($city);
        }

        $user = new User();
        $user->setUsername($username);
        $hashed = $hasher->hashPassword($user, $password);
        $user->setPassword($hashed);
        $user->setCity($city);

        $em->persist($user);
        $em->flush();

        return new JsonResponse([
            'id' => $user->getId(),
            'username' => $user->getUsername(),
            'city' => [
                'id' => $city->getId(),
                'name' => $city->getName(),
                'zipCode' => $city->getZipCode(),
            ],
        ], Response::HTTP_CREATED);
    }

}
