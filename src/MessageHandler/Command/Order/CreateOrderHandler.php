<?php

declare(strict_types=1);

namespace App\MessageHandler\Command\Order;

use App\Contract\EntityFactory\OrderEntityFactoryInterface;
use App\Contract\MessageHandler\CommandHandlerInterface;
use App\Exception\Validation\UnprocessableEntityException;
use App\Message\Command\Order\CreateOrderCommand;
use App\Message\Event\Order\OrderCreatedEvent;
use App\Repository\OrderRepository;
use App\Service\MessageBusHandler;
use Exception;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class CreateOrderHandler implements CommandHandlerInterface
{
    /**
     * @param iterable<OrderEntityFactoryInterface> $orderFactories
     */
    public function __construct(
        private TranslatorInterface $translator,
        private OrderRepository $orderRepository,
        private MessageBusHandler $messageBusHandler,

        #[AutowireIterator('app.order.entity_factory')]
        private iterable $orderFactories,
    ) {
    }

    public function __invoke(CreateOrderCommand $command): void
    {
        $order = $this->resolveFactory($command)->create($command->data);

        try {
            $this->orderRepository->save($order);
        } catch (Exception $e) {
            throw new UnprocessableEntityException($e->getMessage(), $e->getCode(), $e);
        }

        $this->messageBusHandler->handleEvent(new OrderCreatedEvent($order->getId()));
    }

    private function resolveFactory(CreateOrderCommand $command): OrderEntityFactoryInterface
    {
        foreach ($this->orderFactories as $factory) {
            if ($factory->supports($command->data)) {
                return $factory;
            }
        }

        throw new UnprocessableEntityException($this->translator->trans('ORDER.NO_FACTORY', [], 'validators'));
    }
}
