<?php

declare(strict_types=1);

namespace App\Message\Query\Product;

/**
 * @see GetProductDetailsHandler
 */
readonly class GetProductDetailsQuery
{
    public function __construct(
        public string $factory,
        public string $collection,
        public string $article,
    ) {
    }
}
