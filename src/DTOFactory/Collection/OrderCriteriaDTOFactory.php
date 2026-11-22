<?php

declare(strict_types=1);

namespace App\DTOFactory\Collection;

use App\Contract\Dictionary\OrderGroupBy;
use App\ControllerRequest\Api\Order\GetOrderCollectionRequest;
use App\DTO\Collection\OrderCriteriaDTO;

use function array_key_exists;

class OrderCriteriaDTOFactory
{
    public function fromCollectionRequest(GetOrderCollectionRequest $request): OrderCriteriaDTO
    {
        return new OrderCriteriaDTO(
            groupBy: OrderGroupBy::tryFrom((string) $request->groupBy) ?? OrderGroupBy::Day,
            page: $request->page ?? GetOrderCollectionRequest::DEFAULT_PAGE,
            perPage: $request->perPage ?? GetOrderCollectionRequest::DEFAULT_PER_PAGE,
            query: $request->query,
            startDay: $request->startDay,
            endDay: $request->endDay,
            orderBy: array_key_exists((string) $request->orderBy, OrderCriteriaDTO::SORT_BY)
                ? $request->orderBy
                : OrderCriteriaDTO::SORT_GROUP,
            orderDirection: $request->orderDirection,
        );
    }
}
