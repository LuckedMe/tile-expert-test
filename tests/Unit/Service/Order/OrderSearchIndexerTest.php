<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Order;

use App\Entity\Order;
use App\Exception\CommunicationException;
use App\Repository\OrderRepository;
use App\Service\Manticore\ManticoreClient;
use App\Service\Order\OrderSearchIndexer;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

use function json_decode;
use function str_contains;

final class OrderSearchIndexerTest extends TestCase
{
    public function testIndexesAnOrderWithOneBulkRequest(): void
    {
        $requests = [];
        $indexer  = $this->indexer(function (string $method, string $url, array $options) use (&$requests): MockResponse {
            $requests[] = [$url, $options['body'] ?? ''];

            return new MockResponse('{"items":[],"errors":false}');
        });

        $indexer->indexOrder($this->order());

        self::assertCount(1, $requests);
        self::assertSame('http://manticore:9308/bulk', $requests[0][0]);

        $line = json_decode($requests[0][1], true);
        self::assertSame('orders_test', $line['replace']['table']);
        self::assertSame(7, $line['replace']['id']);
        self::assertSame('john@example.com', $line['replace']['doc']['email']);
        self::assertSame('abc', $line['replace']['doc']['order_hash']);
    }

    public function testCreatesTheMissingIndexAndRetries(): void
    {
        $urls    = [];
        $indexer = $this->indexer(function (string $method, string $url, array $options) use (&$urls): MockResponse {
            $urls[] = $url;

            // the first bulk fails: the table does not exist yet; CREATE TABLE and the second bulk succeed
            return 1 === \count($urls)
                ? new MockResponse('{"error":"table \'orders_test\' absent"}', ['http_code' => 500])
                : new MockResponse('{"items":[],"errors":false}');
        });

        $indexer->indexOrder($this->order());

        self::assertSame([
            'http://manticore:9308/bulk',
            'http://manticore:9308/sql?mode=raw',
            'http://manticore:9308/bulk',
        ], $urls);
    }

    public function testGivesUpWhenManticoreIsDown(): void
    {
        $indexer = $this->indexer(static fn () => throw new TransportException('connection refused'));

        $this->expectException(CommunicationException::class);

        $indexer->indexOrder($this->order());
    }

    private function indexer(callable $responses): OrderSearchIndexer
    {
        return new OrderSearchIndexer(
            new ManticoreClient(new MockHttpClient($responses), 'http://manticore:9308'),
            $this->createStub(OrderRepository::class),
            'orders_test',
        );
    }

    private function order(): Order
    {
        $order = new Order()
            ->setHash('abc')
            ->setEmail('john@example.com')
            ->setName('Order')
            ->setCreateDate(new DateTimeImmutable('2026-01-01'));

        new \ReflectionProperty(Order::class, 'id')->setValue($order, 7);

        return $order;
    }
}
