<?php

namespace App\Controller;

use App\Dto\ConseilData;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Service\Attribute\Required;
use App\Entity\Conseil;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Repository\ConseilRepository;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
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
    public function create(
        #[MapRequestPayload(
            acceptFormat: 'json',
            validationGroups: [ConseilData::GROUP_CREATE],
            validationFailedStatusCode: Response::HTTP_BAD_REQUEST,
        )]
        ConseilData $data
    ): JsonResponse
    {
        $conseil  = new Conseil();
        $conseil->setMonths($data->months ?? []);
        $conseil->setContent($data->content ?? '');

        $this->em->persist($conseil);
        $this->em->flush();

        return new JsonResponse(['message' => 'Conseil created successfully'], Response::HTTP_CREATED);
    }

    #[Route(path: '/conseil/{id}', methods: ['PUT'])]
    #[IsGranted('ROLE_ADMIN', message: "You're not authorized to update this conseil.")]
    public function update(
        Conseil $conseil,
        #[MapRequestPayload(
            acceptFormat: 'json',
            validationGroups: [ConseilData::GROUP_UPDATE],
            validationFailedStatusCode: Response::HTTP_BAD_REQUEST,
        )]
        ConseilData $data
    ): JsonResponse
    {
        if ($data->months !== null) {
            $conseil->setMonths($data->months);
        }

        if ($data->content !== null) {
            $conseil->setContent($data->content);
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

    #[Route(path: '/conseil/{month}', methods: ['GET'], defaults: ['month' => null], requirements: ['month' => '\d+'])]
    #[IsGranted('ROLE_USER', message: "You're not authorized to view conseils.")]
    public function list(?int $month, SerializerInterface $serializer): JsonResponse
    {
        $month ??= Conseil::getMonthFromDate(new DateTime());

        if ($month < 1 || $month > 12) {
            return new JsonResponse(['error' => 'Invalid month. Month must be between 1 and 12.'], Response::HTTP_BAD_REQUEST);
        }

        $conseils = $this->conseilRepository->findByMonth($month);
        if (empty($conseils)) {
            $monthName = Conseil::getMonthName($month);
            return new JsonResponse(['message' => "No conseils found for $monthName"], Response::HTTP_NOT_FOUND);
        }

        $jsonConseils = $serializer->serialize($conseils, 'json', ['groups' => ['conseilPublic']]);

        return new JsonResponse($jsonConseils, Response::HTTP_OK, [], true);
    }
}