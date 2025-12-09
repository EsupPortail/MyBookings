<?php

namespace App\Repository;

use App\Entity\WorkflowLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<WorkflowLog>
 *
 * @method WorkflowLog|null find($id, $lockMode = null, $lockVersion = null)
 * @method WorkflowLog|null findOneBy(array $criteria, array $orderBy = null)
 * @method WorkflowLog[]    findAll()
 * @method WorkflowLog[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class WorkflowLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WorkflowLog::class);
    }

    public function add(WorkflowLog $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(WorkflowLog $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findBookingCreator($bookingId)
    {
        return $this->createQueryBuilder('w')
            ->andWhere('w.booking = :bookingId')
            ->andWhere('w.statusTarget = :status')
            ->setParameter('bookingId', $bookingId)
            ->setParameter('status', 'init_booking')
            ->getQuery()
            ->getOneOrNullResult();
    }

//    /**
//     * @return WorkflowLog[] Returns an array of WorkflowLog objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('w')
//            ->andWhere('w.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('w.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }
   /**
    * @return WorkflowLog[] Returns an array of WorkflowLog objects
    */
   public function findByTransitionAndUsername($transition, $username): array
   {
       return $this->createQueryBuilder('w')
            ->andWhere('w.statusTarget = :transition')
            ->setParameter('transition', $transition)
            ->andWhere('w.comment LIKE :username')
            ->setParameter('username', '%' . $username . '%')
            ->orderBy('w.id', 'ASC')
            ->getQuery()
            ->getResult()
       ;
   }


    /**
     * @return WorkflowLog[] Returns an array of WorkflowLog objects
     */
    public function findByTransitionAndCatalogId($transition, $catalogId): array
    {
        return $this->createQueryBuilder('w')
            ->andWhere('w.statusTarget = :transition')
            ->setParameter('transition', $transition)
            ->andWhere('w.comment LIKE :catalog')
            ->setParameter('catalog', '%"catalogId":' . $catalogId . '%')
            ->orderBy('w.id', 'ASC')
            ->getQuery()
            ->getResult()
            ;
    }
//    public function findOneBySomeField($value): ?WorkflowLog
//    {
//        return $this->createQueryBuilder('w')
//            ->andWhere('w.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
