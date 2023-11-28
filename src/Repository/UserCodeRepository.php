<?php

namespace App\Repository;

use App\Entity\UserCode;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method UserCode|null find($id, $lockMode = null, $lockVersion = null)
 * @method UserCode|null findOneBy(array $criteria, array $orderBy = null)
 * @method UserCode[]    findAll()
 * @method UserCode[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserCodeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserCode::class);
    }
}
