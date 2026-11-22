<?php

declare(strict_types=1);

namespace App\Tests\Functional\MessageHandler;

use App\ControllerRequest\Api\Order\CreateOrderArticleRequest;
use App\ControllerRequest\Api\Order\CreateOrderRequest;
use App\Entity\Order;
use App\Exception\Validation\UnprocessableEntityException;
use App\Exception\Validation\ValidationFailedException;
use App\Message\Command\Order\CreateOrderCommand;
use App\MessageHandler\Command\Order\CreateOrderHandler;
use App\Service\Order\OrderSearchIndexer;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;

use function md5;

final class CreateOrderHandlerTest extends KernelTestCase
{
    private CreateOrderHandler $handler;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->handler = static::getContainer()->get(CreateOrderHandler::class);
        $this->em      = static::getContainer()->get(EntityManagerInterface::class);

        $connection = $this->em->getConnection();
        $connection->executeStatement('SET FOREIGN_KEY_CHECKS = 0');
        $connection->executeStatement('TRUNCATE TABLE orders_article');
        $connection->executeStatement('TRUNCATE TABLE orders');
        $connection->executeStatement('SET FOREIGN_KEY_CHECKS = 1');

        static::getContainer()->get(OrderSearchIndexer::class)->reindexAll();
    }

    public function testCreatesOrderFromCreateOrderRequest(): void
    {
        ($this->handler)(new CreateOrderCommand($this->request('a')));

        $order = $this->findOrder('a@example.com');
        self::assertSame('a@example.com', $order->getEmail());
        self::assertCount(1, $order->getArticles());
        self::assertSame(10.5, $order->getArticles()->first()->getPrice());
    }

    public function testCreatesOrderFromArray(): void
    {
        ($this->handler)(new CreateOrderCommand($this->payload('b')));

        self::assertCount(1, $this->findOrder('b@example.com')->getArticles());
    }

    public function testCreatesOrderFromHttpRequest(): void
    {
        $httpRequest = Request::create('/api/orders', 'POST', $this->payload('c'));

        ($this->handler)(new CreateOrderCommand($httpRequest));

        self::assertCount(1, $this->findOrder('c@example.com')->getArticles());
    }

    public function testInvalidHttpRequestIsValidated(): void
    {
        $this->expectException(ValidationFailedException::class);

        ($this->handler)(new CreateOrderCommand(Request::create('/api/orders', 'POST', ['email' => 'nope'])));
    }

    #[DataProvider('unsupportedSources')]
    public function testThrowsWhenThereIsNoCreationHandler(mixed $source): void
    {
        $this->expectException(UnprocessableEntityException::class);
        $this->expectExceptionMessage('No handler found for the given order source');

        ($this->handler)(new CreateOrderCommand($source));
    }

    public static function unsupportedSources(): iterable
    {
        yield 'integer'  => [123];
        yield 'string'   => ['order'];
        yield 'null'     => [null];
        yield 'stdClass' => [new \stdClass()];
    }

    private function findOrder(string $email): ?Order
    {
        $this->em->clear();

        return $this->em->getRepository(Order::class)->findOneBy(['email' => $email]);
    }

    private function request(string $key): CreateOrderRequest
    {
        $p = $this->payload($key);

        return new CreateOrderRequest(
            hash: $p['hash'],
            token: $p['token'],
            email: $p['email'],
            vatType: $p['vat_type'],
            vatNumber: $p['vat_number'],
            taxNumber: $p['tax_number'],
            paymentType: $p['payment_type'],
            locale: $p['locale'],
            currency: $p['currency'],
            measure: $p['measure'],
            name: $p['name'],
            articles: [new CreateOrderArticleRequest(
                articleId: 5, amount: 2.0, price: 10.5, weight: 20.0, packagingCount: 1.0, pallet: 50.0, packaging: 1.44,
            )],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(string $key): array
    {
        return [
            'hash'         => md5($key),
            'token'        => md5($key) . md5($key . '2'),
            'email'        => $key . '@example.com',
            'vat_type'     => 'vat-type-1',
            'vat_number'   => '0123456789',
            'tax_number'   => '9876543210',
            'payment_type' => 'visa',
            'locale'       => 'en_GB',
            'currency'     => 'EUR',
            'measure'      => 'm',
            'name'         => 'Order ' . $key,
            'articles'     => [[
                'article_id' => 5, 'amount' => 2, 'price' => 10.5, 'weight' => 20,
                'packaging_count' => 1, 'pallet' => 50, 'packaging' => 1.44,
            ]],
        ];
    }
}
