<?php

namespace App\Repository;

use App\Entity\Booking;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\Query\Parameter;
use Doctrine\Persistence\ManagerRegistry;
use Eluceo\iCal\Domain\ValueObject\DateTime;

/**
 * @extends ServiceEntityRepository<Booking>
 *
 * @method Booking|null find($id, $lockMode = null, $lockVersion = null)
 * @method Booking|null findOneBy(array $criteria, array $orderBy = null)
 * @method Booking[]    findAll()
 * @method Booking[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BookingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Booking::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(Booking $entity, bool $flush = false): void
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
    public function remove(Booking $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @return Booking[] Returns an array of Booking objects
     */
    public function findAcceptedToStart($today) {
        return $this->createQueryBuilder('b')
            ->where("b.status = 'accepted'")
            ->andWhere('b.dateStart < :val')
            ->setParameter('val', $today)
            ->andWhere('b.dateEnd > :val')
            ->setParameter('val', $today)
            ->getQuery()
            ->getResult();
    }

    public function findInit() {
        return $this->createQueryBuilder('b')
            ->where("b.status = 'init'")
            ->getQuery()
            ->getResult();
    }

    public function findStartedToEnd($today) : array {
        return $this->createQueryBuilder('b')
            ->where("b.status = 'progress'")
            ->andWhere('b.dateEnd < :val')
            ->setParameter('val', $today)
            ->getQuery()
            ->getResult();
    }

    public function findAcceptedToEnd($today) {
        return $this->createQueryBuilder('b')
            ->where("b.status = 'accepted'")
            ->andWhere('b.dateEnd < :val')
            ->setParameter('val', $today)
            ->getQuery()
            ->getResult();
    }

    public function findBookResource($resourceId, $dateStart, $dateEnd) {
        return $this->createQueryBuilder('booking')
            ->select('booking.id')
            ->leftJoin('booking.Resource', 'resource')
            ->where('resource.id = :resourceId')
            ->andWhere('(booking.dateStart >= :start AND booking.dateStart < :end) OR (booking.dateEnd > :start AND booking.dateEnd <= :end) OR (booking.dateStart < :start AND booking.dateEnd > :end)')
            ->setParameters(new ArrayCollection([
                new Parameter('resourceId', $resourceId),
                new Parameter('start', $dateStart),
                new Parameter('end', $dateEnd),
            ]))
            ->getQuery()
            ->getResult();
    }

    public function findBookByCatalog($catalog, $dateStart) {
        return $this->createQueryBuilder('b')
            ->leftJoin('b.catalogueResource', 'c')
            ->leftJoin('b.Resource', 'r')
            ->where('c.id = :catalogId')
            ->andWhere('b.dateStart >= :start or b.dateEnd >= :start')
            ->setParameters(new ArrayCollection([
                new Parameter('catalogId', $catalog),
                new Parameter('start', $dateStart),
            ]))
            ->getQuery()
            ->getResult();
    }

    public function getBookings($dateStart, $dateEnd)
    {
        return $this->createQueryBuilder('booking')
            ->select('booking.id')
            ->andWhere('(booking.dateStart BETWEEN :start AND :end)')
            ->setParameters(new ArrayCollection([
                new Parameter('start', $dateStart),
                new Parameter('end', $dateEnd),
            ]))
            ->getQuery()
            ->getResult();
    }

    public function getBookingsBefore($dateEnd)
    {
        return $this->createQueryBuilder('booking')
            ->andWhere('(booking.dateEnd < :end)')
            ->setParameters(new ArrayCollection([
                new Parameter('end', $dateEnd),
            ]))
            ->getQuery()
            ->getResult();
    }

    public function findByUser(User $user, $dateStart, $dateEnd, $catalog)
    {
        return $this->createQueryBuilder('b')
            ->join('b.user', 'u')
            ->where(':user MEMBER OF b.user')
            ->andWhere('b.catalogueResource = :catalog')
            ->andWhere('(b.dateStart >= :start AND b.dateStart < :end) OR (b.dateEnd > :start AND b.dateEnd <= :end) OR (b.dateStart < :start AND b.dateEnd > :end)')
            ->setParameters(new ArrayCollection([
                new Parameter('start', $dateStart),
                new Parameter('end', $dateEnd),
                new Parameter('user', $user),
                new Parameter('catalog', $catalog),
            ]))
            ->getQuery()
            ->getResult();
    }

    public function findBookingByCatalogResource($catalogId, $status, $intervalStart, $intervalEnd)
    {
        return $this->createQueryBuilder('b')
            ->select('b.dateStart, b.dateEnd', 'r.id') // Seulement besoin des dates de début et de fin
            ->leftJoin('b.Resource', 'r')
            ->join('b.catalogueResource', 'c')
            ->andWhere('b.status IN (:status)')
            ->andWhere('c.id = :catalogueId')
            // Condition de chevauchement avec l'intervalle global demandé
            ->andWhere('b.dateStart < :dateEnd AND b.dateEnd > :dateStart')
            ->setParameter('catalogueId', $catalogId)
            ->setParameter('status', $status)
            ->setParameter('dateStart', $intervalStart) // Utiliser les objets DateTime pour les paramètres
            ->setParameter('dateEnd', $intervalEnd)
            ->getQuery()->getResult();
    }

    public function findBookingByResource($resourceId, $status, $intervalStart, $intervalEnd)
    {
        return $this->createQueryBuilder('b')
            ->select('b.dateStart, b.dateEnd', '1 as resource_count') // Seulement besoin des dates de début et de fin
            ->join('b.Resource', 'r')
            ->andWhere('b.status IN (:status)')
            ->andWhere('r.id = :resourceId')
            // Condition de chevauchement avec l'intervalle global demandé
            ->andWhere('b.dateStart < :dateEnd AND b.dateEnd > :dateStart')
            ->setParameter('resourceId', $resourceId)
            ->setParameter('status', $status)
            ->setParameter('dateStart', $intervalStart) // Utiliser les objets DateTime pour les paramètres
            ->setParameter('dateEnd', $intervalEnd)
            ->getQuery()->getResult();
    }

//    /**
//     * @return Booking[] Returns an array of Booking objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('b')
//            ->andWhere('b.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('b.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Booking
//    {
//        return $this->createQueryBuilder('b')
//            ->andWhere('b.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
