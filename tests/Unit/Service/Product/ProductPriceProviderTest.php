<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Product;

use App\Exception\CommunicationException;
use App\Exception\Validation\EntityNotFoundException;
use App\Service\Product\ProductPriceParser;
use App\Service\Product\ProductPriceProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ProductPriceProviderTest extends TestCase
{
    private const string TEMPLATE = 'https://tile.expert/it/tile/%s/%s/a/%s';
    private const string PAGE     = '<meta property="product:price:amount" content="59.99">';

    public function testBuildsUrlAndCachesThePrice(): void
    {
        $urls     = [];
        $provider = $this->provider(function (string $method, string $url) use (&$urls): MockResponse {
            $urls[] = $url;

            return new MockResponse(self::PAGE);
        }, ttl: 3600);

        self::assertSame(59.99, $provider->getPrice('marca-corona', 'arteseta', 'k263'));
        self::assertSame(59.99, $provider->getPrice('marca-corona', 'arteseta', 'k263'));

        self::assertSame(['https://tile.expert/it/tile/marca-corona/arteseta/a/k263'], $urls, 'second call is served from the cache');
    }

    public function testDifferentArticlesAreCachedSeparately(): void
    {
        $calls    = 0;
        $provider = $this->provider(function () use (&$calls): MockResponse {
            ++$calls;

            return new MockResponse(self::PAGE);
        }, ttl: 3600);

        $provider->getPrice('f', 'c', 'a1');
        $provider->getPrice('f', 'c', 'a2');

        self::assertSame(2, $calls);
    }

    public function testNotFoundIsNotCached(): void
    {
        $calls    = 0;
        $provider = $this->provider(function () use (&$calls): MockResponse {
            ++$calls;

            return new MockResponse('', ['http_code' => 404]);
        }, ttl: 3600);

        for ($i = 0; $i < 2; ++$i) {
            try {
                $provider->getPrice('f', 'c', 'a');
                self::fail('EntityNotFoundException expected');
            } catch (EntityNotFoundException) {
            }
        }

        self::assertSame(2, $calls);
    }

    public function testServerErrorBecomesCommunicationException(): void
    {
        $provider = $this->provider(fn () => new MockResponse('', ['http_code' => 503]), ttl: 0);

        $this->expectException(CommunicationException::class);

        $provider->getPrice('f', 'c', 'a');
    }

    private function provider(callable $responses, int $ttl): ProductPriceProvider
    {
        $translator = $this->createStub(TranslatorInterface::class);
        $translator->method('trans')->willReturnArgument(0);

        return new ProductPriceProvider(
            new MockHttpClient($responses),
            new ArrayAdapter(),
            new ProductPriceParser(),
            $translator,
            self::TEMPLATE,
            $ttl,
        );
    }
}
