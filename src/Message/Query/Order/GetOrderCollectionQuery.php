<?php

declare(strict_types=1);

namespace App\Message\Query\Order;

use App\DTO\Collection\OrderCriteriaDTO;

/**
 * @see GetOrderCollectionHandler
 */
readonly class GetOrderCollectionQuery
{
    public function __construct(
        public OrderCriteriaDTO $criteria,
    ) {
    }
}
