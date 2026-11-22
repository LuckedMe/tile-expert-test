<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Order;

use App\Exception\CommunicationException;
use App\Service\Manticore\ManticoreClient;
use App\Service\Order\OrderSearchService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

use function json_decode;

final class OrderSearchServiceTest extends TestCase
{
    #[DataProvider('queries')]
    public function testBuildExpression(string $query, ?string $expected): void
    {
        self::assertSame($expected, $this->service(new MockHttpClient())->buildExpression($query));
    }

    public static function queries(): iterable
    {
        yield 'plain word is wrapped'        => ['sample', '*sample*'];
        yield 'user wildcard is kept'        => ['foo*', 'foo*'];
        yield 'leading wildcard is kept'     => ['*@example.com', '*\@example.com'];
        yield 'all terms'                    => ['john  smith', '*john* *smith*'];
        yield 'operators are escaped'        => ['a"b(c)|d-e', '*a\"b\(c\)\|d\-e*'];
        yield 'star only'                    => ['*', null];
        yield 'blank'                        => ['   ', null];
        yield 'empty'                        => ['', null];
        yield 'stars are ignored in the mix' => ['* foo', '*foo*'];
    }

    public function testFindIdsSendsSearchAndReturnsIds(): void
    {
        $sent   = null;
        $client = new MockHttpClient(function (string $method, string $url, array $options) use (&$sent): MockResponse {
            $sent = ['method' => $method, 'url' => $url, 'body' => json_decode($options['body'], true)];

            return new MockResponse('{"hits":{"total":2,"hits":[{"_id":"7"},{"_id":9}]}}');
        });

        $ids = $this->service($client)->findIds('sample');

        self::assertSame([7, 9], $ids);
        self::assertSame('http://manticore:9308/search', $sent['url']);
        self::assertSame('orders_test', $sent['body']['table']);
        self::assertSame(['query_string' => '*sample*'], $sent['body']['query']);
    }

    public function testFindIdsDoesNotCallManticoreWhenQueryDoesNotFilter(): void
    {
        $client = new MockHttpClient(static function (): never {
            self::fail('Manticore must not be called');
        });

        self::assertNull($this->service($client)->findIds('*'));
        self::assertNull($this->service($client)->findIds(null));
    }

    public function testNoHits(): void
    {
        $client = new MockHttpClient(new MockResponse('{"hits":{"total":0,"hits":[]}}'));

        self::assertSame([], $this->service($client)->findIds('zzz'));
    }

    public function testManticoreErrorIsReported(): void
    {
        $client = new MockHttpClient(new MockResponse('{"error":"bad query"}', ['http_code' => 500]));

        $this->expectException(CommunicationException::class);
        $this->expectExceptionMessage('bad query');

        $this->service($client)->findIds('x');
    }

    public function testUnavailableManticoreIsReported(): void
    {
        $client = new MockHttpClient(static fn () => throw new \Symfony\Component\HttpClient\Exception\TransportException('connection refused'));

        $this->expectException(CommunicationException::class);

        $this->service($client)->findIds('x');
    }

    private function service(MockHttpClient $client): OrderSearchService
    {
        return new OrderSearchService(new ManticoreClient($client, 'http://manticore:9308'), 'orders_test');
    }
}
