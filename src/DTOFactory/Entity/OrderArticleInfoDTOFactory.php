<?php

declare(strict_types=1);

namespace App\DTOFactory\Entity;

use App\DTO\Entity\OrderArticleInfoDTO;
use App\Entity\OrderArticle;

readonly class OrderArticleInfoDTOFactory
{
    public function fromEntity(OrderArticle $article): OrderArticleInfoDTO
    {
        return new OrderArticleInfoDTO(
            id: $article->getId(),
            articleId: $article->getArticleId(),
            amount: $article->getAmount(),
            price: $article->getPrice(),
            priceEur: $article->getPriceEur(),
            currency: $article->getCurrency(),
            measure: $article->getMeasure(),
            deliveryTimeMin: $article->getDeliveryTimeMin(),
            deliveryTimeMax: $article->getDeliveryTimeMax(),
            weight: $article->getWeight(),
            multiplePallet: $article->getMultiplePallet(),
            packagingCount: $article->getPackagingCount(),
            pallet: $article->getPallet(),
            packaging: $article->getPackaging(),
            swimmingPool: $article->isSwimmingPool(),
        );
    }
}
