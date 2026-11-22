<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api\Product;

use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

use function json_decode;
use function rawurlencode;

final class GetProductTest extends WebTestCase
{
    private const string QUERY = 'factory=marca-corona&collection=arteseta&article=k263-arteseta-camoscio-s000628660';

    private KernelBrowser $client;

    /** @var list<string> requested urls */
    private array $requested = [];

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->client->disableReboot();

        static::getContainer()->get('cache.app')->clear();
    }

    public function testReturnsPriceFromJsonLd(): void
    {
        $this->mockUpstream(new MockResponse($this->page('<script type="application/ld+json">{"@type":"Product","offers":{"@type":"Offer","price":"59.99","priceCurrency":"EUR"}}</script>')));

        $data = $this->get(self::QUERY);

        self::assertResponseIsSuccessful();
        self::assertSame([
            'price'      => 59.99,
            'factory'    => 'marca-corona',
            'collection' => 'arteseta',
            'article'    => 'k263-arteseta-camoscio-s000628660',
        ], $data);
        self::assertSame(
            ['https://tile.expert/it/tile/marca-corona/arteseta/a/k263-arteseta-camoscio-s000628660'],
            $this->requested
        );
    }

    public function testOtherArticleIsRequestedFromItsOwnUrl(): void
    {
        $this->mockUpstream(new MockResponse($this->page('<meta property="product:price:amount" content="12,50">')));

        $data = $this->get('factory=ceramica-sant-agostino&collection=blend&article=csabldgr12-blend-grey');

        self::assertSame(12.5, $data['price']);
        self::assertSame('ceramica-sant-agostino', $data['factory']);
        self::assertSame(
            ['https://tile.expert/it/tile/ceramica-sant-agostino/blend/a/csabldgr12-blend-grey'],
            $this->requested
        );
    }

    public function testPageWithoutPriceIsNotFound(): void
    {
        $this->mockUpstream(new MockResponse($this->page('<p>no price here</p>')));

        $data = $this->get(self::QUERY);

        self::assertResponseStatusCodeSame(404);
        self::assertSame('Product price not found', $data['message']);
    }

    public function testUnknownProductIsNotFound(): void
    {
        $this->mockUpstream(new MockResponse('', ['http_code' => 404]));

        $data = $this->get(self::QUERY);

        self::assertResponseStatusCodeSame(404);
        self::assertSame('Product Not Found', $data['message']);
    }

    public function testUpstreamFailureIsReported(): void
    {
        $this->mockUpstream(new MockResponse('blocked', ['http_code' => 403]));

        $data = $this->get(self::QUERY);

        self::assertResponseStatusCodeSame(400);
        self::assertStringContainsString('Third-party service error', $data['message']);
    }

    #[DataProvider('invalidQueries')]
    public function testValidation(string $query, array $invalidFields): void
    {
        $data = $this->get($query);

        self::assertResponseStatusCodeSame(422);
        self::assertSame($invalidFields, array_keys($data['errors']['fields']));
        self::assertSame([], $this->requested, 'tile.expert must not be called for an invalid request');
    }

    public static function invalidQueries(): iterable
    {
        yield 'nothing'           => ['', ['factory', 'collection', 'article']];
        yield 'only factory'      => ['factory=marca-corona', ['collection', 'article']];
        yield 'path traversal'    => ['factory=' . rawurlencode('../x') . '&collection=a&article=b', ['factory']];
        yield 'slash in article'  => ['factory=a&collection=b&article=' . rawurlencode('x/y'), ['article']];
    }

    private function mockUpstream(MockResponse $response): void
    {
        $this->requested = [];
        $client          = new MockHttpClient(function (string $method, string $url) use ($response): MockResponse {
            $this->requested[] = $url;

            return $response;
        });

        static::getContainer()->set('http_client', $client);
    }

    private function get(string $query): array
    {
        $this->client->request('GET', '/api/products?' . $query);

        return json_decode($this->client->getResponse()->getContent(), true) ?? [];
    }

    private function page(string $head): string
    {
        return '<!DOCTYPE html><html><head>' . $head . '</head><body></body></html>';
    }
}
