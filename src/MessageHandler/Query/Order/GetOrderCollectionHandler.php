<?php

declare(strict_types=1);

namespace App\MessageHandler\Query\Order;

use App\Contract\MessageHandler\QueryHandlerInterface;
use App\DTO\Collection\Meta\CollectionFiltersDTO;
use App\DTO\Collection\Meta\CollectionMetaDTO;
use App\DTO\Collection\Meta\CollectionPaginatorDTO;
use App\DTO\Collection\OrderCriteriaDTO;
use App\DTO\Collection\OrderGroupCollectionDTO;
use App\DTO\Entity\OrderGroupDTO;
use App\Exception\CommunicationException;
use App\Message\Query\Order\GetOrderCollectionQuery;
use App\Repository\OrderRepository;
use App\Service\Order\OrderSearchService;
use Psr\Log\LoggerInterface;

use function array_map;
use function trim;

final readonly class GetOrderCollectionHandler implements QueryHandlerInterface
{
    public function __construct(
        private OrderRepository $orderRepository,
        private OrderSearchService $orderSearchService,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(GetOrderCollectionQuery $query): OrderGroupCollectionDTO
    {
        $criteria = $this->applySearch($query->criteria);
        $result   = $this->orderRepository->countGrouped($criteria);

        return new OrderGroupCollectionDTO(
            items: array_map(
                static fn (array $row): OrderGroupDTO => new OrderGroupDTO($row['group'], $row['count']),
                $result['items'],
            ),
            meta: new CollectionMetaDTO(
                paginator: new CollectionPaginatorDTO($criteria->page, $criteria->perPage, $result['total']),
                filters: new CollectionFiltersDTO(
                    query: $criteria->query,
                    sort: $criteria->sort(),
                    availableSortBy: $criteria->availableSortBy(),
                    availableFilters: $criteria->availableFilters(),
                    appliedFilters: $criteria->appliedFilters(),
                ),
            ),
        );
    }

    /**
     * The "query" filter is resolved by Manticore (wildcard search) to a list of order ids;
     * when Manticore is not available the search falls back to MySQL LIKE.
     */
    private function applySearch(OrderCriteriaDTO $criteria): OrderCriteriaDTO
    {
        try {
            $ids = $this->orderSearchService->findIds($criteria->query);
        } catch (CommunicationException $e) {
            $this->logger->warning('Manticore is unavailable, falling back to SQL search: ' . $e->getMessage());

            $ids = trim((string) $criteria->query, " *\t") !== ''
                ? $this->orderRepository->findIdsByQuery((string) $criteria->query)
                : null;
        }

        return $criteria->withOrderIds($ids);
    }
}
