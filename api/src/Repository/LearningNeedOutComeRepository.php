<?php

namespace App\Repository;

use App\Entity\LearningNeedOutCome;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method LearningNeedOutCome|null find($id, $lockMode = null, $lockVersion = null)
 * @method LearningNeedOutCome|null findOneBy(array $criteria, array $orderBy = null)
 * @method LearningNeedOutCome[]    findAll()
 * @method LearningNeedOutCome[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LearningNeedOutComeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LearningNeedOutCome::class);
    }

    // /**
    //  * @return LearningNeedOutCome[] Returns an array of LearningNeedOutCome objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('t.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?LearningNeedOutCome
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
