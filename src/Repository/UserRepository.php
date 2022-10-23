<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<User>
 *
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function save(User $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(User $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', \get_class($user)));
        }

        $user->setPassword($newHashedPassword);

        $this->add($user, true);
    }

    public function createFindByCriteriaQueryBuilder(array $criteria = []): QueryBuilder
    {
        $qb = $this->createQueryBuilder('u');

        if (!empty($criteria['notUsers'])) {
            $qb
                ->andWhere('u NOT IN (:notUsers)')
                ->setParameter('notUsers', $criteria['notUsers']);
        }

        if (!empty($criteria['query'])) {
            $qb->andWhere('(u.firstName LIKE :query OR u.lastName LIKE :query OR u.email LIKE :query)')
                ->setParameter('query', '%'.$criteria['query'].'%');
        }


        return $qb;
    }

    public function getNumberOfPageFromFindByCriteria(array $criteria = [], int $limit = 10): int
    {
        $qb = $this->createFindByCriteriaQueryBuilder($criteria);
        $qb->select('count(u.id)');
        return ceil($qb->getQuery()->getSingleColumnResult()[0] / $limit);
    }

    public function findByCriteria(array $criteria = [], int $page = 1, int $limit = 10): array
    {
        $firstResult = ($page - 1) * $limit;
        $qb = $this->createFindByCriteriaQueryBuilder($criteria);
        $qb
            ->setFirstResult($firstResult)
            ->setMaxResults($limit);

        return $qb->getQuery()->getResult();
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
