<?php

declare(strict_types=1);

namespace App\DTO\Data;

use OpenApi\Attributes as OA;
use Symfony\Component\Serializer\Attribute\Groups;

/**
 * @see ProductDetailsDTOFactory
 */
readonly class ProductDetailsDTO
{
    public function __construct(
        #[Groups(['product_details'])]
        #[OA\Property(type: 'number')]
        public int $id,
    ) {
    }
}
