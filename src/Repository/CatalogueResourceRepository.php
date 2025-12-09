<?php

namespace App\Repository;

use App\Entity\CatalogueResource;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\Query\Parameter;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CatalogueResource>
 *
 * @method CatalogueResource|null find($id, $lockMode = null, $lockVersion = null)
 * @method CatalogueResource|null findOneBy(array $criteria, array $orderBy = null)
 * @method CatalogueResource[]    findAll()
 * @method CatalogueResource[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CatalogueResourceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CatalogueResource::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(CatalogueResource $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function remove(CatalogueResource $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findAvailableCataloguesFromDates($type, $start, $end) {
        return $this->createQueryBuilder('catalogueResource')
            ->select('catalogueResource.id')
            ->leftJoin('catalogueResource.Provisions', 'provisions')
            ->andWhere('catalogueResource.subType = :type')
            ->andWhere('(:start BETWEEN provisions.dateStart AND provisions.dateEnd) AND (:end BETWEEN provisions.dateStart AND provisions.dateEnd)')
            ->setParameters(new ArrayCollection([
                new Parameter('type', $type),
                new Parameter('start', $start),
                new Parameter('end', $end),
            ]))
            ->getQuery()
            ->getResult();
    }

    //get catalog with a single provision
    public function findCatalogWithSingleProvision() {
        return $this->createQueryBuilder('catalogueResource')
            ->leftJoin('catalogueResource.Provisions', 'provisions')
            ->groupBy('catalogueResource.id')
            ->where('provisions.periodBracket is null')
            ->having('COUNT(provisions.id) = 1')
            ->getQuery()
            ->getResult();
    }

//    /**
//     * @return CatalogueResource[] Returns an array of CatalogueResource objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('c.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?CatalogueResource
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
