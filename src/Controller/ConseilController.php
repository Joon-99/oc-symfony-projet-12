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
}