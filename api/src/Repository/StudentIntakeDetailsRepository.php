<?php

namespace App\Repository;

use App\Entity\StudentIntakeDetails;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method StudentIntakeDetails|null find($id, $lockMode = null, $lockVersion = null)
 * @method StudentIntakeDetails|null findOneBy(array $criteria, array $orderBy = null)
 * @method StudentIntakeDetails[]    findAll()
 * @method StudentIntakeDetails[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class StudentIntakeDetailsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StudentIntakeDetails::class);
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
