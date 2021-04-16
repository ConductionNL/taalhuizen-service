<?php

namespace App\Repository;

use App\Entity\RegisterStudentRegistrar;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method RegisterStudentRegistrar|null find($id, $lockMode = null, $lockVersion = null)
 * @method RegisterStudentRegistrar|null findOneBy(array $criteria, array $orderBy = null)
 * @method RegisterStudentRegistrar[]    findAll()
 * @method RegisterStudentRegistrar[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RegisterStudentRegistrarRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RegisterStudentRegistrar::class);
    }

    // /**
    //  * @return RegisterStudentRegistrar[] Returns an array of RegisterStudentRegistrar objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('r.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?RegisterStudentRegistrar
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
