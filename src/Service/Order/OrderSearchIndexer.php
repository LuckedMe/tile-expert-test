<?php

declare(strict_types=1);

namespace App\Service\Order;

use App\Entity\Order;
use App\Repository\OrderRepository;
use App\Service\Manticore\ManticoreClient;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

use function array_map;
use function count;
use function sprintf;
use function trim;

/**
 * Keeps the Manticore "orders" real-time table in sync with MySQL.
 */
final readonly class OrderSearchIndexer
{
    public const int BATCH_SIZE = 500;

    public function __construct(
        private ManticoreClient $client,
        private OrderRepository $orderRepository,
        #[Autowire('%env(string:MANTICORE_ORDERS_TABLE)%')]
        private string $table,
    ) {
    }

    public function createTable(): void
    {
        // min_infix_len enables "*foo*" wildcard search
        $this->client->sql(sprintf(
            "CREATE TABLE IF NOT EXISTS %s (
                order_hash text, email text, name text, number text,
                client_name text, client_surname text, company_name text, description text,
                create_date timestamp
            ) min_infix_len='2' charset_table='non_cjk'",
            $this->table
        ));
    }

    public function dropTable(): void
    {
        $this->client->sql(sprintf('DROP TABLE IF EXISTS %s', $this->table));
    }

    public function indexOrder(Order $order): void
    {
        $this->client->bulkReplace($this->table, [$this->toDocument($order)]);
    }

    public function indexById(int $orderId): void
    {
        if ($order = $this->orderRepository->findOneById($orderId)) {
            $this->indexOrder($order);
        }
    }

    /**
     * @param callable(int): void|null $progress receives the number of indexed orders so far
     */
    public function reindexAll(?callable $progress = null): int
    {
        $this->dropTable();
        $this->createTable();

        $total  = 0;
        $lastId = 0;

        while (true) {
            /** @var Order[] $orders */
            $orders = $this->orderRepository->createQueryBuilder('o')
                ->where('o.id > :last')
                ->setParameter('last', $lastId)
                ->orderBy('o.id', 'ASC')
                ->setMaxResults(self::BATCH_SIZE)
                ->getQuery()
                ->getResult();

            if (!$orders) {
                return $total;
            }

            $this->client->bulkReplace($this->table, array_map($this->toDocument(...), $orders));

            $total += count($orders);
            $lastId = $orders[array_key_last($orders)]->getId();
            $progress?->__invoke($total);

            $this->orderRepository->getEntityManager()->clear();
        }
    }

    /**
     * @return array{id: int, doc: array<string, mixed>}
     */
    private function toDocument(Order $order): array
    {
        return [
            'id'  => $order->getId(),
            'doc' => [
                'order_hash'     => (string) $order->getHash(),
                'email'          => (string) $order->getEmail(),
                'name'           => (string) $order->getName(),
                'number'         => (string) $order->getNumber(),
                'client_name'    => (string) $order->getClientName(),
                'client_surname' => (string) $order->getClientSurname(),
                'company_name'   => (string) $order->getCompanyName(),
                'description'    => trim((string) $order->getDescription()),
                'create_date'    => $order->getCreateDate()->getTimestamp(),
            ],
        ];
    }
}
