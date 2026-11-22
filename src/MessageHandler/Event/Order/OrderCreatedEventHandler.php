<?php

declare(strict_types=1);

namespace App\MessageHandler\Event\Order;

use App\Contract\MessageHandler\EventHandlerInterface;
use App\Message\Event\Order\OrderCreatedEvent;
use App\Service\Order\OrderSearchIndexer;

/**
 * Adds a new order to the Manticore index.
 */
final readonly class OrderCreatedEventHandler implements EventHandlerInterface
{
    public function __construct(
        private OrderSearchIndexer $indexer,
    ) {
    }

    public function __invoke(OrderCreatedEvent $event): void
    {
        $this->indexer->indexById($event->orderId);
    }
}
