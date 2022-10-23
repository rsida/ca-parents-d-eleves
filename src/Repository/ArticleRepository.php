<?php

namespace App\Repository;

use App\Entity\Article;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Article>
 *
 * @method Article|null find($id, $lockMode = null, $lockVersion = null)
 * @method Article|null findOneBy(array $criteria, array $orderBy = null)
 * @method Article[]    findAll()
 * @method Article[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ArticleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Article::class);
    }

    public function save(Article $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Article $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function getNumberOfPageFromFindByCriteria(array $criteria = [], int $limit = 7): int
    {
        $qb = $this->createFindByCriteriaQueryBuilder($criteria);
        $qb->select('count(a.id)');
        return ceil($qb->getQuery()->getSingleColumnResult()[0] / $limit);
    }

    public function findByCriteria(array $criteria = [], int $page = 1, int $limit = 7): array
    {
        $qb = $this->createFindByCriteriaQueryBuilder($criteria);

        $order = $criteria['order'] ?? 'DESC';
        $sort = 'a.'.($criteria['orderBy'] ?? 'updatedAt');
        $qb->addOrderBy($sort, $order);

        $firstResult = ($page - 1) * $limit;
        $qb
            ->setFirstResult($firstResult)
            ->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }

    public function createFindByCriteriaQueryBuilder(array $criteria = []): QueryBuilder
    {
        $qb = $this->createQueryBuilder('a');

        if (!empty($criteria['author'])) {
            $qb->andWhere('a.author = :author')->setParameter('author', $criteria['author']);
        }

        if (!empty($criteria['query'])) {
            $qb->andWhere('(a.title LIKE :query OR a.description LIKE :query)')->setParameter('query', '%'.$criteria['query'].'%');
        }

        return $qb;
    }

//    /**
//     * @return Article[] Returns an array of Article objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('a')
//            ->andWhere('a.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('a.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Article
//    {
//        return $this->createQueryBuilder('a')
//            ->andWhere('a.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
