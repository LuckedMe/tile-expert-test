<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Product;

use App\Service\Product\ProductPriceParser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ProductPriceParserTest extends TestCase
{
    #[DataProvider('pages')]
    public function testParse(string $html, ?float $expected): void
    {
        self::assertSame($expected, new ProductPriceParser()->parse($html));
    }

    public static function pages(): iterable
    {
        yield 'json-ld offer'          => ['<script type="application/ld+json">{"@type":"Product","offers":{"price":"59.99"}}</script>', 59.99];
        yield 'json-ld numeric'        => ['<script type="application/ld+json">{"offers":{"price":12}}</script>', 12.0];
        yield 'json-ld lowPrice'       => ['<script type="application/ld+json">{"offers":{"lowPrice":"7.5"}}</script>', 7.5];
        yield 'json-ld nested graph'   => ['<script type="application/ld+json">{"@graph":[{"@type":"WebSite"},{"@type":"Product","offers":[{"price":"3.10"}]}]}</script>', 3.1];
        yield 'invalid json-ld, meta'  => ['<script type="application/ld+json">{broken</script><meta property="product:price:amount" content="8.20">', 8.2];
        yield 'open graph'             => ['<meta property="og:price:amount" content="45.00">', 45.0];
        yield 'microdata content'      => ['<span itemprop="price" content="19.90">19,90 €</span>', 19.9];
        yield 'microdata text comma'   => ['<span itemprop="price">€ 59,99</span>', 59.99];
        yield 'thousands separator'    => ['<meta property="product:price:amount" content="1.234,50">', 1234.5];
        yield 'no price'               => ['<html><body><p>nothing</p></body></html>', null];
        yield 'empty'                  => ['', null];
        yield 'non numeric price'      => ['<span itemprop="price">on request</span>', null];
    }
}
