<?php

declare(strict_types=1);

namespace App\MessageHandler\Query\Product;

use App\Contract\MessageHandler\QueryHandlerInterface;
use App\DTO\Data\ProductDetailsDTO;
use App\Message\Query\Product\GetProductDetailsQuery;
use App\Service\Product\ProductPriceProvider;

final readonly class GetProductDetailsHandler implements QueryHandlerInterface
{
    public function __construct(
        private ProductPriceProvider $priceProvider,
    ) {
    }

    public function __invoke(GetProductDetailsQuery $query): ProductDetailsDTO
    {
        return new ProductDetailsDTO(
            price: $this->priceProvider->getPrice($query->factory, $query->collection, $query->article),
            factory: $query->factory,
            collection: $query->collection,
            article: $query->article,
        );
    }
}
