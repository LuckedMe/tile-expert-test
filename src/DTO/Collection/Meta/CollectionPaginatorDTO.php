<?php

declare(strict_types=1);

namespace App\DTO\Collection\Meta;

use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Attribute\SerializedName;

use function max;

final readonly class CollectionPaginatorDTO
{
    #[Groups(['collection_meta'])]
    public int $pages;

    public function __construct(
        #[Groups(['collection_meta'])]
        public int $page,

        #[Groups(['collection_meta'])]
        #[SerializedName('per_page')]
        public int $perPage,

        #[Groups(['collection_meta'])]
        public int $total,
    ) {
        $this->pages = $perPage > 0 ? max(1, (int) ceil($total / $perPage)) : 1;
    }
}
