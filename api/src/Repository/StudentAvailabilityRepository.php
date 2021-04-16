<?php

namespace App\Repository;

use App\Entity\StudentAvailability;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method StudentAvailability|null find($id, $lockMode = null, $lockVersion = null)
 * @method StudentAvailability|null findOneBy(array $criteria, array $orderBy = null)
 * @method StudentAvailability[]    findAll()
 * @method StudentAvailability[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class StudentAvailabilityRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StudentAvailability::class);
    }

    // /**
    //  * @return StudentAvailability[] Returns an array of StudentAvailability objects
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
    public function findOneBySomeField($value): ?StudentAvailability
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
