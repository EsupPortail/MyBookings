<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\Query\Parameter;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<User>
 *
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(User $entity, bool $flush = false): void
    {
        if($entity->getLastLoginAt() === null) {
            $entity->setLastLoginAt(new \DateTimeImmutable());
        }
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function remove(User $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findUserBy($value)
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.displayUserName like :val')
            ->setParameter('val', '%'.$value.'%')
            ->orderBy('u.displayUserName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function getUserLastLoginBefore($date)
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.lastLoginAt < :end')
            ->setParameters(new ArrayCollection([
                new Parameter('end', $date),
            ]))
            ->getQuery()
            ->getResult();
    }

    public function getUserLastTenMinLogin()
    {
        $date = new \DateTimeImmutable('-10 minutes');
        return $this->createQueryBuilder('u')
            ->andWhere('u.lastLoginAt >= :start')
            ->setParameters(new ArrayCollection([
                new Parameter('start', $date),
            ]))
            ->getQuery()
            ->getResult();
    }

    public function countActiveUsersSince(\DateTimeImmutable $since): int
    {
        return (int) $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->andWhere('u.lastLoginAt >= :since')
            ->setParameter('since', $since)
            ->getQuery()
            ->getSingleScalarResult();
    }

//    /**
//     * @return User[] Returns an array of User objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('u')
//            ->andWhere('u.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('u.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?User
//    {
//        return $this->createQueryBuilder('u')
//            ->andWhere('u.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
