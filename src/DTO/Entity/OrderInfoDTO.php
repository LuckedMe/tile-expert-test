<?php

declare(strict_types=1);

namespace App\DTO\Entity;

use App\DTOFactory\Entity\OrderInfoDTOFactory;
use OpenApi\Attributes as OA;
use Symfony\Component\Serializer\Attribute\Groups;

/**
 * @see OrderInfoDTOFactory
 */
readonly class OrderInfoDTO
{
    public function __construct(
        #[Groups(['order_collection', 'order_info'])]
        #[OA\Property(type: 'number')]
        public int $id,
    ) {
    }
}
