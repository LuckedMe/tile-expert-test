<?php

declare(strict_types=1);

namespace App\Repository;

use App\Contract\Repository\FindByIdInterface;
use App\DTO\Collection\OrderCriteriaDTO;
use App\Entity\Order;
use App\Repository\Traits\findOneByIdTrait;
use App\Repository\Traits\SaveRepositoryTrait;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\Persistence\ManagerRegistry;

use function addcslashes;
use function array_map;
use function implode;
use function preg_split;
use function sprintf;
use function trim;

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

    /**
     * Number of orders grouped by day / month / year (create_date), paginated by groups.
     *
     * @return array{items: list<array{group: string, count: int}>, total: int}
     */
    public function countGrouped(OrderCriteriaDTO $criteria): array
    {
        $connection = $this->getEntityManager()->getConnection();
        $format     = $criteria->groupBy->sqlFormat(); // trusted enum constant
        $params     = [];
        $types      = [];
        $where      = [];

        if ($criteria->startDay) {
            $where[]            = 'create_date >= :startDay';
            $params['startDay'] = $criteria->startDay;
        }

        if ($criteria->endDay) {
            $where[]          = 'create_date < DATE_ADD(:endDay, INTERVAL 1 DAY)';
            $params['endDay'] = $criteria->endDay;
        }

        if ($criteria->orderIds !== null) {
            if ($criteria->orderIds) {
                $where[]            = 'id IN (:orderIds)';
                $params['orderIds'] = $criteria->orderIds;
                $types['orderIds']  = ArrayParameterType::INTEGER;
            } else {
                $where[] = '1 = 0';
            }
        }

        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        $grouped  = sprintf(
            "SELECT DATE_FORMAT(create_date, '%s') AS grp, COUNT(*) AS cnt FROM orders %s GROUP BY grp",
            $format,
            $whereSql,
        );

        $total = (int) $connection->fetchOne(sprintf('SELECT COUNT(*) FROM (%s) g', $grouped), $params, $types);

        $direction = $criteria->orderDirection === 'asc' ? 'ASC' : 'DESC';
        $rows      = $connection->fetchAllAssociative(
            sprintf(
                '%s ORDER BY %s %s, grp DESC LIMIT %d OFFSET %d',
                $grouped,
                $criteria->sqlOrderBy(),
                $direction,
                $criteria->perPage,
                ($criteria->page - 1) * $criteria->perPage,
            ),
            $params,
            $types,
        );

        return [
            'items' => array_map(
                static fn (array $row): array => ['group' => (string) $row['grp'], 'count' => (int) $row['cnt']],
                $rows,
            ),
            'total' => $total,
        ];
    }

    /**
     * Fallback of the Manticore search: every term of the query must be found (LIKE %term%)
     * in one of the searchable columns.
     *
     * @return int[]
     */
    public function findIdsByQuery(string $query, int $limit = 10000): array
    {
        $qb = $this->createQueryBuilder('o')->select('o.id');

        foreach (preg_split('/\s+/', trim($query)) ?: [] as $i => $term) {
            $term = trim($term, '*');

            if ($term === '') {
                continue;
            }

            $qb->andWhere(sprintf(
                'o.hash LIKE :t%1$d OR o.email LIKE :t%1$d OR o.name LIKE :t%1$d OR o.number LIKE :t%1$d'
                . ' OR o.clientName LIKE :t%1$d OR o.clientSurname LIKE :t%1$d OR o.companyName LIKE :t%1$d'
                . ' OR o.description LIKE :t%1$d',
                $i
            ))->setParameter('t' . $i, '%' . addcslashes($term, '%_\\') . '%');
        }

        return array_map('intval', $qb->setMaxResults($limit)->getQuery()->getSingleColumnResult());
    }
}
