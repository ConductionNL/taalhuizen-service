<?php

namespace App\Repository;

use App\Entity\CurrentEducationYes;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method CurrentEducationYes|null find($id, $lockMode = null, $lockVersion = null)
 * @method CurrentEducationYes|null findOneBy(array $criteria, array $orderBy = null)
 * @method CurrentEducationYes[]    findAll()
 * @method CurrentEducationYes[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CurrentEducationYesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CurrentEducationYes::class);
    }

    // /**
    //  * @return CurrentEducationYes[] Returns an array of CurrentEducationYes objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('c.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?CurrentEducationYes
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
