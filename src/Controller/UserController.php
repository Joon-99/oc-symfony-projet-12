<?php

namespace App\Controller;

use App\Entity\City;
use App\Entity\User;
use App\Repository\CityRepository;
use App\Repository\UserRepository;
use App\Service\CityService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class UserController extends AbstractController
{

    //TODO validation through forms or serializer 

    #[Route(path: '/user', methods: ['POST'])]
    public function create(Request $request,
        EntityManagerInterface $em, CityRepository $cityRepo, UserRepository $userRepo,
        UserPasswordHasherInterface $hasher, CityService $cityService): Response
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
        $cityOrResponse = $cityService->getCityFromZipCode($zipCode);
        if ($cityOrResponse instanceof Response) {
            return $cityOrResponse;
        }
        $city = $cityOrResponse;
        $em->persist($city);

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
                'name' => $city->getName(),
                'zipCode' => $city->getZipCode(),
            ],
        ], Response::HTTP_CREATED);
    }

    #[Route(path: '/user/{id}', methods: ['PUT'])]
    public function update(int $id, Request $request,
        UserPasswordHasherInterface $hasher, EntityManagerInterface $em, UserRepository $userRepo, CityRepository $cityRepo,
        CityService $cityService): Response
    {
        $user = $userRepo->find($id);
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
            $cityOrResponse = $cityService->getCityFromZipCode($zipCode);
            if ($cityOrResponse instanceof Response) {
                return $cityOrResponse;
            }
            $city = $cityOrResponse;
            $em->persist($city);
            $user->setCity($city);
        }

        if ($username) {
            $existingUser = $userRepo->findOneBy(['username' => $username]);
            if ($existingUser) {
                return new JsonResponse(['error' => 'username already exists'], Response::HTTP_BAD_REQUEST);
            }
            $user->setUsername($username);
        }

        if ($password) {
            $hashed = $hasher->hashPassword($user, $password);
            $user->setPassword($hashed);
        }

        $em->flush();

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
    public function delete(int $id, EntityManagerInterface $em, UserRepository $userRepo): Response
    {
        $user = $userRepo->find($id);
        if (!$user) {
            return new JsonResponse(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        $em->remove($user);
        $em->flush();

        return new JsonResponse(null, Response::HTTP_OK);
    }
}
