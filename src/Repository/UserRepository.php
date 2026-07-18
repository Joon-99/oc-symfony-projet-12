<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Find users by username, excluding a specific ID if provided.
     * @param list<string, string> $fields
     * @return User[]
     */
    public function findByUsernameField(array $fields): array
    {
        $username = $fields['username'] ?? null;
        $id = $fields['id'] ?? null;
        
        if (!$username) {
            return [];
        }
        
        $qb = $this->createQueryBuilder('u')
            ->where('u.username = :username')
            ->setParameter('username', $username);
        
        // for example when updating an existing user, we want to exclude that user from the search
        if ($id !== null) {
            $qb->andWhere('u.id != :id')
               ->setParameter('id', $id);
        }
        
        return $qb->getQuery()->getResult();
    }

}
