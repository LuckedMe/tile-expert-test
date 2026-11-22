<?php

declare(strict_types=1);

namespace App\MessageHandler\Query\Order;

use App\Contract\MessageHandler\QueryHandlerInterface;
use App\DTO\Entity\OrderDetailsDTO;
use App\DTOFactory\Entity\OrderDetailsDTOFactory;
use App\Exception\Validation\EntityNotFoundException;
use App\Message\Query\Order\GetOrderDetailsQuery;
use App\Repository\OrderRepository;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class GetOrderDetailsHandler implements QueryHandlerInterface
{
    public function __construct(
        private TranslatorInterface $translator,
        private OrderRepository $orderRepository,
        private OrderDetailsDTOFactory $orderDetailsDTOFactory,
    ) {
    }

    public function __invoke(GetOrderDetailsQuery $query): OrderDetailsDTO
    {
        if (!$order = $this->orderRepository->findOneById($query->id)) {
            throw new EntityNotFoundException($this->translator->trans('ORDER.NOT_FOUND', [], 'validators'));
        }

        return $this->orderDetailsDTOFactory->fromEntity($order);
    }
}
