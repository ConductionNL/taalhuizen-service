<?php

namespace App\Repository;

use App\Entity\StudentPermission;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method StudentPermission|null find($id, $lockMode = null, $lockVersion = null)
 * @method StudentPermission|null findOneBy(array $criteria, array $orderBy = null)
 * @method StudentPermission[]    findAll()
 * @method StudentPermission[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class StudentPermissionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StudentPermission::class);
    }

    // /**
    //  * @return StudentPermission[] Returns an array of StudentPermission objects
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
    public function findOneBySomeField($value): ?StudentPermission
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
