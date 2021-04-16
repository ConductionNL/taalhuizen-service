<?php

namespace App\Repository;

use App\Entity\StudentJob;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method StudentJob|null find($id, $lockMode = null, $lockVersion = null)
 * @method StudentJob|null findOneBy(array $criteria, array $orderBy = null)
 * @method StudentJob[]    findAll()
 * @method StudentJob[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class StudentJobRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StudentJob::class);
    }

    // /**
    //  * @return StudentJob[] Returns an array of StudentJob objects
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
    public function findOneBySomeField($value): ?StudentJob
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
