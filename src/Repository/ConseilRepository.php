<?php

namespace App\Repository;

use App\Entity\Conseil;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ConseilRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Conseil::class);
    }

    /**
     * @return Conseil[]
     */
    public function findByMonth(int $month): array
    {
        $conseils = $this->findAll();
        $conseilsForMonth = array_filter($conseils, static fn(Conseil $conseil): bool => in_array($month, $conseil->getMonths(), true));
        return array_values($conseilsForMonth);
    }
}
