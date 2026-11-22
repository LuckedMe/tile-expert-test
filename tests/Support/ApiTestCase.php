<?php

declare(strict_types=1);

namespace App\Tests\Support;

use App\Entity\Order;
use App\Entity\OrderArticle;
use App\Service\Order\OrderSearchIndexer;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

use function json_decode;
use function json_encode;
use function md5;
use function str_pad;

use const JSON_THROW_ON_ERROR;
use const STR_PAD_LEFT;

/**
 * Base class of the API tests: clean MySQL tables and a clean Manticore index for every test.
 */
abstract class ApiTestCase extends WebTestCase
{
    protected KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->client->disableReboot();

        $connection = $this->em()->getConnection();
        $connection->executeStatement('SET FOREIGN_KEY_CHECKS = 0');
        $connection->executeStatement('TRUNCATE TABLE orders_article');
        $connection->executeStatement('TRUNCATE TABLE orders');
        $connection->executeStatement('SET FOREIGN_KEY_CHECKS = 1');

        $this->indexer()->reindexAll(); // drops and recreates the (empty) test index
    }

    protected function em(): EntityManagerInterface
    {
        return static::getContainer()->get(EntityManagerInterface::class);
    }

    protected function indexer(): OrderSearchIndexer
    {
        return static::getContainer()->get(OrderSearchIndexer::class);
    }

    /**
     * Stores an order directly and puts it to the search index.
     */
    protected function createOrder(string $createDate = 'now', string $email = 'john@example.com', string $name = 'Order'): Order
    {
        static $n = 0;
        ++$n;

        $order = new Order()
            ->setHash(md5('order' . $n))
            ->setToken(md5('token' . $n) . md5('token2' . $n))
            ->setEmail($email)
            ->setLocale('en_GB')
            ->setName($name)
            ->setCreateDate(new DateTimeImmutable($createDate));

        $this->em()->persist($order);
        $this->em()->flush();
        $this->indexer()->indexOrder($order);

        return $order;
    }

    protected function createArticle(Order $order, int $articleId = 5, float $price = 30.2): OrderArticle
    {
        $article = new OrderArticle();
        $article->setOrder($order)
            ->setArticleId($articleId)
            ->setAmount(12.5)
            ->setPrice($price)
            ->setPriceEur($price)
            ->setCurrency('EUR')
            ->setMeasure('m')
            ->setWeight(20.0)
            ->setPackagingCount(1.0)
            ->setPallet(50.0)
            ->setPackaging(1.44)
            ->setSwimmingPool(false);

        $this->em()->persist($article);
        $this->em()->flush();
        $this->em()->clear();

        return $article;
    }

    /**
     * @param array<string, mixed>|null $json
     */
    protected function request(string $method, string $uri, ?array $json = null): array
    {
        $this->client->request(
            $method,
            $uri,
            server: ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'],
            content: null === $json ? null : json_encode($json, JSON_THROW_ON_ERROR),
        );

        $content = $this->client->getResponse()->getContent();

        return $content ? (json_decode($content, true) ?? []) : [];
    }

    /**
     * @return array<string, mixed>
     */
    protected static function orderPayload(array $override = []): array
    {
        return $override + [
            'hash'         => str_pad('1', 32, '0', STR_PAD_LEFT),
            'token'        => str_pad('1', 64, '0', STR_PAD_LEFT),
            'email'        => 'test@example.com',
            'vat_type'     => 'vat-type-1',
            'vat_number'   => '0123456789',
            'tax_number'   => '9876543210',
            'payment_type' => 'visa',
            'locale'       => 'en_GB',
            'currency'     => 'EUR',
            'measure'      => 'm',
            'name'         => 'Order name',
            'articles'     => [self::articlePayload()],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected static function articlePayload(array $override = []): array
    {
        return $override + [
            'article_id'        => 5,
            'amount'            => 12.5,
            'price'             => 30.2,
            'price_eur'         => 30.2,
            'currency'          => 'EUR',
            'measure'           => 'm',
            'delivery_time_min' => '2026-12-01',
            'delivery_time_max' => '2026-12-15',
            'weight'            => 20,
            'multiple_pallet'   => 1,
            'packaging_count'   => 1,
            'pallet'            => 50,
            'packaging'         => 1.44,
            'swimming_pool'     => true,
        ];
    }
}
