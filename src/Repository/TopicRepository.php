<?php

namespace App\Repository;

use App\Entity\Topic;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Topic>
 *
 * @method Topic|null find($id, $lockMode = null, $lockVersion = null)
 * @method Topic|null findOneBy(array $criteria, array $orderBy = null)
 * @method Topic[]    findAll()
 * @method Topic[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TopicRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Topic::class);
    }

    public function save(Topic $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Topic $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function getNumberOfPageFromFindByCriteria(array $criteria = [], int $limit = 7): int
    {
        $qb = $this->createFindByCriteriaQueryBuilder($criteria);
        $qb->select('count(t.id)');
        return ceil($qb->getQuery()->getSingleColumnResult()[0] / $limit);
    }

    public function findByCriteria(array $criteria = [], int $page = 1, int $limit = 7): array
    {
        $qb = $this->createFindByCriteriaQueryBuilder($criteria);

        $order = $criteria['order'] ?? 'DESC';
        $sort = 't.'.($criteria['orderBy'] ?? 'updatedAt');
        $qb->addOrderBy($sort, $order);
        $qb->addOrderBy('p.createdAt', 'ASC');

        $firstResult = ($page - 1) * $limit;
        $qb
            ->setFirstResult($firstResult)
            ->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }

    public function createFindByCriteriaQueryBuilder(array $criteria = []): QueryBuilder
    {
        $qb = $this
            ->createQueryBuilder('t')
            ->innerJoin('t.posts', 'p')
        ;

        if (!empty($criteria['author'])) {
            $qb->andWhere('t.author = :author')->setParameter('author', $criteria['author']);
        }

        if (!empty($criteria['query'])) {
            $qb->andWhere('(t.title LIKE :query)')->setParameter('query', '%'.$criteria['query'].'%');
        }

        return $qb;
    }

//    /**
//     * @return Topic[] Returns an array of Topic objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('t')
//            ->andWhere('t.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('t.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Topic
//    {
//        return $this->createQueryBuilder('t')
//            ->andWhere('t.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
