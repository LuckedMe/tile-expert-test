<?php

declare(strict_types=1);

namespace App\Controller\Api\Product;

use App\Contract\Dictionary\ApiDocTag;
use App\ControllerRequest\Api\Product\GetProductRequest;
use App\DTO\Data\ProductDetailsDTO;
use App\Message\Query\Product\GetProductDetailsQuery;
use App\Service\MessageBusHandler;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class GetProductDetailsController extends AbstractController
{
    #[Route(
        path: '/api/products',
        name: 'api.product.details',
        options: ['expose' => true],
        methods: ['GET']
    )]
    #[OA\Get(
        summary: 'Get product price',
        description: 'Reads the price in EUR from the tile.expert product page '
            . '(https://tile.expert/it/tile/{factory}/{collection}/a/{article}). Results are cached for one hour.',
    )]
    #[OA\Response(ref: '#/components/responses/error', response: Response::HTTP_UNPROCESSABLE_ENTITY)]
    #[OA\Response(response: Response::HTTP_BAD_REQUEST, description: 'tile.expert is unavailable or refused the request')]
    #[OA\Response(ref: '#/components/responses/not_found', response: Response::HTTP_NOT_FOUND, description: 'Product page or its price was not found')]
    #[OA\Response(
        response: Response::HTTP_OK,
        description: 'Product price',
        content: new OA\JsonContent(
            ref: new Model(
                type: ProductDetailsDTO::class,
                groups: ['product_details'],
            )
        )
    )]
    #[OA\Parameter(name: 'factory', in: 'query', required: true, schema: new OA\Schema(type: 'string'), example: 'marca-corona')]
    #[OA\Parameter(name: 'collection', in: 'query', required: true, schema: new OA\Schema(type: 'string'), example: 'arteseta')]
    #[OA\Parameter(name: 'article', in: 'query', required: true, schema: new OA\Schema(type: 'string'), example: 'k263-arteseta-camoscio-s000628660')]
    #[OA\Tag(name: ApiDocTag::PRODUCTS)]
    public function __invoke(
        GetProductRequest $request,
        MessageBusHandler $messageBusHandler,
    ): JsonResponse {
        /** @var ProductDetailsDTO $dto */
        $dto = $messageBusHandler->handleQuery(new GetProductDetailsQuery(
            factory: $request->factory,
            collection: $request->collection,
            article: $request->article,
        ));

        return $this->json(
            data: $dto,
            context: ['groups' => ['product_details']]
        );
    }
}
