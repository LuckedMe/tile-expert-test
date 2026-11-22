<?php

declare(strict_types=1);

namespace App\DTO\Entity;

use OpenApi\Attributes as OA;
use Symfony\Component\Serializer\Attribute\Groups;

/**
 * @see OrderArticleInfoDTOFactory
 */
readonly class OrderArticleInfoDTO
{
    public function __construct(
        #[Groups(['order_article_collection', 'order_article_info'])]
        #[OA\Property(type: 'number')]
        public int $id,
    ) {
    }
}
