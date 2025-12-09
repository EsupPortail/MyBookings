<?php

namespace App\Repository;

use App\Entity\Acl;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Acl>
 *
 * @method Acl|null find($id, $lockMode = null, $lockVersion = null)
 * @method Acl|null findOneBy(array $criteria, array $orderBy = null)
 * @method Acl[]    findAll()
 * @method Acl[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AclRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Acl::class);
    }

    public function add(Acl $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Acl $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

   /**
    * @return Acl[] Returns an array of Acl objects with users
    */
   public function findUsersByService($serviceId): array
   {
       return $this->createQueryBuilder('a')
            ->andWhere('a.service = :serviceId')
            ->setParameter('serviceId', $serviceId)
            ->leftJoin('a.user', 'u')
            ->orderBy('a.id', 'ASC')
            ->getQuery()
            ->getResult()
       ;
   }

//    public function findOneBySomeField($value): ?Acl
//    {
//        return $this->createQueryBuilder('a')
//            ->andWhere('a.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
