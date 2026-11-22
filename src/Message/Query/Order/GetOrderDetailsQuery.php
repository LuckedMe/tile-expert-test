<?php

declare(strict_types=1);

namespace App\Message\Query\Order;

/**
 * @see GetOrderDetailsHandler
 */
class GetOrderDetailsQuery
{
    public function __construct(
        public int $id,
    ) {
    }
}
