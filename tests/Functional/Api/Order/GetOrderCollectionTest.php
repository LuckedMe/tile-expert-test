<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api\Order;

use App\Tests\Support\ApiTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

final class GetOrderCollectionTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // 2 orders on 2025-01-10, 1 on 2025-01-20, 1 on 2025-03-05, 1 on 2026-02-01
        $this->createOrder('2025-01-10 10:00:00', 'alice@example.com', 'Alpha');
        $this->createOrder('2025-01-10 18:30:00', 'bob@sample.org', 'Beta');
        $this->createOrder('2025-01-20 09:00:00', 'carol@example.com', 'Gamma');
        $this->createOrder('2025-03-05 12:00:00', 'dave@sample.org', 'Delta');
        $this->createOrder('2026-02-01 08:00:00', 'erin@example.com', 'Epsilon');
    }

    public function testGroupsByDayByDefault(): void
    {
        $data = $this->request('GET', '/api/orders');

        self::assertResponseIsSuccessful();
        self::assertSame([
            ['group' => '2026-02-01', 'count' => 1],
            ['group' => '2025-03-05', 'count' => 1],
            ['group' => '2025-01-20', 'count' => 1],
            ['group' => '2025-01-10', 'count' => 2],
        ], $data['items']);
        self::assertEquals(['page' => 1, 'per_page' => 10, 'total' => 4, 'pages' => 1], $data['meta']['paginator']);
    }

    public function testGroupsByMonth(): void
    {
        $data = $this->request('GET', '/api/orders?group_by=month');

        self::assertSame([
            ['group' => '2026-02', 'count' => 1],
            ['group' => '2025-03', 'count' => 1],
            ['group' => '2025-01', 'count' => 3],
        ], $data['items']);
    }

    public function testGroupsByYear(): void
    {
        $data = $this->request('GET', '/api/orders?group_by=year');

        self::assertSame([
            ['group' => '2026', 'count' => 1],
            ['group' => '2025', 'count' => 4],
        ], $data['items']);
        self::assertSame(2, $data['meta']['paginator']['total']);
    }

    public function testPagination(): void
    {
        $page1 = $this->request('GET', '/api/orders?per_page=3&page=1');
        $page2 = $this->request('GET', '/api/orders?per_page=3&page=2');
        $page3 = $this->request('GET', '/api/orders?per_page=3&page=3');

        self::assertCount(3, $page1['items']);
        self::assertEquals(['page' => 1, 'per_page' => 3, 'total' => 4, 'pages' => 2], $page1['meta']['paginator']);
        self::assertSame([['group' => '2025-01-10', 'count' => 2]], $page2['items']);
        self::assertSame([], $page3['items']);
    }

    public function testSortByCountAndGroup(): void
    {
        $byCount = $this->request('GET', '/api/orders?group_by=month&sort=' . rawurlencode('count desc'));
        self::assertSame('2025-01', $byCount['items'][0]['group']);

        $oldestFirst = $this->request('GET', '/api/orders?group_by=year&sort=' . rawurlencode('group asc'));
        self::assertSame(['2025', '2026'], array_column($oldestFirst['items'], 'group'));
    }

    public function testDateFilter(): void
    {
        $data = $this->request('GET', '/api/orders?group_by=day&start_day=2025-01-10&end_day=2025-01-20');

        self::assertSame([
            ['group' => '2025-01-20', 'count' => 1],
            ['group' => '2025-01-10', 'count' => 2],
        ], $data['items']);
    }

    public function testEndDayIsInclusive(): void
    {
        $data = $this->request('GET', '/api/orders?group_by=day&start_day=2025-01-10&end_day=2025-01-10');

        self::assertSame([['group' => '2025-01-10', 'count' => 2]], $data['items']);
    }

    public function testWildcardSearch(): void
    {
        // "sample" is found inside "bob@sample.org" and "dave@sample.org"
        $data = $this->request('GET', '/api/orders?group_by=month&query=sample');
        self::assertSame([
            ['group' => '2025-03', 'count' => 1],
            ['group' => '2025-01', 'count' => 1],
        ], $data['items']);

        // explicit wildcard
        $data = $this->request('GET', '/api/orders?group_by=year&query=' . rawurlencode('alp*'));
        self::assertSame([['group' => '2025', 'count' => 1]], $data['items']);

        // several terms must all match
        $data = $this->request('GET', '/api/orders?group_by=year&query=' . rawurlencode('example epsilon'));
        self::assertSame([['group' => '2026', 'count' => 1]], $data['items']);
    }

    public function testStarAndEmptyQueryDoNotFilter(): void
    {
        $all = $this->request('GET', '/api/orders?group_by=year');

        self::assertSame($all['items'], $this->request('GET', '/api/orders?group_by=year&query=' . rawurlencode('*'))['items']);
        self::assertSame($all['items'], $this->request('GET', '/api/orders?group_by=year&query=')['items']);
    }

    public function testSearchWithoutMatches(): void
    {
        $data = $this->request('GET', '/api/orders?query=nothingmatchesthis');

        self::assertResponseIsSuccessful();
        self::assertSame([], $data['items']);
        self::assertSame(0, $data['meta']['paginator']['total']);
    }

    public function testSpecialCharactersInQueryDoNotBreakTheSearch(): void
    {
        $this->request('GET', '/api/orders?query=' . rawurlencode('a"b(c) @x -y'));

        self::assertResponseIsSuccessful();
    }

    #[DataProvider('invalidParameters')]
    public function testValidation(string $query, string $field): void
    {
        $data = $this->request('GET', '/api/orders?' . $query);

        self::assertResponseStatusCodeSame(422);
        self::assertArrayHasKey($field, $data['errors']['fields']);
    }

    public static function invalidParameters(): iterable
    {
        yield 'unknown group_by' => ['group_by=week', 'group_by'];
        yield 'page 0'           => ['page=0', 'page'];
        yield 'per_page too big' => ['per_page=1001', 'per_page'];
        yield 'bad start_day'    => ['start_day=yesterday', 'start_day'];
    }
}
