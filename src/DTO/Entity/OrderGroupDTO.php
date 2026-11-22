<?php

declare(strict_types=1);

namespace App\DTO\Entity;

use OpenApi\Attributes as OA;
use Symfony\Component\Serializer\Attribute\Groups;

readonly class OrderGroupDTO
{
    public function __construct(
        #[Groups(['order_collection'])]
        #[OA\Property(description: 'Group key: Y-m-d, Y-m or Y depending on group_by', type: 'string', example: '2026-11-22')]
        public string $group,

        #[Groups(['order_collection'])]
        #[OA\Property(description: 'Number of orders in the group', type: 'integer', example: 18)]
        public int $count,
    ) {
    }
}
