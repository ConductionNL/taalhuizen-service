<?php

namespace App\Repository;

use App\Entity\StudentEducation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method StudentEducation|null find($id, $lockMode = null, $lockVersion = null)
 * @method StudentEducation|null findOneBy(array $criteria, array $orderBy = null)
 * @method StudentEducation[]    findAll()
 * @method StudentEducation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class StudentEducationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StudentEducation::class);
    }

    // /**
    //  * @return StudentEducation[] Returns an array of StudentEducation objects
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
    public function findOneBySomeField($value): ?StudentEducation
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
