<?php

declare(strict_types=1);

namespace App\DTO\Entity;

use App\Contract\Dictionary\Currency;
use App\Contract\Dictionary\Measure;
use App\Contract\Dictionary\PaymentType;
use App\Contract\Dictionary\VatType;
use App\DTOFactory\Entity\OrderDetailsDTOFactory;
use DateTimeImmutable;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\Serializer\Attribute\Groups;

/**
 * @see OrderDetailsDTOFactory
 */
readonly class OrderDetailsDTO
{
    public function __construct(
        #[Groups(['order_details'])]
        public int $id,

        #[Groups(['order_details'])]
        public ?string $hash,

        #[Groups(['order_details'])]
        public ?string $token,

        #[Groups(['order_details'])]
        public ?string $email,

        #[Groups(['order_details'])]
        public ?VatType $vatType,

        #[Groups(['order_details'])]
        public ?string $vatNumber,

        #[Groups(['order_details'])]
        public ?string $taxNumber,

        #[Groups(['order_details'])]
        public ?PaymentType $paymentType,

        #[Groups(['order_details'])]
        public ?string $locale,

        #[Groups(['order_details'])]
        public ?Currency $currency,

        #[Groups(['order_details'])]
        public ?Measure $measure,

        #[Groups(['order_details'])]
        public ?string $name,

        #[Groups(['order_details'])]
        #[OA\Property(type: 'string', format: 'date-time')]
        public ?DateTimeImmutable $createdAt,

        #[Groups(['order_details'])]
        #[OA\Property(type: 'string', format: 'date-time')]
        public ?DateTimeImmutable $updatedAt,

        /** @var OrderArticleInfoDTO[] */
        #[Groups(['order_details'])]
        #[OA\Property(
            type: 'array',
            items: new OA\Items(ref: new Model(type: OrderArticleInfoDTO::class, groups: ['order_details'])),
        )]
        public array $articles = [],
    ) {
    }
}
