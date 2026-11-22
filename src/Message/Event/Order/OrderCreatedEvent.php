<?php

declare(strict_types=1);

namespace App\Message\Event\Order;

use App\Contract\Message\LowPriorityAsyncMessageInterface;

final readonly class OrderCreatedEvent implements LowPriorityAsyncMessageInterface
{
    public function __construct(
        public int $orderId,
    ) {
    }
}
