<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api\Order;

use App\Tests\Support\ApiTestCase;

final class GetOrderDetailsTest extends ApiTestCase
{
    public function testReturnsOrderWithArticles(): void
    {
        $order = $this->createOrder('2025-01-10 10:00:00', 'alice@example.com', 'Alpha');
        $id    = $order->getId();
        $this->createArticle($order, 5, 30.2);

        $data = $this->request('GET', '/api/orders/' . $id);

        self::assertResponseIsSuccessful();
        self::assertSame($id, $data['id']);
        self::assertSame('alice@example.com', $data['email']);
        self::assertSame('Alpha', $data['name']);
        self::assertSame('en_GB', $data['locale']);
        self::assertSame('vat-type-1', $data['vat_type']);
        self::assertSame('EUR', $data['currency']);
        self::assertSame('2025-01-10T10:00:00+00:00', $data['created_at']);

        self::assertCount(1, $data['articles']);
        self::assertSame(5, $data['articles'][0]['article_id']);
        self::assertSame(12.5, $data['articles'][0]['amount']);
        self::assertSame(30.2, $data['articles'][0]['price']);
        self::assertFalse($data['articles'][0]['swimming_pool']);
    }

    public function testOrderWithoutArticles(): void
    {
        $order = $this->createOrder();

        $data = $this->request('GET', '/api/orders/' . $order->getId());

        self::assertResponseIsSuccessful();
        self::assertSame([], $data['articles']);
    }

    public function testNotFound(): void
    {
        $data = $this->request('GET', '/api/orders/999999');

        self::assertResponseStatusCodeSame(404);
        self::assertSame(['code' => 404, 'message' => 'Order Not Found', 'errors' => null], $data);
    }

    public function testNonNumericIdDoesNotMatchTheRoute(): void
    {
        $this->request('GET', '/api/orders/abc');

        self::assertResponseStatusCodeSame(404);
    }
}
