<?php

namespace App\Repository;

use App\Entity\StudentGeneral;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method StudentGeneral|null find($id, $lockMode = null, $lockVersion = null)
 * @method StudentGeneral|null findOneBy(array $criteria, array $orderBy = null)
 * @method StudentGeneral[]    findAll()
 * @method StudentGeneral[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class StudentGeneralRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StudentGeneral::class);
    }

    // /**
    //  * @return StudentGeneral[] Returns an array of StudentGeneral objects
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
    public function findOneBySomeField($value): ?StudentGeneral
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
