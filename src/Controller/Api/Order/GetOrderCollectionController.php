<?php

declare(strict_types=1);

namespace App\Controller\Api\Order;

use App\Contract\Dictionary\ApiDocTag;
use App\ControllerRequest\Api\Order\GetOrderCollectionRequest;
use App\DTO\Collection\OrderGroupCollectionDTO;
use App\DTOFactory\Collection\OrderCriteriaDTOFactory;
use App\Message\Query\Order\GetOrderCollectionQuery;
use App\Service\MessageBusHandler;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class GetOrderCollectionController extends AbstractController
{
    #[Route(
        path: '/api/orders',
        name: 'api.order.collection',
        options: ['expose' => true],
        methods: ['GET']
    )]
    #[OA\Get(
        description: 'Paginated by groups. `query` is a Manticore wildcard search over hash, email, name, number, '
            . 'client and company names and description (`foo` matches `*foo*`, `*` matches everything).',
        summary: 'Get the number of orders grouped by day, month or year',
    )]
    #[OA\Response(ref: '#/components/responses/error', response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'Invalid query parameters')]
    // custom
    #[OA\Parameter(ref: '#/components/parameters/page')]
    #[OA\Parameter(ref: '#/components/parameters/per_page')]
    #[OA\Parameter(ref: '#/components/parameters/query')]
    #[OA\Parameter(ref: '#/components/parameters/start_day')]
    #[OA\Parameter(ref: '#/components/parameters/end_day')]
    // #[OA\Parameter(ref: '#/components/parameters/sort')]
    #[OA\Parameter(
        name: 'sort',
        in: 'query',
        required: false,
        schema: new OA\Schema(
            type: 'string',
            default: 'group desc'
        ),
        examples: [
            new OA\Examples(example: 'group desc', summary: 'Newest groups first', value: 'group desc'),
            new OA\Examples(example: 'group asc', summary: 'Oldest groups first', value: 'group asc'),
            new OA\Examples(example: 'count desc', summary: 'Biggest groups first', value: 'count desc'),
        ]
    )]
    #[OA\Parameter(
        name: 'group_by',
        in: 'query',
        required: false,
        schema: new OA\Schema(type: 'string', default: 'day', enum: ['day', 'month', 'year']),
    )]
    #[OA\Response(
        response: Response::HTTP_OK,
        description: 'Page of groups with the number of orders in each',
        content: new OA\JsonContent(
            ref: new Model(
                type: OrderGroupCollectionDTO::class,
                groups: ['collection_meta', 'order_collection'],
            )
        ),
    )]
    #[OA\Tag(name: ApiDocTag::ORDERS)]
    public function __invoke(
        MessageBusHandler $messageBusHandler,
        OrderCriteriaDTOFactory $factory,
        GetOrderCollectionRequest $request,
    ): JsonResponse {
        /** @var OrderGroupCollectionDTO $dto */
        $dto = $messageBusHandler->handleQuery(
            new GetOrderCollectionQuery(criteria: $factory->fromCollectionRequest($request))
        );

        return $this->json(
            data: $dto,
            context: ['groups' => ['collection_meta', 'order_collection']]
        );
    }
}
