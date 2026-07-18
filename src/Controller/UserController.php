<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\CityService;
use App\Service\Exception\City\CityNotFoundException;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Dto\UserData;
use App\Security\Voter\UserVoter;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
final class UserController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private LoggerInterface $logger,
        private UserPasswordHasherInterface $hasher,
        private CityService $cityService,
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    #[Route(path: '/user', methods: ['POST'], format: 'json')]
    public function create(#[MapRequestPayload(acceptFormat: 'json', validationFailedStatusCode: Response::HTTP_BAD_REQUEST)] UserData $userData): JsonResponse
    {
        $user = new User();

        $username = $userData->username;
        $password = $userData->password;
        $zipCode = $userData->zipCode;

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
        $user->setCity($city);

        $hashed = $this->hasher->hashPassword($user, $password);
        $user->setPassword($hashed);

        $user->setUsername($username);

        $this->em->persist($user);
        $this->em->flush();

        $location = $this->urlGenerator->generate('app_user_get', ['id' => $user->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        return new JsonResponse([
            'id' => $user->getId(),
            'username' => $user->getUsername(),
            'city' => [
                'name' => $city->getName(),
                'zipCode' => $city->getZipCode(),
            ],
        ], Response::HTTP_CREATED, ['Location' => $location]);
    }

    #[Route(path: '/user/{id}', methods: ['PUT'], format: 'json')]
    #[IsGranted(UserVoter::CAN_EDIT_USER, 'user', message: "You don't have permission to edit this user.")]
    public function update(User $user, #[MapRequestPayload(acceptFormat: 'json', validationFailedStatusCode: Response::HTTP_BAD_REQUEST)] UserData $userData): JsonResponse
    {
        $username = $userData->username;
        $password = $userData->password;
        $zipCode = $userData->zipCode;

        try {
            $city = $this->cityService->getCityFromZipCode($zipCode);
        } catch (CityNotFoundException $e) {
            return new JsonResponse(['error' => $e->getMessage()], $e->getStatusCode());
        } catch (Exception $e) {
            $this->logger->error('Error while geocoding zip code', ['exception' => $e]);
            return new JsonResponse(['error' => "Failed to geocode zip code: {$e->getMessage()}"], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $this->em->persist($city);
        $user->setCity($city);

        $hashed = $this->hasher->hashPassword($user, $password);
        $user->setPassword($hashed);

        $user->setUsername($username);

        $this->em->flush();

        /** @var User $currentUser */
        $currentUser = $this->getUser();
        $this->logger->info('User updated', ['modifier_id' => $currentUser?->getId(), 'target_user_id' => $user->getId()]);

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route(path: '/user/{id}', methods: ['DELETE'])]
    #[IsGranted(UserVoter::CAN_DELETE_USER, 'user', message: "You don't have permission to delete this user.")]
    public function delete(User $user): JsonResponse
    {
        /** @var User $currentUser */
        $currentUser = $this->getUser();
        $deleterId = $currentUser?->getId();
        $deletedUserId = $user->getId();

        try {
            $this->em->remove($user);
            $this->em->flush();
            $this->logger->info('User deleted', ['deleter_id' => $deleterId, 'deleted_user_id' => $deletedUserId]);
        } catch (Exception $e) {
            $this->logger->error('Error while deleting user', ['exception' => $e]);
            return new JsonResponse(['error' => 'Error while deleting user'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route(path: '/user/{id}', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN', message: "You're not authorized to view this user.")]
    public function get(User $user): JsonResponse
    {
        return new JsonResponse([
            'id' => $user->getId(),
            'username' => $user->getUsername(),
            'city' => [
                'name' => $user->getCity()->getName(),
                'zipCode' => $user->getCity()->getZipCode(),
            ],
        ], Response::HTTP_OK);
    }
}
