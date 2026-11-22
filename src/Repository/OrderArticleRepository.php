<?php

declare(strict_types=1);

namespace App\Repository;

use App\Contract\Repository\FindByIdInterface;
use App\Entity\OrderArticle;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<OrderArticle>
 *
 * @method OrderArticle|null find($id, $lockMode = null, $lockVersion = null)
 * @method OrderArticle|null findOneBy(array $criteria, array $orderBy = null)
 * @method OrderArticle[]    findAll()
 * @method OrderArticle[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OrderArticleRepository extends ServiceEntityRepository implements FindByIdInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OrderArticle::class);
    }

    public function findOneById(int $id): ?OrderArticle
    {
        return $this->find($id);
    }
}
