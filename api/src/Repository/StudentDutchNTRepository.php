<?php

namespace App\Repository;

use App\Entity\StudentDutchNT;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method StudentDutchNT|null find($id, $lockMode = null, $lockVersion = null)
 * @method StudentDutchNT|null findOneBy(array $criteria, array $orderBy = null)
 * @method StudentDutchNT[]    findAll()
 * @method StudentDutchNT[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class StudentDutchNTRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StudentDutchNT::class);
    }

    // /**
    //  * @return StudentDutchNT[] Returns an array of StudentDutchNT objects
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
    public function findOneBySomeField($value): ?StudentDutchNT
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
