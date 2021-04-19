<?php

namespace App\Repository;

use App\Entity\StudentPerson;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method StudentPerson|null find($id, $lockMode = null, $lockVersion = null)
 * @method StudentPerson|null findOneBy(array $criteria, array $orderBy = null)
 * @method StudentPerson[]    findAll()
 * @method StudentPerson[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class StudentPersonRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StudentPerson::class);
    }

    // /**
    //  * @return StudentPerson[] Returns an array of StudentPerson objects
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
    public function findOneBySomeField($value): ?StudentPerson
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
