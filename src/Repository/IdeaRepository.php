<?php

namespace App\Repository;

use App\Entity\Idea;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Idea>
 *
 * @method Idea|null find($id, $lockMode = null, $lockVersion = null)
 * @method Idea|null findOneBy(array $criteria, array $orderBy = null)
 * @method Idea[]    findAll()
 * @method Idea[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class IdeaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Idea::class);
    }

    public function save(Idea $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Idea $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function getNumberOfPageFromFindByCriteria(array $criteria = [], int $limit = 7): int
    {
        $qb = $this->createFindByCriteriaQueryBuilder($criteria);
        $qb->select('count(i.id)');
        return ceil($qb->getQuery()->getSingleColumnResult()[0] / $limit);
    }

    public function findByCriteria(array $criteria = [], int $page = 1, int $limit = 7): array
    {
        $qb = $this->createFindByCriteriaQueryBuilder($criteria);

        $order = $criteria['order'] ?? 'DESC';
        $sort = 'i.'.($criteria['orderBy'] ?? 'updatedAt');
        $qb->addOrderBy($sort, $order);

        $firstResult = ($page - 1) * $limit;
        $qb
            ->setFirstResult($firstResult)
            ->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }

    public function createFindByCriteriaQueryBuilder(array $criteria = []): QueryBuilder
    {
        $qb = $this->createQueryBuilder('i');

        if (!empty($criteria['query'])) {
            $qb
                ->andWhere('i.content LIKE :query')
                ->setParameter('query', '%'.$criteria['query'].'%');
        }

        return $qb;
    }

//    /**
//     * @return Idea[] Returns an array of Idea objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('i')
//            ->andWhere('i.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('i.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Idea
//    {
//        return $this->createQueryBuilder('i')
//            ->andWhere('i.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
