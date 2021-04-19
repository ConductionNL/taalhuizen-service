<?php

namespace App\Repository;

use App\Entity\LearningNeed;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method LearningNeed|null find($id, $lockMode = null, $lockVersion = null)
 * @method LearningNeed|null findOneBy(array $criteria, array $orderBy = null)
 * @method LearningNeed[]    findAll()
 * @method LearningNeed[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LearningNeedRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LearningNeed::class);
    }

    // /**
    //  * @return LearningNeed[] Returns an array of LearningNeed objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('l.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?LearningNeed
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
