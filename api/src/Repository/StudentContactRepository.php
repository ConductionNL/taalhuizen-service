<?php

namespace App\Repository;

use App\Entity\StudentContact;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method StudentContact|null find($id, $lockMode = null, $lockVersion = null)
 * @method StudentContact|null findOneBy(array $criteria, array $orderBy = null)
 * @method StudentContact[]    findAll()
 * @method StudentContact[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class StudentContactRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StudentContact::class);
    }

    // /**
    //  * @return StudentContact[] Returns an array of StudentContact objects
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
    public function findOneBySomeField($value): ?StudentContact
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
