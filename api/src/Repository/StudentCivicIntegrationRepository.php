<?php

namespace App\Repository;

use App\Entity\StudentCivicIntegration;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method StudentCivicIntegration|null find($id, $lockMode = null, $lockVersion = null)
 * @method StudentCivicIntegration|null findOneBy(array $criteria, array $orderBy = null)
 * @method StudentCivicIntegration[]    findAll()
 * @method StudentCivicIntegration[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class StudentCivicIntegrationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StudentCivicIntegration::class);
    }

    // /**
    //  * @return StudentCivicIntegration[] Returns an array of StudentCivicIntegration objects
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
    public function findOneBySomeField($value): ?StudentCivicIntegration
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
