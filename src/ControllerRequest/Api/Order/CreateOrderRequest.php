<?php

declare(strict_types=1);

namespace App\ControllerRequest\Api\Order;

use App\Contract\ControllerRequest\ControllerRequestInterface;
use App\Contract\Dictionary\Currency;
use App\Contract\Dictionary\Measure;
use App\Contract\Dictionary\PaymentType;
use App\Contract\Dictionary\VatType;
use App\ControllerRequestModifier\StripTagsModifier;
use App\ControllerRequestModifier\TrimModifier;
use App\Entity\Order;
use App\Validator\Constraint\EntityNotExists;
use App\Validator\Constraint\EnumValues;
use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

readonly class CreateOrderRequest implements ControllerRequestInterface
{
    public function __construct(
        #[TrimModifier]
        #[StripTagsModifier]
        #[Assert\NotBlank]
        #[Assert\Length(min: 32, max: 32)]
        #[EntityNotExists(entity: Order::class, property: 'hash')]
        #[OA\Property(description: 'Order hash', type: 'string')]
        public ?string $hash = null,

        #[TrimModifier]
        #[StripTagsModifier]
        #[Assert\NotBlank]
        #[Assert\Length(min: 64, max: 64)]
        #[OA\Property(description: 'Unique user hash', type: 'string')]
        public ?string $token = null,

        #[TrimModifier]
        #[Assert\NotBlank]
        #[Assert\Email]
        #[Assert\Length(max: 100)]
        #[OA\Property(description: 'Order email', type: 'string')]
        public ?string $email = null,

        #[Assert\NotBlank]
        #[EnumValues(VatType::class)]
        public ?string $vatType = null,

        #[TrimModifier]
        #[Assert\Length(max: 100)]
        public ?string $vatNumber = null,

        #[TrimModifier]
        #[Assert\Length(max: 50)]
        public ?string $taxNumber = null,

        #[Assert\NotBlank]
        #[EnumValues(PaymentType::class)]
        public ?string $paymentType = null,

        #[TrimModifier]
        #[Assert\NotBlank]
        #[Assert\Length(min: 5, max: 5)]
        public ?string $locale = null,

        #[Assert\NotBlank]
        #[EnumValues(Currency::class)]
        public ?string $currency = null,

        #[Assert\NotBlank]
        #[EnumValues(Measure::class)]
        public ?string $measure = null,

        #[TrimModifier]
        #[Assert\NotBlank]
        #[Assert\Length(max: 200)]
        public ?string $name = null,
    ) {
    }
}
