<?php

namespace App\Repository;

use App\Entity\StudentReferrer;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method StudentReferrer|null find($id, $lockMode = null, $lockVersion = null)
 * @method StudentReferrer|null findOneBy(array $criteria, array $orderBy = null)
 * @method StudentReferrer[]    findAll()
 * @method StudentReferrer[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class StudentReferrerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StudentReferrer::class);
    }

    // /**
    //  * @return StudentReferrer[] Returns an array of StudentReferrer objects
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
    public function findOneBySomeField($value): ?StudentReferrer
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
