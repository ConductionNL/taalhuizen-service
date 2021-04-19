<?php

namespace App\Repository;

use App\Entity\CurrentEducationNoButDidFollow;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method CurrentEducationNoButDidFollow|null find($id, $lockMode = null, $lockVersion = null)
 * @method CurrentEducationNoButDidFollow|null findOneBy(array $criteria, array $orderBy = null)
 * @method CurrentEducationNoButDidFollow[]    findAll()
 * @method CurrentEducationNoButDidFollow[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CurrentEducationNoButDidFollowRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CurrentEducationNoButDidFollow::class);
    }

    // /**
    //  * @return CurrentEducationNoButDidFollow[] Returns an array of CurrentEducationNoButDidFollow objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('c.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?CurrentEducationNoButDidFollow
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
