<?php

declare(strict_types=1);

namespace App\Tests\Unit\MessageHandler;

use App\Entity\Order;
use App\Message\Event\Order\OrderCreatedEvent;
use App\MessageHandler\Event\Order\OrderCreatedEventHandler;
use App\Repository\OrderRepository;
use App\Service\Manticore\ManticoreClient;
use App\Service\Order\OrderSearchIndexer;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\HttpClient\MockHttpClient;

final class OrderCreatedEventHandlerTest extends TestCase
{
    public function testManticoreOutageDoesNotFailTheOrder(): void
    {
        $order = new Order()
            ->setHash('abc')
            ->setEmail('john@example.com')
            ->setName('Order')
            ->setCreateDate(new DateTimeImmutable('2026-01-01'));
        new \ReflectionProperty(Order::class, 'id')->setValue($order, 7);

        $repository = $this->createStub(OrderRepository::class);
        $repository->method('findOneById')->willReturn($order);

        $indexer = new OrderSearchIndexer(
            new ManticoreClient(new MockHttpClient(static fn () => throw new TransportException('connection refused')), 'http://manticore:9308'),
            $repository,
            'orders_test',
        );

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())->method('warning')->with(self::stringContains('Order 7 was not added to the search index'));

        new OrderCreatedEventHandler($indexer, $logger)(new OrderCreatedEvent(7));
    }
}
