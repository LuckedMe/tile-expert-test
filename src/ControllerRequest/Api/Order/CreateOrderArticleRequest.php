<?php

declare(strict_types=1);

namespace App\ControllerRequest\Api\Order;

use App\Contract\Dictionary\Currency;
use App\Contract\Dictionary\Measure;
use App\Validator\Constraint\EnumValues;
use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * One item of the "articles" array of {@see CreateOrderRequest}.
 */
readonly class CreateOrderArticleRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Positive]
        #[OA\Property(description: 'Collection (article) id', type: 'integer', example: 5)]
        public ?int $articleId = null,

        #[Assert\NotBlank]
        #[Assert\Positive]
        #[OA\Property(description: 'Number of articles in the unit of measure', type: 'number', example: 12.5)]
        public ?float $amount = null,

        #[Assert\NotBlank]
        #[Assert\PositiveOrZero]
        #[OA\Property(description: 'Price at the time of the order', type: 'number', example: 30.2)]
        public ?float $price = null,

        #[Assert\PositiveOrZero]
        #[OA\Property(description: 'Price in euros', type: 'number', example: 30.2)]
        public ?float $priceEur = null,

        #[EnumValues(Currency::class)]
        #[OA\Property(description: 'Currency the price is set for', type: 'string', example: 'EUR')]
        public ?string $currency = null,

        #[EnumValues(Measure::class)]
        #[OA\Property(description: 'Unit of measure the price is set for', type: 'string', example: 'm')]
        public ?string $measure = null,

        #[Assert\Date]
        #[OA\Property(type: 'string', format: 'date', example: '2026-12-01')]
        public ?string $deliveryTimeMin = null,

        #[Assert\Date]
        #[Assert\GreaterThanOrEqual(propertyPath: 'deliveryTimeMin')]
        #[OA\Property(type: 'string', format: 'date', example: '2026-12-15')]
        public ?string $deliveryTimeMax = null,

        #[Assert\NotBlank]
        #[Assert\PositiveOrZero]
        #[OA\Property(description: 'Package weight', type: 'number', example: 20)]
        public ?float $weight = null,

        #[Assert\Choice(choices: [1, 2, 3])]
        #[OA\Property(description: '1 - multiple of a package, 2 - multiple of a pallet, 3 - not less than a pallet', type: 'integer', example: 1)]
        public ?int $multiplePallet = null,

        #[Assert\NotBlank]
        #[Assert\Positive]
        #[OA\Property(description: 'Quantity multiple in which the product can be added to the order', type: 'number', example: 1)]
        public ?float $packagingCount = null,

        #[Assert\NotBlank]
        #[Assert\PositiveOrZero]
        #[OA\Property(description: 'Quantity per pallet', type: 'number', example: 50)]
        public ?float $pallet = null,

        #[Assert\NotBlank]
        #[Assert\Positive]
        #[OA\Property(description: 'Quantity per package', type: 'number', example: 1.44)]
        public ?float $packaging = null,

        #[Assert\Type('bool')]
        #[OA\Property(description: 'Tile specifically for swimming pools', type: 'boolean', example: false)]
        public ?bool $swimmingPool = false,
    ) {
    }
}
