<?php

namespace App\Repository;

use App\Entity\Comment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Comment>
 *
 * @method Comment|null find($id, $lockMode = null, $lockVersion = null)
 * @method Comment|null findOneBy(array $criteria, array $orderBy = null)
 * @method Comment[]    findAll()
 * @method Comment[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CommentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Comment::class);
    }

    public function save(Comment $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Comment $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findCommentPaginated(int $trick, int $page, int $limit = 2): array
    {
        $limit = abs($limit);

        $result = [];
        $query = $this->getEntityManager()->createQueryBuilder()
            ->select('c')
            ->from('App\Entity\Comment', 'c')
            ->where(sprintf('c.trick = %s', $trick))
            ->setMaxResults($limit)
            ->setFirstResult(($page * $limit) - $limit)
        ;
        $paginator = new Paginator($query);
        $data = $paginator->getQuery()->getResult();
        // On vérifie la presence de données
        if (empty($data)) {
            return $result;
        }

        // On calcule le nb de pages
        $pages = ceil($paginator->count() / $limit);

        // On remplie le tableau
        $result['data'] = $data;
        $result['page'] = $page;
        $result['pages'] = $pages;
        $result['limit'] = $limit;

        return $result;
    }
}
