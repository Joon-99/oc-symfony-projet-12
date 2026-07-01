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
use Exception;


final class ConseilController extends AbstractController
{
    #[Required]
    public EntityManagerInterface $em;

    #[Route(path: '/conseil', methods: ['POST'])]
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
    public function update(int $id, Request $request): JsonResponse
    {
        $conseil = $this->em->getRepository(Conseil::class)->find($id);

        if (!$conseil) {
            return new JsonResponse(['error' => 'Conseil not found'], Response::HTTP_NOT_FOUND);
        }

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
    public function delete(int $id): JsonResponse
    {
        $conseil = $this->em->getRepository(Conseil::class)->find($id);

        if (!$conseil) {
            return new JsonResponse(['error' => 'Conseil not found'], Response::HTTP_NOT_FOUND);
        }

        try {
            $this->em->remove($conseil);
            $this->em->flush();
        } catch (Exception $e) {
            return new JsonResponse(['error' => 'Failed to delete Conseil'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new JsonResponse(['message' => 'Conseil deleted successfully'], Response::HTTP_OK);
    }
}