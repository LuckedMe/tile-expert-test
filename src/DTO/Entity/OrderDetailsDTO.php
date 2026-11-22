<?php

declare(strict_types=1);

namespace App\DTO\Entity;

use App\DTOFactory\Entity\OrderDetailsDTOFactory;
use OpenApi\Attributes as OA;
use Symfony\Component\Serializer\Attribute\Groups;

/**
 * @see OrderDetailsDTOFactory
 */
readonly class OrderDetailsDTO
{
    public function __construct(
        #[Groups(['order_details'])]
        #[OA\Property(type: 'number')]
        public int $id,
    ) {
    }
}
