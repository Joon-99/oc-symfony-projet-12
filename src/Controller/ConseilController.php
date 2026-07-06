<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Service\Attribute\Required;
use App\Entity\Conseil;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Repository\ConseilRepository;
use Symfony\Component\Serializer\SerializerInterface;
use DateTime;
use Exception;


final class ConseilController extends AbstractController
{
    #[Required]
    public EntityManagerInterface $em;

    #[Required]
    public ConseilRepository $conseilRepository;

    #[Route(path: '/conseil', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN', message: "You're not authorized to create a conseil.")]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            return new JsonResponse(['error' => 'Invalid JSON'], Response::HTTP_BAD_REQUEST);
        }

        $months = $data['months'] ?? null;
        $content = $data['content'] ?? null;

        if ($months === null || $content === null) {
            return new JsonResponse(['error' => 'months and content are required'], Response::HTTP_BAD_REQUEST);
        }

        if (!is_array($months) || array_filter($months, fn($month) => !is_int($month) || $month < 1 || $month > 12)) {
            return new JsonResponse(['error' => 'months must be an array of integers between 1 and 12'], Response::HTTP_BAD_REQUEST);
        }

        if (empty($months)) {
            return new JsonResponse(['error' => 'months cannot be empty'], Response::HTTP_BAD_REQUEST);
        }


        $conseil  = new Conseil();
        $conseil->setMonths($months);
        $conseil->setContent($content);

        $this->em->persist($conseil);
        $this->em->flush();

        return new JsonResponse(['message' => 'Conseil created successfully'], Response::HTTP_CREATED);
    }

    #[Route(path: '/conseil/{id}', methods: ['PUT'])]
    #[IsGranted('ROLE_ADMIN', message: "You're not authorized to update this conseil.")]
    public function update(Conseil $conseil, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            return new JsonResponse(['error' => 'Invalid JSON'], Response::HTTP_BAD_REQUEST);
        }

        $months = $data['months'] ?? null;
        $content = $data['content'] ?? null;

        if ($months === null && $content === null) {
            return new JsonResponse(['error' => 'Either months or content must be provided'], Response::HTTP_BAD_REQUEST);
        }

        if ($months !== null) {
            if (!is_array($months) || array_filter($months, fn($month) => !is_int($month) || $month < 1 || $month > 12)) {
                return new JsonResponse(['error' => 'months must be an array of integers between 1 and 12'], Response::HTTP_BAD_REQUEST);
            }
            if (empty($months)) {
                return new JsonResponse(['error' => 'months cannot be empty'], Response::HTTP_BAD_REQUEST);
            }
            $conseil->setMonths($months);
        }

        if ($content !== null) {
            $conseil->setContent($content);
        }

        $this->em->flush();

        return new JsonResponse(['message' => 'Conseil updated successfully'], Response::HTTP_OK);
    }

    #[Route(path: '/conseil/{id}', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN', message: "You're not authorized to delete this conseil.")]
    public function delete(Conseil $conseil): JsonResponse
    {
        try {
            $this->em->remove($conseil);
            $this->em->flush();
        } catch (Exception $e) {
            return new JsonResponse(['error' => 'Failed to delete Conseil'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new JsonResponse(['message' => 'Conseil deleted successfully'], Response::HTTP_NO_CONTENT);
    }

    #[Route(path: '/conseil', methods: ['GET'])]
    #[IsGranted('ROLE_USER', message: "You're not authorized to view current conseils.")]
    public function list(SerializerInterface $serializer): JsonResponse
    {
        $month = Conseil::getMonthFromDate(new DateTime());
        $conseils = $this->conseilRepository->findByMonth($month);
        if (empty($conseils)) {
            return new JsonResponse(['message' => 'No conseils found for the current month'], Response::HTTP_NOT_FOUND);
        }
        $jsonConseils = $serializer->serialize($conseils, 'json', ['groups' => ['conseilPublic']]);
        return new JsonResponse($jsonConseils, Response::HTTP_OK, [], true);
    }

    #[Route(path: '/conseil/{month}', methods: ['GET'], requirements: ['month' => '\d+'])]
    #[IsGranted('ROLE_USER', message: "You're not authorized to conseils for a specific month.")]
    public function conseilByMonth(int $month, SerializerInterface $serializer): JsonResponse
    {
        if ($month < 1 || $month > 12) {
            return new JsonResponse(['error' => 'Invalid month. Month must be between 1 and 12.'], Response::HTTP_BAD_REQUEST);
        }
        $conseils = $this->conseilRepository->findByMonth($month);
        if (empty($conseils)) {
            return new JsonResponse(['message' => 'No conseils found for the specified month'], Response::HTTP_NOT_FOUND);
        }
        $jsonConseils = $serializer->serialize($conseils, 'json', ['groups' => ['conseilPublic']]);
        return new JsonResponse($jsonConseils, Response::HTTP_OK, [], true);
    }
}