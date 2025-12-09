<?php

namespace App\Repository;

use App\Entity\RuleResource;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<RuleResource>
 *
 * @method RuleResource|null find($id, $lockMode = null, $lockVersion = null)
 * @method RuleResource|null findOneBy(array $criteria, array $orderBy = null)
 * @method RuleResource[]    findAll()
 * @method RuleResource[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RuleResourceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RuleResource::class);
    }

    //    /**
    //     * @return RuleResource[] Returns an array of RuleResource objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('r')
    //            ->andWhere('r.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('r.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?RuleResource
    //    {
    //        return $this->createQueryBuilder('r')
    //            ->andWhere('r.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
