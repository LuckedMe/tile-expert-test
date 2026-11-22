<?php

declare(strict_types=1);

namespace App\DTO\Collection;

use App\DTO\Entity\OrderGroupDTO;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\Serializer\Attribute\Groups;

final readonly class OrderGroupCollectionDTO extends AbstractCollectionDTO
{
    /** @var OrderGroupDTO[] */
    #[Groups(['order_collection'])]
    #[OA\Property(
        type: 'array',
        items: new OA\Items(ref: new Model(type: OrderGroupDTO::class, groups: ['order_collection'])),
    )]
    public array $items;
}
