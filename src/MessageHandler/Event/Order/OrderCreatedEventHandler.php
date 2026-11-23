<?php

declare(strict_types=1);

namespace App\MessageHandler\Event\Order;

use App\Contract\MessageHandler\EventHandlerInterface;
use App\Exception\CommunicationException;
use App\Message\Event\Order\OrderCreatedEvent;
use App\Service\Order\OrderSearchIndexer;
use Psr\Log\LoggerInterface;

use function sprintf;

/**
 * Adds a new order to the Manticore index.
 *
 * The index is secondary data: when Manticore is unavailable the order stays created (the search falls back to
 * MySQL) and `bin/console app:order:reindex` repairs the index later.
 */
final readonly class OrderCreatedEventHandler implements EventHandlerInterface
{
    public function __construct(
        private OrderSearchIndexer $indexer,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(OrderCreatedEvent $event): void
    {
        try {
            $this->indexer->indexById($event->orderId);
        } catch (CommunicationException $e) {
            $this->logger->warning(sprintf('Order %d was not added to the search index: %s', $event->orderId, $e->getMessage()));
        }
    }
}
