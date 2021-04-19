<?php

namespace App\Repository;

use App\Entity\StudentBackground;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method StudentBackground|null find($id, $lockMode = null, $lockVersion = null)
 * @method StudentBackground|null findOneBy(array $criteria, array $orderBy = null)
 * @method StudentBackground[]    findAll()
 * @method StudentBackground[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class StudentBackgroundRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StudentBackground::class);
    }

    // /**
    //  * @return StudentBackground[] Returns an array of StudentBackground objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('s.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?StudentBackground
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
