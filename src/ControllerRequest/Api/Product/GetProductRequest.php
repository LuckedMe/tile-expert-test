<?php

declare(strict_types=1);

namespace App\ControllerRequest\Api\Product;

use App\Contract\ControllerRequest\ControllerRequestInterface;
use App\ControllerRequestModifier\TrimModifier;
use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

readonly class GetProductRequest implements ControllerRequestInterface
{
    private const string SLUG_PATTERN = '/^[a-z0-9][a-z0-9_-]*$/i';

    public function __construct(
        #[TrimModifier]
        #[Assert\NotBlank]
        #[Assert\Regex(pattern: self::SLUG_PATTERN)]
        #[OA\Property(description: 'Factory slug', type: 'string', example: 'marca-corona')]
        public ?string $factory = null,

        #[TrimModifier]
        #[Assert\NotBlank]
        #[Assert\Regex(pattern: self::SLUG_PATTERN)]
        #[OA\Property(description: 'Collection slug', type: 'string', example: 'arteseta')]
        public ?string $collection = null,

        #[TrimModifier]
        #[Assert\NotBlank]
        #[Assert\Regex(pattern: self::SLUG_PATTERN)]
        #[OA\Property(description: 'Article slug', type: 'string', example: 'k263-arteseta-camoscio-s000628660')]
        public ?string $article = null,
    ) {
    }
}
