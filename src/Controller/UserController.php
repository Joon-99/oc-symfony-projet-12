<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\CityService;
use App\Service\Exception\City\CityNotFoundException;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

final class UserController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private LoggerInterface $logger,
        private UserPasswordHasherInterface $hasher,
        private UserRepository $userRepository,
        private CityService $cityService,
    ) {
    }

    //TODO validation through forms or serializer 

    #[Route(path: '/user', methods: ['POST'])]
    public function create(Request $request): Response
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
        if ($this->userRepository->findOneBy(['username' => $username])) {
            return new JsonResponse(['error' => 'username already exists'], Response::HTTP_BAD_REQUEST);
        }

        // requires zipCode
        if (!$zipCode) {
            return new JsonResponse(['error' => 'zipCode is required'], Response::HTTP_BAD_REQUEST);
        }

        // find or create city
        try {
            $city = $this->cityService->getCityFromZipCode($zipCode);
        } catch (CityNotFoundException $e) {
            return new JsonResponse(['error' => $e->getMessage()], $e->getStatusCode());
        } catch (Exception $e) {
            $this->logger->error('Error while geocoding zip code', ['exception' => $e]);
            return new JsonResponse(['error' => "Failed to geocode zip code: {$e->getMessage()}"], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $this->em->persist($city);

        $user = new User();
        $user->setUsername($username);
        $hashed = $this->hasher->hashPassword($user, $password);
        $user->setPassword($hashed);
        $user->setCity($city);

        $this->em->persist($user);
        $this->em->flush();

        return new JsonResponse([
            'id' => $user->getId(),
            'username' => $user->getUsername(),
            'city' => [
                'name' => $city->getName(),
                'zipCode' => $city->getZipCode(),
            ],
        ], Response::HTTP_CREATED);
    }

    #[Route(path: '/user/{id}', methods: ['PUT'])]
    public function update(int $id, Request $request): Response
    {
        $user = $this->userRepository->find($id);
        if (!$user) {
            return new JsonResponse(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            return new JsonResponse(['error' => 'Invalid JSON'], Response::HTTP_BAD_REQUEST);
        }

        $username = $data['username'] ?? null;
        $password = $data['password'] ?? null;
        $zipCode = $data['zipCode'] ?? null;

        if (!$username && !$password && !$zipCode) {
            return new JsonResponse(['error' => 'Expecting these values: username, password, or zipCode'], Response::HTTP_BAD_REQUEST);
        }

        if ($zipCode) {
            try {
                $city = $this->cityService->getCityFromZipCode($zipCode);
            } catch (CityNotFoundException $e) {
                return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
            } catch (Exception $e) {
                $this->logger->error('Error while geocoding zip code', ['exception' => $e]);
                return new JsonResponse(['error' => "Failed to geocode zip code: {$e->getMessage()}"], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            $this->em->persist($city);
            $user->setCity($city);
        }

        if ($username) {
            $existingUser = $this->userRepository->findOneBy(['username' => $username]);
            if ($existingUser && $existingUser->getId() !== $user->getId()) {
                return new JsonResponse(['error' => 'username already exists'], Response::HTTP_BAD_REQUEST);
            }
            $user->setUsername($username);
        }

        if ($password) {
            $hashed = $this->hasher->hashPassword($user, $password);
            $user->setPassword($hashed);
        }

        $this->em->flush();

        return new JsonResponse([
            'id' => $user->getId(), // for consequent put requests
            'username' => $user->getUsername(),
            'city' => [
                'name' => $user->getCity()->getName(),
                'zipCode' => $user->getCity()->getZipCode(),
            ],
        ], Response::HTTP_OK);
    }

    #[Route(path: '/user/{id}', methods: ['DELETE'])]
    public function delete(int $id): Response
    {
        $user = $this->userRepository->find($id);
        if (!$user) {
            return new JsonResponse(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        try {
            $this->em->remove($user);
            $this->em->flush();
        } catch (Exception $e) {
            $this->logger->error('Error while deleting user', ['exception' => $e]);
            return new JsonResponse(['error' => 'Error while deleting user'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new JsonResponse(null, Response::HTTP_OK);
    }
}
