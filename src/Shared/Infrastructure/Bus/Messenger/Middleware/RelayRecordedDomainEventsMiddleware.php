<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Bus\Messenger\Middleware;

use App\Shared\Application\Event\EventBus;
use App\Shared\Infrastructure\Bus\DomainEventBuffer;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Messenger\Stamp\ReceivedStamp;
use Throwable;

/**
 * Sits *outside* the Doctrine transaction middleware on `command.bus`. It lets the
 * command handler and the transaction run to completion, then publishes the
 * domain events the aggregates recorded — so subscribers, and any queue message
 * they emit, only ever see committed state.
 *
 * On the worker side (a message received from a transport) the buffer is drained
 * without publishing: those events were already relayed when the command first ran.
 */
final readonly class RelayRecordedDomainEventsMiddleware implements MiddlewareInterface
{
    public function __construct(
        private DomainEventBuffer $buffer,
        private EventBus $eventBus,
    ) {
    }

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        try {
            $envelope = $stack->next()->handle($envelope, $stack);
        } catch (Throwable $exception) {
            // The transaction rolled back; discard anything the aborted unit of
            // work managed to record.
            $this->buffer->drain();

            throw $exception;
        }

        $events = $this->buffer->drain();

        if ($envelope->last(ReceivedStamp::class) === null && $events !== []) {
            $this->eventBus->publish(...$events);
        }

        return $envelope;
    }
}
