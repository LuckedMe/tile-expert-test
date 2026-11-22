<?php

declare(strict_types=1);

namespace App\DTO\Entity;

use App\DTOFactory\Entity\OrderArticleInfoDTOFactory;
use DateTimeImmutable;
use OpenApi\Attributes as OA;
use Symfony\Component\Serializer\Attribute\Groups;

/**
 * @see OrderArticleInfoDTOFactory
 */
readonly class OrderArticleInfoDTO
{
    public function __construct(
        #[Groups(['order_details', 'order_article_collection', 'order_article_info'])]
        #[OA\Property(type: 'number')]
        public int $id,

        #[Groups(['order_details', 'order_article_collection', 'order_article_info'])]
        public ?int $articleId,

        #[Groups(['order_details', 'order_article_collection', 'order_article_info'])]
        public float $amount,

        #[Groups(['order_details', 'order_article_collection', 'order_article_info'])]
        public float $price,

        #[Groups(['order_details', 'order_article_collection', 'order_article_info'])]
        public ?float $priceEur,

        #[Groups(['order_details', 'order_article_collection', 'order_article_info'])]
        public ?string $currency,

        #[Groups(['order_details', 'order_article_collection', 'order_article_info'])]
        public ?string $measure,

        #[Groups(['order_details', 'order_article_collection', 'order_article_info'])]
        #[OA\Property(type: 'string', format: 'date')]
        public ?DateTimeImmutable $deliveryTimeMin,

        #[Groups(['order_details', 'order_article_collection', 'order_article_info'])]
        #[OA\Property(type: 'string', format: 'date')]
        public ?DateTimeImmutable $deliveryTimeMax,

        #[Groups(['order_details', 'order_article_collection', 'order_article_info'])]
        public float $weight,

        #[Groups(['order_details', 'order_article_collection', 'order_article_info'])]
        public ?int $multiplePallet,

        #[Groups(['order_details', 'order_article_collection', 'order_article_info'])]
        public float $packagingCount,

        #[Groups(['order_details', 'order_article_collection', 'order_article_info'])]
        public float $pallet,

        #[Groups(['order_details', 'order_article_collection', 'order_article_info'])]
        public float $packaging,

        #[Groups(['order_details', 'order_article_collection', 'order_article_info'])]
        public bool $swimmingPool,
    ) {
    }
}
