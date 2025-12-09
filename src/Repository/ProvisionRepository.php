<?php

namespace App\Repository;

use App\Entity\Provision;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\Query\Parameter;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Provision>
 *
 * @method Provision|null find($id, $lockMode = null, $lockVersion = null)
 * @method Provision|null findOneBy(array $criteria, array $orderBy = null)
 * @method Provision[]    findAll()
 * @method Provision[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProvisionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Provision::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(Provision $entity, bool $flush = false): void
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
    public function remove(Provision $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
    * @return Provision[] Returns an array of Provision objects
    */
    public function findCatalogueFromDate($type, $start, $end, $day1, $day2): array
    {

        return $this->createQueryBuilder('provision')
            //Vérificaition du type de catalogue
            ->leftJoin('provision.catalogueResource', 'catalogue')
            ->andWhere('catalogue.subType = :type')
            //On vérifie que le catalogue est compris dans l'heure d'ouverture du catalogue
            ->andWhere('(:start BETWEEN provision.dateStart AND provision.dateEnd) AND (:end BETWEEN provision.dateStart AND provision.dateEnd)')
            //Vérification si la date de début et de fin entrent dans l'intervalle de réservation
            ->andWhere('(:start BETWEEN provision.minBookingTime AND provision.maxBookingTime) AND (:end BETWEEN provision.minBookingTime AND provision.maxBookingTime)')

            //Vérification des jours de réservation
            ->andWhere("JSON_SEARCH(provision.days, 'one', :day1) is not null AND JSON_SEARCH(provision.days, 'one', :day2) is not null")
            ->setParameters(new ArrayCollection([
                new Parameter('type', $type),
                new Parameter('start', $start),
                new Parameter('end', $end),
                new Parameter('day1', $day1),
                new Parameter('day2', $day2),
            ]))
            ->getQuery()
            ->getResult();
    }

//    /**
//     * @return Provision[] Returns an array of Provision objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('p.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Provision
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
