<?php

declare(strict_types=1);

namespace App\Repository;

use App\Contract\Repository\FindByIdInterface;
use App\Entity\Order;
use App\Repository\Traits\findOneByIdTrait;
use App\Repository\Traits\SaveRepositoryTrait;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Order>
 *
 * @method Order|null findOneById(int $id)
 * @method Order|null findOneBy(array $criteria, array $orderBy = null)
 * @method Order|null find($id, $lockMode = null, $lockVersion = null)
 * @method Order[]    findAll()
 * @method Order[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OrderRepository extends ServiceEntityRepository implements FindByIdInterface
{
    use findOneByIdTrait;
    use SaveRepositoryTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Order::class);
    }
}
