<?php

declare(strict_types=1);

namespace App\DTO\Collection\Meta;

use OpenApi\Attributes as OA;
use Symfony\Component\Serializer\Attribute\Groups;

final readonly class CollectionFiltersDTO
{
    public function __construct(
        #[Groups(['collection_meta'])]
        public ?string $query = null,

        #[Groups('collection_meta')]
        public ?string $sort = null,

        #[Groups('collection_meta')]
        #[OA\Property(type: 'array', items: new OA\Items(type: 'string'))]
        public ?array $availableSortBy = [],

        // todo: rename to available_filters
        #[Groups(['collection_meta'])]
        #[OA\Property(type: 'array', items: new OA\Items(type: 'mixed'))]
        public ?array $availableFilters = [],

        #[Groups(['collection_meta'])]
        #[OA\Property(type: 'array', items: new OA\Items(type: 'string'))]
        public ?array $appliedFilters = [],
    ) {
    }
}
