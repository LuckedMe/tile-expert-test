<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Contract\Message\LowPriorityAsyncMessageInterface;
use App\Service\MessageBusHandler;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\LogicException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;

final class MessageBusHandlerTest extends TestCase
{
    public function testCommandReturnsTheHandlerResult(): void
    {
        $message = new \stdClass();
        $handler = $this->handler(command: $this->bus($message, [new HandledStamp('result', 'handler')]));

        self::assertSame('result', $handler->handleCommand($message));
    }

    public function testQueryReturnsTheHandlerResult(): void
    {
        $message = new \stdClass();
        $handler = $this->handler(query: $this->bus($message, [new HandledStamp(42, 'handler')]));

        self::assertSame(42, $handler->handleQuery($message));
    }

    public function testNotHandledCommandFails(): void
    {
        $message = new \stdClass();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('was handled zero times');

        $this->handler(command: $this->bus($message, []))->handleCommand($message);
    }

    public function testHandledTwiceFails(): void
    {
        $message = new \stdClass();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('was handled multiple times');

        $this->handler(command: $this->bus($message, [new HandledStamp(1, 'a'), new HandledStamp(2, 'b')]))
            ->handleCommand($message);
    }

    public function testAsyncMessageMayHaveNoResult(): void
    {
        $message = new class implements LowPriorityAsyncMessageInterface {};

        self::assertNull($this->handler(command: $this->bus($message, []))->handleCommand($message));
    }

    public function testEventMayHaveNoResult(): void
    {
        $message = new \stdClass();

        self::assertNull($this->handler(event: $this->bus($message, []))->handleEvent($message));
    }

    private function handler(
        ?MessageBusInterface $command = null,
        ?MessageBusInterface $query = null,
        ?MessageBusInterface $event = null,
    ): MessageBusHandler {
        return new MessageBusHandler(
            $command ?? $this->createStub(MessageBusInterface::class),
            $query ?? $this->createStub(MessageBusInterface::class),
            $event ?? $this->createStub(MessageBusInterface::class),
        );
    }

    /**
     * @param list<HandledStamp> $stamps
     */
    private function bus(object $message, array $stamps): MessageBusInterface
    {
        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects(self::once())->method('dispatch')->with($message)->willReturn(new Envelope($message, $stamps));

        return $bus;
    }
}
