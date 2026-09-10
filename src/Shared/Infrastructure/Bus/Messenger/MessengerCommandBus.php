<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Bus\Messenger;

use App\Shared\Application\Command\Command;
use App\Shared\Application\Command\CommandBus;
use App\Shared\Infrastructure\Bus\Messenger\Exception\MessengerExceptionUnwrapper;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Adapter binding the {@see CommandBus} port to Symfony Messenger's `command.bus`.
 *
 * Messenger wraps any exception a handler throws in {@see HandlerFailedException};
 * this adapter unwraps it so callers catch the original domain exception.
 */
final readonly class MessengerCommandBus implements CommandBus
{
    public function __construct(
        private MessageBusInterface $commandBus
    ) {
    }

    public function dispatch(Command $command): void
    {
        try {
            $this->commandBus->dispatch($command);
        } catch (HandlerFailedException $exception) {
            throw MessengerExceptionUnwrapper::unwrap($exception);
        }
    }
}
