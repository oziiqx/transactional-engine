<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Bus\Messenger;

use App\Shared\Application\Event\IntegrationMessage;
use App\Shared\Application\Event\IntegrationMessageBus;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DispatchAfterCurrentBusStamp;

/**
 * Adapter for {@see IntegrationMessageBus}. Messages are stamped so that, when a
 * listener produces one while handling a domain event, the message is only sent
 * once the surrounding bus has finished — never mid-transaction.
 */
final readonly class MessengerIntegrationMessageBus implements IntegrationMessageBus
{
    public function __construct(
        private MessageBusInterface $eventBus
    ) {
    }

    public function dispatch(IntegrationMessage $message): void
    {
        $this->eventBus->dispatch($message, [new DispatchAfterCurrentBusStamp()]);
    }
}
