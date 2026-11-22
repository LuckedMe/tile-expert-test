<?php

declare(strict_types=1);

namespace App\DTO\Collection;

use App\DTO\Entity\OrderInfoDTO;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\Serializer\Attribute\Groups;

final readonly class OrderInfoCollectionDTO extends AbstractCollectionDTO
{
    /** @var OrderInfoDTO[] */
    #[Groups(['order_collection', 'order_info'])]
    #[OA\Property(
        type: 'array',
        items: new OA\Items(
            ref: new Model(
                type: OrderInfoDTO::class,
                groups: ['order_collection', 'order_info'],
            )),
    )]
    public array $items;
}
