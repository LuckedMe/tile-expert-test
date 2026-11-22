<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api\Order;

use App\Entity\Order;
use App\Entity\OrderArticle;
use App\Tests\Support\ApiTestCase;

final class CreateOrderTest extends ApiTestCase
{
    public function testCreatesOrder(): void
    {
        $this->request('POST', '/api/orders', self::orderPayload());

        self::assertResponseStatusCodeSame(204);

        /** @var Order $order */
        $order = $this->em()->getRepository(Order::class)->findOneBy(['email' => 'test@example.com']);
        self::assertNotNull($order);
        self::assertSame('Order name', $order->getName());
        self::assertSame('en_GB', $order->getLocale());
        self::assertSame('visa', $order->getPayType()->value);
    }

    public function testSavesArticlesOfTheOrder(): void
    {
        $this->request('POST', '/api/orders', self::orderPayload([
            'articles' => [
                self::articlePayload(),
                self::articlePayload([
                    'article_id' => 7, 'amount' => 3, 'price' => 9.99, 'price_eur' => null, 'currency' => null,
                    'measure' => null, 'delivery_time_min' => null, 'delivery_time_max' => null,
                    'multiple_pallet' => null, 'swimming_pool' => false,
                ]),
            ],
        ]));

        self::assertResponseStatusCodeSame(204);

        $order = $this->em()->getRepository(Order::class)->findOneBy(['email' => 'test@example.com']);
        self::assertNotNull($order);

        $articles = $this->em()->getRepository(OrderArticle::class)->findBy(['order' => $order], ['id' => 'ASC']);
        self::assertCount(2, $articles);

        [$first, $second] = $articles;
        self::assertSame(5, $first->getArticleId());
        self::assertSame(12.5, $first->getAmount());
        self::assertSame(30.2, $first->getPrice());
        self::assertSame(30.2, $first->getPriceEur());
        self::assertSame('EUR', $first->getCurrency());
        self::assertSame('m', $first->getMeasure());
        self::assertSame('2026-12-01', $first->getDeliveryTimeMin()->format('Y-m-d'));
        self::assertSame('2026-12-15', $first->getDeliveryTimeMax()->format('Y-m-d'));
        self::assertSame(20.0, $first->getWeight());
        self::assertSame(1, $first->getMultiplePallet());
        self::assertSame(1.44, $first->getPackaging());
        self::assertTrue($first->isSwimmingPool());

        self::assertSame(7, $second->getArticleId());
        self::assertSame(9.99, $second->getPrice());
        self::assertNull($second->getPriceEur());
        self::assertNull($second->getDeliveryTimeMin());
        self::assertFalse($second->isSwimmingPool());
    }

    public function testCreatedArticlesAreReturnedByTheDetailsEndpoint(): void
    {
        $this->request('POST', '/api/orders', self::orderPayload());
        $id = $this->em()->getRepository(Order::class)->findOneBy([])->getId();

        $data = $this->request('GET', '/api/orders/' . $id);

        self::assertCount(1, $data['articles']);
        self::assertSame(5, $data['articles'][0]['article_id']);
        self::assertSame(30.2, $data['articles'][0]['price']);
    }

    public function testArticlesAreRequired(): void
    {
        $payload = self::orderPayload();
        unset($payload['articles']);

        $data = $this->request('POST', '/api/orders', $payload);

        self::assertResponseStatusCodeSame(422);
        self::assertArrayHasKey('articles', $data['errors']['fields']);
    }

    public function testArticlesMustNotBeEmpty(): void
    {
        $data = $this->request('POST', '/api/orders', self::orderPayload(['articles' => []]));

        self::assertResponseStatusCodeSame(422);
        self::assertArrayHasKey('articles', $data['errors']['fields']);
    }

    public function testArticlesMustBeAnArray(): void
    {
        $this->request('POST', '/api/orders', self::orderPayload(['articles' => 'five tiles']));

        self::assertResponseStatusCodeSame(400);
    }

    public function testArticleValidationErrors(): void
    {
        $data = $this->request('POST', '/api/orders', self::orderPayload([
            'articles' => [
                self::articlePayload(),
                ['article_id' => 0, 'amount' => -1, 'currency' => 'GBP', 'multiple_pallet' => 9, 'delivery_time_min' => 'soon'],
            ],
        ]));

        self::assertResponseStatusCodeSame(422);

        $fields = array_keys($data['errors']['fields']);
        foreach (['article_id', 'amount', 'price', 'currency', 'multiple_pallet', 'delivery_time_min', 'weight', 'packaging_count', 'pallet', 'packaging'] as $field) {
            self::assertContains('articles[1].' . $field, $fields);
        }
        // only the second article is invalid
        self::assertCount(10, $fields);
    }

    public function testNothingIsSavedWhenAnArticleIsInvalid(): void
    {
        $this->request('POST', '/api/orders', self::orderPayload([
            'articles' => [self::articlePayload(), self::articlePayload(['amount' => 0])],
        ]));

        self::assertResponseStatusCodeSame(422);
        self::assertSame(0, (int) $this->em()->getConnection()->fetchOne('SELECT COUNT(*) FROM orders'));
        self::assertSame(0, (int) $this->em()->getConnection()->fetchOne('SELECT COUNT(*) FROM orders_article'));
    }

    public function testNewOrderIsSearchable(): void
    {
        $this->request('POST', '/api/orders', self::orderPayload(['email' => 'zebrafinch@example.com']));
        self::assertResponseStatusCodeSame(204);

        $data = $this->request('GET', '/api/orders?query=zebra');

        self::assertSame([['group' => (new \DateTimeImmutable())->format('Y-m-d'), 'count' => 1]], $data['items']);
    }

    public function testValidationErrors(): void
    {
        $data = $this->request('POST', '/api/orders', ['email' => 'not-an-email']);

        self::assertResponseStatusCodeSame(422);
        self::assertSame(422, $data['code']);

        $fields = $data['errors']['fields'];
        foreach (['hash', 'token', 'email', 'vat_type', 'payment_type', 'locale', 'currency', 'measure', 'name', 'articles'] as $field) {
            self::assertArrayHasKey($field, $fields, $field);
        }
    }

    public function testRejectsUnknownEnumValue(): void
    {
        $data = $this->request('POST', '/api/orders', self::orderPayload(['currency' => 'GBP']));

        self::assertResponseStatusCodeSame(422);
        self::assertArrayHasKey('currency', $data['errors']['fields']);
    }

    public function testRejectsDuplicateHash(): void
    {
        $this->request('POST', '/api/orders', self::orderPayload());
        self::assertResponseStatusCodeSame(204);

        $data = $this->request('POST', '/api/orders', self::orderPayload());

        self::assertResponseStatusCodeSame(422);
        self::assertArrayHasKey('hash', $data['errors']['fields']);
    }

    public function testBadJson(): void
    {
        $this->client->request('POST', '/api/orders', server: ['CONTENT_TYPE' => 'application/json'], content: '{bad json');

        self::assertResponseStatusCodeSame(400);
        self::assertSame('Invalid JSON request', json_decode($this->client->getResponse()->getContent(), true)['message']);
    }
}
