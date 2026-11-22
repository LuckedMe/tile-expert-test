<?php

declare(strict_types=1);

namespace App\DTO\Data;

use OpenApi\Attributes as OA;
use Symfony\Component\Serializer\Attribute\Groups;

readonly class ProductDetailsDTO
{
    public function __construct(
        #[Groups(['product_details'])]
        #[OA\Property(type: 'number', format: 'float', example: 59.99)]
        public float $price,

        #[Groups(['product_details'])]
        #[OA\Property(type: 'string', example: 'marca-corona')]
        public string $factory,

        #[Groups(['product_details'])]
        #[OA\Property(type: 'string', example: 'arteseta')]
        public string $collection,

        #[Groups(['product_details'])]
        #[OA\Property(type: 'string', example: 'k263-arteseta-camoscio-s000628660')]
        public string $article,
    ) {
    }
}
