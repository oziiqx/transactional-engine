<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Bus\Messenger;

use App\Shared\Application\Event\EventBus;
use App\Shared\Domain\Event\DomainEvent;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\BusNameStamp;

/**
 * Adapter binding the {@see EventBus} port to Messenger's `event.bus`.
 *
 * Every event is dispatched individually so one failing subscriber cannot stop
 * the rest (the bus is configured with `allow_no_handlers: true`).
 */
final readonly class MessengerEventBus implements EventBus
{
    public function __construct(
        private MessageBusInterface $eventBus
    ) {
    }

    public function publish(DomainEvent ...$events): void
    {
        foreach ($events as $event) {
            $this->eventBus->dispatch($event, [new BusNameStamp('event.bus')]);
        }
    }
}
